<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\QualityInspection;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CitizenExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        $department = Department::create(['name' => 'Infraestructura', 'code' => 'CIT', 'contact_email' => 'infra@example.test', 'phone' => '123']);

        return Category::create(['name' => 'Baches y Malla Vial', 'department_id' => $department->id, 'sla_hours' => 72, 'description' => 'Daños en la vía.']);
    }

    private function report(): Report
    {
        return Report::create([
            'ticket_code' => 'REP-2026-0001', 'category_id' => $this->category()->id,
            'title' => 'Hueco frente al parque', 'description' => 'Hay un hueco grande que dificulta el paso.',
            'damage_photo_url' => 'reports/test.png', 'latitude' => 3.0095, 'longitude' => -76.4849,
            'address_reference' => 'Frente al parque', 'status' => ReportStatus::Reported,
            'citizen_name' => 'NOMBRE_PRIVADO', 'citizen_email' => 'correo-privado@example.test', 'citizen_phone' => 'TELEFONO_PRIVADO',
        ]);
    }

    public function test_home_prioritizes_reporting_and_inline_lookup_without_modals(): void
    {
        $this->get('/')->assertOk()->assertSee('Mejoremos nuestra comunidad')
            ->assertSee('Reportar un problema')->assertSee('Reportes en este dispositivo')
            ->assertDontSee('data-report-lookup', false)->assertDontSee('Buscar reporte')
            ->assertSee('citizen-extras')->assertSee('data-text-size', false)
            ->assertDontSee('id="ticket-modal"', false);
    }

    public function test_form_keeps_existing_categories_fields_and_non_gps_location_options(): void
    {
        $category = $this->category();
        $this->get('/reportes/crear')->assertOk()
            ->assertSee('Hueco en la calle')->assertSee('value="'.$category->id.'"', false)
            ->assertSee('data-category-name="Baches y Malla Vial"', false)
            ->assertSee('data-report-step="0"', false)->assertSee('data-report-step="1"', false)->assertSee('data-report-step="2"', false)
            ->assertSee('Usar mi ubicación')->assertSee('Mapa para señalar el problema')
            ->assertDontSee('id="address-search"', false)
            ->assertSee('data-use-map-center', false)->assertSee('data-open-map', false)
            ->assertSee('Agregar foto')->assertSee('data-photo-camera', false)
            ->assertSee('name="latitude"', false)->assertSee('name="longitude"', false)
            ->assertSee('maxlength="1000"', false)
            ->assertDontSee('category-modal-backdrop');
    }

    public function test_public_submission_creates_a_real_report_with_manual_coordinates_and_no_contact_data(): void
    {
        Storage::fake('public');
        $category = $this->category();
        $reportData = [
            'category_id' => $category->id, 'title' => 'Hueco en el barrio',
            'description' => 'Hay un hueco grande frente al parque del barrio.',
            'latitude' => '3.0095', 'longitude' => '-76.4849',
            'address_reference' => 'Frente al parque',
            'damage_photo' => UploadedFile::fake()->createWithContent('problema.png', (string) base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
            )),
        ];
        $response = $this->post('/reportes', $reportData);
        $response->assertSessionHasNoErrors();
        $report = Report::sole();
        $response->assertRedirect(route('reports.success', $report->ticket_code));
        $this->assertNull($report->user_id);
        $this->assertNull($report->citizen_email);
        $this->assertSame(ReportStatus::Reported, $report->status);
        Storage::disk('public')->assertExists($report->damage_photo_url);
        $this->get(route('reports.success', $report->ticket_code))->assertOk()
            ->assertSee('¡Tu reporte fue recibido!')->assertSee($report->ticket_code)
            ->assertSee('id="ticket-code"', false)
            ->assertDontSee('Copiar número')->assertDontSee('Imprimir comprobante')
            ->assertDontSee('Compartir comprobante')
            ->assertSee('data-device-receipt', false);
        $this->get(route('reports.success', $report->ticket_code))->assertOk()
            ->assertDontSee('data-device-receipt', false);

        $cookie = $response->getCookie('vigia_device_reports');
        $this->assertNotNull($cookie);
        $this->assertGreaterThan(now()->addDays(30)->timestamp, $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('/reportes', $cookie->getPath());

        $this->flushSession();
        $this->get(route('reports.device'))->assertOk()->assertDontSee($report->ticket_code);

        $this->withCookie($cookie->getName(), $cookie->getValue());
        $list = $this->get(route('reports.device'))->assertOk()->assertSee($report->ticket_code);
        $this->assertStringContainsString('no-store', $list->headers->get('Cache-Control'));

        $this->post('/reportes', $reportData)->assertSessionHasNoErrors()->assertCookie($cookie->getName(), $cookie->getValue());
        $second = Report::latest('id')->firstOrFail();
        $this->assertNotSame($report->ticket_code, $second->ticket_code);
        $this->get(route('reports.device'))->assertOk()->assertSeeInOrder([$second->ticket_code, $report->ticket_code]);
    }

    public function test_device_reports_page_is_public_and_does_not_list_other_peoples_reports(): void
    {
        $report = $this->report();
        $this->get('/reportes/mis-reportes')->assertOk()
            ->assertSee('Mis reportes en este dispositivo')
            ->assertSee('data-device-reports', false)
            ->assertDontSee($report->ticket_code);
        $this->get(route('reports.success', $report->ticket_code))->assertOk()
            ->assertDontSee('data-device-receipt', false);
        $this->get(route('reports.device', ['radicado' => strtolower($report->ticket_code)]))->assertOk()
            ->assertSee(route('reports.show', $report->ticket_code), false)
            ->assertSee('Abrir mi reporte');
        $this->get('/')->assertOk()->assertSee(route('reports.device'), false);
    }

    public function test_invalid_submission_preserves_values_and_does_not_claim_success(): void
    {
        $category = $this->category();
        $this->from('/reportes/crear')->post('/reportes', [
            'category_id' => $category->id, 'title' => 'Hueco frente al parque',
            'description' => 'Hay un hueco que dificulta el paso.',
        ])->assertRedirect('/reportes/crear')->assertSessionHasErrors(['latitude', 'longitude']);
        $this->assertDatabaseCount('reports', 0);
        $this->get('/reportes/crear')->assertOk()->assertSee('Revisa la información que ingresaste')
            ->assertSee('Hay un hueco que dificulta el paso.')->assertSee('data-field-error="damage_photo"', false)
            ->assertDontSee('¡Tu reporte fue recibido!');
    }

    public function test_citizen_cannot_report_without_a_photo(): void
    {
        Storage::fake('public');
        $category = $this->category();
        $response = $this->post('/reportes', [
            'category_id' => $category->id,
            'description' => 'Hay un hueco grande que dificulta el paso.',
            'latitude' => 3.0095, 'longitude' => -76.4849,
            'location_label' => 'Parque principal', 'address_reference' => 'Frente a la farmacia',
        ]);
        $response->assertSessionHasErrors('damage_photo');
        $this->assertDatabaseCount('reports', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_timeline_marks_execution_only_after_the_crew_starts_work(): void
    {
        $report = $this->report();
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $order = WorkOrder::create([
            'report_id' => $report->id, 'contractor_id' => $contractor->id,
            'scheduled_date' => now()->toDateString(), 'status' => WorkOrderStatus::Assigned,
        ]);
        $this->actingAs($contractor)->get(route('reports.show', $report->ticket_code))
            ->assertOk()->assertDontSee('Obra en ejecución')
            ->assertSee('Pendiente que el contratista inicie');
        $this->patch(route('contractor.startWork', $order))->assertRedirect();
        $this->get(route('reports.show', $report->ticket_code))->assertOk()
            ->assertSee('Obra en ejecución')->assertSee('aria-current="step"', false);
        $order->update(['status' => WorkOrderStatus::Completed, 'completion_date' => now()]);
        $this->get(route('reports.show', $report->ticket_code))->assertOk()
            ->assertSee('Obra ejecutada por el contratista')->assertDontSee('Obra en ejecución');
        $order->update(['status' => WorkOrderStatus::InExecution]);
        $this->get(route('reports.show', $report->ticket_code))->assertOk()
            ->assertSee('Obra en ejecución')->assertSee('aria-current="step"', false);
    }

    public function test_report_photos_use_the_current_site_in_public_and_staff_views(): void
    {
        $report = $this->report();
        $path = '/reportes/'.$report->ticket_code;
        $this->get('https://pruebas.example.test'.$path)->assertOk()
            ->assertSee('src="/storage/reports/test.png"', false)
            ->assertDontSee('http://localhost/storage', false);
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($admin)->get('https://pruebas.example.test'.$path)->assertOk()
            ->assertSee('src="/storage/reports/test.png"', false);
        $this->get('https://pruebas.example.test/api/v1/reports/map-data')->assertOk()
            ->assertJsonPath('data.0.photo_url', 'https://pruebas.example.test/storage/reports/test.png');
    }

    public function test_public_detail_does_not_render_names_contact_data_or_internal_notes(): void
    {
        $report = $this->report();
        $contractor = User::factory()->create(['name' => 'CONTRATISTA_PRIVADO', 'role' => UserRole::Contractor]);
        $inspector = User::factory()->create(['name' => 'INSPECTOR_PRIVADO', 'role' => UserRole::QualityInspector]);
        $order = WorkOrder::create([
            'report_id' => $report->id, 'contractor_id' => $contractor->id,
            'scheduled_date' => now()->toDateString(), 'completion_date' => now(), 'status' => WorkOrderStatus::Completed,
            'work_description' => 'NOTA_INTERNA_TRABAJO', 'solution_photo_url' => 'solution.png',
        ]);
        QualityInspection::create([
            'work_order_id' => $order->id, 'inspector_id' => $inspector->id, 'decision' => InspectionDecision::Rework,
            'rejection_reason' => 'NOTA_INTERNA_INSPECCION', 'checklist_results' => [],
            'verification_photo_url' => 'inspection.png', 'inspector_latitude' => 3, 'inspector_longitude' => -76, 'inspected_at' => now(),
        ]);
        $report->update(['status' => ReportStatus::Rework]);
        $this->get('/reportes/'.$report->ticket_code)->assertOk()
            ->assertSee('En reparación')->assertSee('Correcciones solicitadas')->assertSee('Frente al parque')
            ->assertDontSee('NOMBRE_PRIVADO')->assertDontSee('correo-privado@example.test')->assertDontSee('TELEFONO_PRIVADO')
            ->assertDontSee('CONTRATISTA_PRIVADO')->assertDontSee('INSPECTOR_PRIVADO')
            ->assertDontSee('NOTA_INTERNA_TRABAJO')->assertDontSee('NOTA_INTERNA_INSPECCION');
    }

    public function test_public_state_labels_are_presentational_and_do_not_mutate_domain_states(): void
    {
        $report = $this->report();
        foreach ([ReportStatus::Reported->value => 'Recibido', ReportStatus::InProgress->value => 'En reparación',
            ReportStatus::UnderInspection->value => 'En revisión', ReportStatus::Rework->value => 'En reparación', ReportStatus::Closed->value => 'Solucionado'] as $state => $label) {
            $report->update(['status' => $state]);
            $this->get('/reportes/'.$report->ticket_code)->assertOk()->assertSee($label);
            $this->assertSame($state, $report->fresh()->status->value);
        }
    }

    public function test_unknown_radicated_number_returns_friendly_real_404_without_confirmation(): void
    {
        $this->get('/reportes/REP-2026-99999')->assertNotFound()
            ->assertSee('No encontramos ese número')->assertSee('Buscar reporte')
            ->assertDontSee('¡Tu reporte fue recibido!');
        $this->get('/reportes/exito/REP-2026-99999')->assertNotFound()->assertDontSee('¡Tu reporte fue recibido!');
    }

    public function test_staff_detail_keeps_its_operational_controls_without_citizen_sizing(): void
    {
        $report = $this->report();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($admin)->get('/reportes/'.$report->ticket_code)->assertOk()
            ->assertSee('Detalle del reporte')->assertSee('data-assign-open', false)
            ->assertDontSee('citizen-large-text')->assertDontSee('data-text-size', false);
    }
}
