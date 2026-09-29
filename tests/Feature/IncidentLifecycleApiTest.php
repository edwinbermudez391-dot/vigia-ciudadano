<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Ciclo de vida completo de la incidencia urbana:
 * assign (Alcaldía) -> start/complete (Contratista) -> inspect (Inspector),
 * incluido el bucle de retrabajo y las restricciones IDOR/rol.
 *
 * Nota: Los ciudadanos reportan incidencias de forma pública y anónima sin cuenta.
 * Este test simula el ciclo operativo institucional.
 */
final class IncidentLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Category $category;

    private User $admin;

    private User $contractor;

    private User $inspector;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->department = Department::create([
            'name' => 'Secretaría de Infraestructura y Malla Vial',
            'code' => 'SEC_INFRA',
            'contact_email' => 'infra@vigia.gov.co',
            'phone' => '(601) 555-0101',
        ]);

        $this->category = Category::create([
            'department_id' => $this->department->id,
            'name' => 'Baches y Malla Vial',
            'sla_hours' => 72,
            'description' => 'Hundimientos en vía urbana.',
        ]);

        $this->admin = $this->makeUser(UserRole::SuperAdmin);
        $this->contractor = $this->makeUser(UserRole::Contractor);
        $this->inspector = $this->makeUser(UserRole::QualityInspector);
    }

    public function test_superadmin_asigna_un_reporte_y_se_crea_la_orden(): void
    {
        $report = $this->makeReport();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => $report->id,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', WorkOrderStatus::Assigned->value)
            ->assertJsonPath('data.contractor.id', $this->contractor->id)
            ->assertJsonPath('data.report.ticket_code', $report->ticket_code);

        $this->assertSame(ReportStatus::InProgress->value, $report->fresh()->status->value);
    }

    public function test_contratista_no_puede_asignar_ordenamientos(): void
    {
        Sanctum::actingAs($this->contractor);

        $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => 1,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->toDateString(),
        ])->assertStatus(403);
    }

    public function test_no_se_asigna_un_reporte_que_ya_esta_en_ejecucion(): void
    {
        $report = $this->makeReport(ReportStatus::InProgress);

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => $report->id,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->toDateString(),
        ])->assertStatus(422);
    }

    public function test_contratista_gestiona_su_agenda_inicia_y_completa_la_obra(): void
    {
        $report = $this->makeReport();
        $order = $this->assignOrder($report);

        Sanctum::actingAs($this->contractor);

        // Agenda: contexto de la incidencia (ticket, categoría, fotos del daño).
        $this->getJson('/api/v1/work-orders')
            ->assertStatus(200)
            ->assertJsonPath('data.0.report.ticket_code', $report->ticket_code)
            ->assertJsonPath('data.0.report.category.name', $this->category->name);

        $this->patchJson("/api/v1/work-orders/{$order->id}/start")
            ->assertStatus(200);

        $this->post("/api/v1/work-orders/{$order->id}/complete", [
            'work_description' => 'Se selló el baché con asfalto en caliente y se compactó la zona.',
            'solution_photo' => $this->imageUpload('solucion.png'),
        ])->assertStatus(200);

        $order->refresh();
        $this->assertSame(WorkOrderStatus::Completed->value, $order->status->value);
        $this->assertNotNull($order->completion_date);
        Storage::disk('public')->assertExists($order->solution_photo_url);

        $this->assertSame(ReportStatus::UnderInspection->value, $report->fresh()->status->value);
    }

    public function test_otro_contratista_no_puede_completar_orden_ajena_idor(): void
    {
        $report = $this->makeReport();
        $order = $this->assignOrder($report);
        $order->update(['status' => WorkOrderStatus::InExecution->value]);

        $intruso = $this->makeUser(UserRole::Contractor);
        Sanctum::actingAs($intruso);

        $this->post("/api/v1/work-orders/{$order->id}/complete", [
            'work_description' => 'Intento de cierre sobre orden que no me pertenece.',
            'solution_photo' => $this->imageUpload('solucion.png'),
        ])->assertStatus(403);

        $this->assertSame(WorkOrderStatus::InExecution->value, $order->fresh()->status->value);
    }

    public function test_inspeccion_reprobada_devuelve_la_orden_a_ejecucion(): void
    {
        $report = $this->makeReport();
        $order = $this->assignOrder($report);
        $this->completeOrder($order);

        Sanctum::actingAs($this->inspector);

        // Cola de inspección con el par de fotos comparativas.
        $this->getJson('/api/v1/inspections/pending')
            ->assertStatus(200)
            ->assertJsonPath('data.0.ticket_code', $report->ticket_code)
            ->assertJsonPath('data.0.work_order.status', WorkOrderStatus::Completed->value)
            ->assertJsonPath('data.0.damage_photo_url', Storage::disk('public')->url('reports/damages/'.$report->ticket_code.'.jpg'));

        $this->postJson("/api/v1/work-orders/{$order->id}/inspections", [
            'decision' => InspectionDecision::Rework->value,
            'checklist_results' => ['superficie_uniforme' => false, 'pendiente_correcta' => 2],
            'rejection_reason' => 'El material aplicado no selló la junta con la carpeta existente.',
            'inspector_latitude' => 4.570868,
            'inspector_longitude' => -74.297353,
            'verification_photo' => $this->imageUpload('verificacion.png'),
        ])->assertStatus(201)
            ->assertJsonPath('data.report.status', ReportStatus::Rework->value)
            ->assertJsonPath('data.work_order.status', WorkOrderStatus::InExecution->value);

        $this->assertSame(WorkOrderStatus::InExecution->value, $order->fresh()->status->value);
        $this->assertSame(ReportStatus::Rework->value, $report->fresh()->status->value);

        // Y la reasignación (Alcaldía) queda habilitada sobre el reporte 'rework'.
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => $report->id,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->addDay()->toDateString(),
        ])->assertStatus(200);
    }

    public function test_inspeccion_aprobada_cierra_la_incidencia(): void
    {
        $report = $this->makeReport();
        $order = $this->assignOrder($report);
        $this->completeOrder($order);

        Sanctum::actingAs($this->inspector);

        $this->postJson("/api/v1/work-orders/{$order->id}/inspections", [
            'decision' => InspectionDecision::Approved->value,
            'checklist_results' => ['superficie_uniforme' => true, 'pendiente_correcta' => true],
            'inspector_latitude' => 4.570868,
            'inspector_longitude' => -74.297353,
            'verification_photo' => $this->imageUpload('verificacion.png'),
        ])->assertStatus(201)
            ->assertJsonPath('data.report.status', ReportStatus::Closed->value)
            ->assertJsonPath('data.inspection.decision', InspectionDecision::Approved->value);

        $this->assertSame(ReportStatus::Closed->value, $report->fresh()->status->value);
        $this->assertSame(WorkOrderStatus::Completed->value, $order->fresh()->status->value);
    }

    public function test_no_se_inspecciona_una_orden_sin_terminar(): void
    {
        $report = $this->makeReport();
        $order = $this->assignOrder($report);

        Sanctum::actingAs($this->inspector);

        $this->postJson("/api/v1/work-orders/{$order->id}/inspections", [
            'decision' => InspectionDecision::Approved->value,
            'checklist_results' => ['superficie_uniforme' => true],
            'inspector_latitude' => 4.570868,
            'inspector_longitude' => -74.297353,
            'verification_photo' => $this->imageUpload('verificacion.png'),
        ])->assertStatus(422);
    }

    // ---- fixtures ---------------------------------------------------------

    /**
     * PNG real mínimo (1x1) sin depender de la extensión GD: valida la ruta
     * `image`/`mimes`/`dimensions` contra el contenido, no contra el Content-Type.
     */
    private function imageUpload(string $name = 'evidencia.png'): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('vigia-test-', true).'.png';
        file_put_contents($path, (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function makeUser(UserRole $role): User
    {
        return User::factory()->create(['role' => $role->value]);
    }

    private function makeReport(ReportStatus $status = ReportStatus::Reported): Report
    {
        static $sequence = 1;

        $ticket = sprintf('REP-2026-%04d', $sequence++);

        // Reporte público sin usuario vinculado (ciudadano anónimo)
        return Report::create([
            'ticket_code' => $ticket,
            'user_id' => null,
            'citizen_name' => 'Ciudadano Anónimo',
            'category_id' => $this->category->id,
            'title' => 'Bache profundo en Calle 80',
            'description' => 'Bache de 40 cm junto al portal del supermercado.',
            'damage_photo_url' => "reports/damages/{$ticket}.jpg",
            'latitude' => 4.6482,
            'longitude' => -74.0476,
            'address_reference' => 'Calle 80 #12-34',
            'status' => $status->value,
        ]);
    }

    /**
     * Asigna vía API como admin y devuelve la orden persistida.
     */
    private function assignOrder(Report $report): \App\Models\WorkOrder
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => $report->id,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->toDateString(),
        ])->assertStatus(201);

        Sanctum::actingAs($this->contractor);

        return $report->workOrder()->firstOrFail();
    }

    /**
     * Ejecuta el contratista hasta 'under_inspection' en el estado del dominio.
     */
    private function completeOrder(\App\Models\WorkOrder $order): void
    {
        $order->update(['status' => WorkOrderStatus::InExecution->value]);

        Sanctum::actingAs($this->contractor);

        $this->post("/api/v1/work-orders/{$order->id}/complete", [
            'work_description' => 'Reparación ejecutada según especificación técnica.',
            'solution_photo' => $this->imageUpload('solucion.png'),
        ])->assertStatus(200);
    }
}
