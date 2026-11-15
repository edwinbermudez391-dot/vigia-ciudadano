<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\QualityInspection;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\InspectionChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionChecklistTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Report $report;
    private WorkOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();
        $department = Department::create(['name' => 'Obras', 'code' => 'CHECK', 'contact_email' => 'obras@example.test', 'phone' => '123']);
        $this->category = Category::create(['name' => 'Fuga de Agua Potable', 'department_id' => $department->id, 'sla_hours' => 12, 'description' => 'Fuga en la red de agua.']);
        $this->report = Report::create([
            'ticket_code' => 'REP-2026-0001', 'category_id' => $this->category->id,
            'title' => 'Fuga en la calle', 'description' => 'Sale agua de la tubería frente al parque.',
            'latitude' => 3.01, 'longitude' => -76.48, 'damage_photo_url' => 'test.png',
            'status' => ReportStatus::UnderInspection,
            'address_reference' => 'Frente al parque',
        ]);
        $this->order = WorkOrder::create([
            'report_id' => $this->report->id,
            'contractor_id' => User::factory()->create(['role' => UserRole::Contractor])->id,
            'scheduled_date' => now()->toDateString(), 'status' => WorkOrderStatus::Completed,
            'completion_date' => now(),
        ]);
        $this->actingAs(User::factory()->create(['role' => UserRole::QualityInspector]));
    }

    private function payload(): array
    {
        return [
            'decision' => 'approved',
            'checklist_results' => array_fill_keys(array_keys(InspectionChecklist::forCategory($this->category->name)), 'cumple'),
            'inspector_latitude' => 3.01, 'inspector_longitude' => -76.48,
            'verification_photo' => UploadedFile::fake()->createWithContent('verificacion.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')),
        ];
    }

    public function test_inspector_sees_the_checklist_for_each_catalog_category(): void
    {
        $this->assertCount(14, config('inspection.categories'));
        foreach (config('inspection.categories') as $name => $specific) {
            $this->category->update(['name' => $name]);
            $response = $this->get(route('inspector.audit', $this->order))->assertOk();
            foreach ($specific + config('inspection.common') as $label) $response->assertSee($label);
            if ($name !== 'Baches y Malla Vial') $response->assertDontSee('Uniones de la reparación cerradas');
        }
        $this->category->update(['name' => 'Nueva categoría']);
        $this->get(route('inspector.audit', $this->order))->assertOk()->assertSee('El problema descrito en el reporte fue atendido');
    }

    public function test_missing_or_foreign_checks_are_rejected_without_creating_an_inspection(): void
    {
        $payload = $this->payload();
        unset($payload['checklist_results']['fuga_detenida']);
        $this->postJson('/api/v1/work-orders/'.$this->order->id.'/inspections', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('checklist_results.fuga_detenida');
        $payload = $this->payload();
        $payload['checklist_results']['superficie_uniforme'] = 'cumple';
        $this->postJson('/api/v1/work-orders/'.$this->order->id.'/inspections', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('checklist_results');
        $this->assertDatabaseCount('quality_inspections', 0);
        $this->assertSame(ReportStatus::UnderInspection, $this->report->fresh()->status);
    }

    public function test_a_failed_check_blocks_approval_and_rework_requires_a_failed_check(): void
    {
        $payload = $this->payload();
        $payload['checklist_results']['fuga_detenida'] = 'no_cumple';
        $this->postJson('/api/v1/work-orders/'.$this->order->id.'/inspections', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('decision');
        $payload = $this->payload();
        $payload['decision'] = 'rework';
        $payload['rejection_reason'] = 'La reparación necesita una nueva revisión.';
        $this->postJson('/api/v1/work-orders/'.$this->order->id.'/inspections', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('decision');
        $this->assertDatabaseCount('quality_inspections', 0);
    }

    public function test_approval_preserves_the_actual_questions_with_the_answers(): void
    {
        $expected = InspectionChecklist::forCategory($this->category->name);
        $this->postJson('/api/v1/work-orders/'.$this->order->id.'/inspections', $this->payload())
            ->assertCreated()->assertJsonPath('data.report.status', 'closed')
            ->assertJsonPath('data.inspection.checklist_snapshot.fuga_detenida', $expected['fuga_detenida']);
        $inspection = QualityInspection::sole();
        $this->assertSame($expected, $inspection->checklist_snapshot);
        $this->category->update(['name' => 'Cableado Expuesto']);
        $this->assertSame($expected, $inspection->fresh()->checklist_snapshot);
    }
}
