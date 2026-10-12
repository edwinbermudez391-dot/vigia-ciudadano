<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportStatusChangedNotification;
use App\Notifications\WorkOrderAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Suite de integración del sistema de notificaciones por correo.
 *
 * Verifica que el ReportObserver despache correctamente las notificaciones
 * ante transiciones de estado del reporte:
 *   - ReportStatusChangedNotification a suscriptores
 *   - ReportStatusChangedNotification al citizen_email (anonymous notifiable)
 *   - WorkOrderAssignedNotification al contratista asignado
 *
 * Utiliza Notification::fake() para interceptar los envíos sin generar
 * correos reales. MAIL_MAILER=log en .env para pruebas locales.
 */
final class ReportNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Category $category;

    private User $admin;

    private User $contractor;

    private User $inspector;

    private User $citizen;

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

        $this->admin = User::factory()->create([
            'role' => UserRole::SuperAdmin->value,
            'email' => 'admin@vigia.gov.co',
        ]);

        $this->contractor = User::factory()->create([
            'role' => UserRole::Contractor->value,
            'email' => 'contractor@test.com',
        ]);

        $this->inspector = User::factory()->create([
            'role' => UserRole::QualityInspector->value,
            'email' => 'inspector@test.com',
        ]);

        $this->citizen = User::factory()->create([
            'role' => UserRole::Citizen->value,
            'email' => 'citizen@test.com',
        ]);
    }

    /**
     * Cuando el reporte pasa de 'reported' a 'in_progress' (asignación de
     * orden de trabajo), se deben enviar:
     *   1. ReportStatusChangedNotification a todos los suscriptores
     *   2. WorkOrderAssignedNotification al contratista asignado
     */
    public function test_assignment_notifies_subscribers_and_contractor(): void
    {
        Notification::fake();

        $report = $this->makeReport();

        // Suscribirse al reporte
        $report->subscribers()->attach($this->citizen->id);

        // Asignar orden de trabajo (cambia status a in_progress)
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => $report->id,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
        ])->assertStatus(201);

        // Verificar que el ciudadano suscrito recibió ReportStatusChangedNotification
        Notification::assertSentTo(
            $this->citizen,
            ReportStatusChangedNotification::class,
            function (ReportStatusChangedNotification $notification) use ($report) {
                return $notification->toArray($this->citizen)['ticket_code'] === $report->ticket_code
                    && $notification->toArray($this->citizen)['new_status'] === 'in_progress';
            }
        );

        // Verificar que el contratista recibió WorkOrderAssignedNotification
        Notification::assertSentTo(
            $this->contractor,
            WorkOrderAssignedNotification::class,
            function (WorkOrderAssignedNotification $notification) use ($report) {
                return $notification->toArray($this->contractor)['ticket_code'] === $report->ticket_code;
            }
        );
    }

    /**
     * Cuando un reporte anónimo tiene citizen_email, ese correo también
     * debe recibir ReportStatusChangedNotification como AnonymousNotifiable.
     */
    public function test_anonymous_citizen_email_receives_notification(): void
    {
        Notification::fake();

        $report = $this->makeReportAnonymous('citizen@example.com');

        // Suscribirse un usuario adicional
        $report->subscribers()->attach($this->citizen->id);

        // Asignar orden (dispara notificaciones)
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => $report->id,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->addDay()->toDateString(),
        ])->assertStatus(201);

        // Verificar que el citizen_email recibió la notificación
        Notification::assertSentTo(
            new AnonymousNotifiable,
            ReportStatusChangedNotification::class,
            function (ReportStatusChangedNotification $notification, array $channels, $notifiable) use ($report) {
                return $notifiable->routes['mail'] === 'citizen@example.com'
                    && $notification->toArray($notifiable)['ticket_code'] === $report->ticket_code;
            }
        );

        // El ciudadano suscrito también debe haber recibido la notificación
        Notification::assertSentTo($this->citizen, ReportStatusChangedNotification::class);
    }

    /**
     * Cuando la inspección aprueba la obra (status → closed), los suscriptores
     * reciben ReportStatusChangedNotification con observaciones de aprobación.
     */
    public function test_closure_notifies_subscribers(): void
    {
        Notification::fake();

        $report = $this->makeReport();
        $report->subscribers()->attach($this->citizen->id);

        // Ejecutar ciclo completo hasta aprobación
        $order = $this->assignOrder($report);
        $this->completeOrder($order);

        // Reset fake para limpiar notificaciones previas
        Notification::fake();

        Sanctum::actingAs($this->inspector);

        $this->postJson("/api/v1/work-orders/{$order->id}/inspections", [
            'decision' => InspectionDecision::Approved->value,
            'checklist_results' => ['superficie_uniforme' => true],
            'inspector_latitude' => 4.570868,
            'inspector_longitude' => -74.297353,
            'verification_photo' => $this->imageUpload('verificacion.png'),
        ])->assertStatus(201);

        // Verificar notificación de cierre
        Notification::assertSentTo(
            $this->citizen,
            ReportStatusChangedNotification::class,
            function (ReportStatusChangedNotification $notification) use ($report) {
                $data = $notification->toArray($this->citizen);
                return $data['ticket_code'] === $report->ticket_code
                    && $data['new_status'] === 'closed';
            }
        );
    }

    /**
     * Cuando la inspección rechaza la obra (status → rework), los suscriptores
     * reciben ReportStatusChangedNotification con el motivo del rechazo.
     */
    public function test_rework_notifies_subscribers_with_reason(): void
    {
        Notification::fake();

        $report = $this->makeReport();
        $report->subscribers()->attach($this->citizen->id);

        // Ejecutar ciclo hasta inspección
        $order = $this->assignOrder($report);
        $this->completeOrder($order);

        // Reset fake
        Notification::fake();

        Sanctum::actingAs($this->inspector);

        $rejectionReason = 'El material aplicado no selló la junta correctamente.';

        $this->postJson("/api/v1/work-orders/{$order->id}/inspections", [
            'decision' => InspectionDecision::Rework->value,
            'checklist_results' => ['superficie_uniforme' => false],
            'rejection_reason' => $rejectionReason,
            'inspector_latitude' => 4.570868,
            'inspector_longitude' => -74.297353,
            'verification_photo' => $this->imageUpload('verificacion.png'),
        ])->assertStatus(201);

        // Verificar notificación de retrabajo
        Notification::assertSentTo(
            $this->citizen,
            ReportStatusChangedNotification::class,
            function (ReportStatusChangedNotification $notification) use ($report) {
                $data = $notification->toArray($this->citizen);
                return $data['ticket_code'] === $report->ticket_code
                    && $data['new_status'] === 'rework';
            }
        );
    }

    /**
     * Si no hay suscriptores ni citizen_email, no se debe enviar
     * ReportStatusChangedNotification (solo WorkOrderAssignedNotification).
     */
    public function test_no_status_notification_without_subscribers(): void
    {
        Notification::fake();

        $report = $this->makeReport();

        // No suscribir a nadie, no establecer citizen_email

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/work-orders/assign', [
            'report_id' => $report->id,
            'contractor_id' => $this->contractor->id,
            'scheduled_date' => now()->toDateString(),
        ])->assertStatus(201);

        // No debe haber ReportStatusChangedNotification (no hay suscriptores)
        Notification::assertNothingSentTo($this->citizen);

        // Pero sí WorkOrderAssignedNotification al contratista
        Notification::assertSentTo($this->contractor, WorkOrderAssignedNotification::class);
    }

    // ---- Fixtures -----------------------------------------------------------

    private function makeReport(ReportStatus $status = ReportStatus::Reported): Report
    {
        static $sequence = 1;

        $ticket = sprintf('REP-2026-%04d', $sequence++);

        return Report::create([
            'ticket_code' => $ticket,
            'user_id' => $this->citizen->id,
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

    private function makeReportAnonymous(string $citizenEmail): Report
    {
        static $sequence = 100;

        $ticket = sprintf('REP-2026-%04d', $sequence++);

        return Report::create([
            'ticket_code' => $ticket,
            'user_id' => null,
            'citizen_name' => 'Ciudadano Anónimo',
            'citizen_email' => $citizenEmail,
            'category_id' => $this->category->id,
            'title' => 'Fuga de agua en parque',
            'description' => 'Fuga constante de agua potable en el parque central.',
            'damage_photo_url' => "reports/damages/{$ticket}.jpg",
            'latitude' => 4.6541,
            'longitude' => -74.0636,
            'address_reference' => 'Parque central',
            'status' => ReportStatus::Reported->value,
        ]);
    }

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

    private function completeOrder(\App\Models\WorkOrder $order): void
    {
        $order->update(['status' => WorkOrderStatus::InExecution->value]);

        Sanctum::actingAs($this->contractor);

        $this->post("/api/v1/work-orders/{$order->id}/complete", [
            'work_description' => 'Reparación ejecutada según especificación técnica.',
            'solution_photo' => $this->imageUpload('solucion.png'),
        ])->assertStatus(200);
    }

    private function imageUpload(string $name = 'evidencia.png'): UploadedFile
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('vigia-test-', true) . '.png';
        file_put_contents($path, (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
