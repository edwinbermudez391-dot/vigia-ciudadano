<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class InstitutionalUiTest extends TestCase
{
    use RefreshDatabase;

    private function report(int $number, ReportStatus $status = ReportStatus::Reported): Report
    {
        $department = Department::firstOrCreate(['code' => 'UI'], [
            'name' => 'Infraestructura', 'contact_email' => 'infra@example.test', 'phone' => '123',
        ]);
        $category = Category::firstOrCreate(['name' => 'Baches', 'department_id' => $department->id], [
            'sla_hours' => 72, 'description' => 'Daños en vías.',
        ]);

        return Report::create([
            'ticket_code' => sprintf('REP-2026-%04d', $number),
            'category_id' => $category->id,
            'title' => 'Daño de prueba '.$number,
            'description' => 'Descripción del daño de prueba para revisión.',
            'damage_photo_url' => 'reports/test.png',
            'latitude' => 4.6, 'longitude' => -74.1,
            'address_reference' => 'Frente al parque central',
            'status' => $status,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_dashboard_has_clear_metrics_navigation_and_accessible_table(): void
    {
        $this->report(1);
        $this->report(2, ReportStatus::Closed);

        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSee('Total de reportes')->assertSee('Reportes pendientes')
            ->assertSee('Trabajos en proceso')->assertSee('Reportes solucionados')
            ->assertSee('Saltar al contenido principal')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Ver detalles')->assertSee('Confirmar asignación')
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 2 && $stats['pending'] === 1 && $stats['closed'] === 1);
    }

    public function test_dashboard_uses_fcfs_with_stable_ties_across_filtered_pages(): void
    {
        $reports = collect(range(1, 12))->map(fn ($number) => $this->report($number));
        foreach ($reports as $index => $report) {
            $report->forceFill(['created_at' => now()->startOfDay()->subDays($index === 0 ? 1 : ($index < 3 ? 3 : 2))])->save();
        }
        $expected = $reports->slice(1)->pluck('id')->push($reports->first()->id)->values();
        $this->actingAs($this->admin())->get('/admin?q=prueba&status=reported')
            ->assertOk()
            ->assertSee('Orden de llegada:')
            ->assertViewHas('recent', fn ($page) => $page->pluck('id')->all() === $expected->take(10)->all());
        $this->get('/admin?q=prueba&status=reported&page=2')->assertOk()
            ->assertViewHas('recent', fn ($page) => $page->pluck('id')->all() === $expected->slice(10)->values()->all());
    }

    public function test_dashboard_search_and_status_filter_apply_together(): void
    {
        $this->report(1);
        $match = $this->report(2, ReportStatus::Rework);
        $this->report(3, ReportStatus::Closed);

        $this->actingAs($this->admin())->get('/admin?q=REP-2026-0002&status=rework')
            ->assertOk()
            ->assertViewHas('recent', fn ($reports) => $reports->total() === 1 && $reports->first()->id === $match->id)
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 3);
    }

    public function test_pagination_keeps_filters_and_empty_results_are_explained(): void
    {
        foreach (range(1, 11) as $number) {
            $this->report($number);
        }

        $this->actingAs($this->admin())->get('/admin?q=prueba&status=reported&page=2')
            ->assertOk()
            ->assertViewHas('recent', fn ($reports) => $reports->count() === 1 && $reports->total() === 11)
            ->assertSee('q=prueba', false)->assertSee('status=reported', false);

        $this->get('/admin?q=NoExiste')->assertOk()->assertSee('No hay reportes para mostrar.');
    }

    public function test_dashboard_rejects_invalid_filter_values(): void
    {
        $this->actingAs($this->admin())->from('/admin')->get('/admin?status=unknown')
            ->assertRedirect('/admin')->assertSessionHasErrors('status');
    }

    public function test_role_navigation_does_not_expose_administrative_links(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Contractor]))
            ->get('/ordenes')->assertOk()->assertSee('Órdenes de trabajo')
            ->assertDontSee('href="'.route('admin.contractors').'"', false);
        $this->get('/admin')->assertForbidden();
    }

    public function test_public_pages_and_api_authentication_remain_distinct(): void
    {
        $report = $this->report(1);
        $this->get('/')->assertOk()->assertSee('Reportar un problema')->assertSee('Reportes en este dispositivo');
        $this->get('/mapa')->assertOk()->assertSee('Mapa de reportes');
        $this->get('/reportes/crear')->assertOk()->assertSee('1. ¿Qué pasó?');
        $this->get('/reportes/'.$report->ticket_code)->assertOk()->assertSee('Así va tu reporte');
        $this->get('/reportes/exito/'.$report->ticket_code)->assertOk()->assertSee('Consultar mi reporte');
        $this->postJson('/api/v1/reports', [])->assertUnauthorized();
        $this->getJson('/api/v1/reports/'.$report->ticket_code)->assertUnauthorized();
    }

    public function test_public_pages_share_compact_navigation_and_clear_actions(): void
    {
        foreach (['/', '/mapa', '/reportes/crear'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('data-public-menu', false)
                ->assertSee('aria-controls="public-navigation"', false)
                ->assertSee('id="public-navigation"', false)
                ->assertSee('Ingresar')
                ->assertDontSee('Acceso institucional');
        }

        $this->get('/')->assertOk()
            ->assertSee('Mejoremos nuestra comunidad')
            ->assertSee('Reporta daños en tu barrio y consulta cómo va su solución.')
            ->assertDontSee('data-report-lookup', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('Reportes y seguimiento abiertos a la ciudadanía.');
    }

    public function test_report_pages_have_explicit_desktop_layouts_without_losing_form_fields(): void
    {
        $report = $this->report(1);

        $this->get('/')->assertOk()->assertSee('class="report-steps"', false);
        $this->get('/reportes/crear')->assertOk()
            ->assertSee('citizen-report-page')->assertSee('citizen-report-form')
            ->assertSee('data-report-step="0"', false)->assertSee('data-report-step="1"', false)
            ->assertSee('data-report-step="2"', false)->assertSee('Anterior')->assertSee('Continuar')
            ->assertSee('name="damage_photo"', false)
            ->assertSee('name="latitude"', false)->assertSee('name="longitude"', false)
            ->assertSee('name="citizen_email"', false);

        $this->get('/reportes/'.$report->ticket_code)->assertOk()
            ->assertSee('citizen-detail-layout')->assertSee('Seguimiento de la solución');

        $this->actingAs(User::factory()->create(['role' => UserRole::Contractor]))
            ->get('/ordenes')->assertOk()->assertSee('class="work-grid"', false);
        $this->actingAs(User::factory()->create(['role' => UserRole::QualityInspector]))
            ->get('/inspector/inspecciones')->assertOk()->assertSee('class="work-grid"', false);
    }

    public function test_statistics_have_explicit_filters_and_non_graphical_data(): void
    {
        $this->report(1);
        $this->actingAs($this->admin())->get('/admin/analitica?period=month')
            ->assertOk()->assertSee('Estadísticas del municipio')
            ->assertSee('Aplicar filtros')->assertSee('Consultar los datos de las gráficas en tablas')
            ->assertSee('Reportes por categoría')->assertSee('Baches');
    }

    public function test_web_assignment_still_redirects_with_confirmation_and_reopens_on_errors(): void
    {
        Notification::fake();
        $report = $this->report(1);
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $this->actingAs($this->admin())->from('/admin')->post(route('admin.assign', $report), [
            'report_id' => $report->id,
            'contractor_id' => $contractor->id,
            'scheduled_date' => now()->toDateString(),
        ])->assertRedirect('/admin')->assertSessionHas('status');
        $this->assertSame(ReportStatus::InProgress, $report->fresh()->status);

        $this->from('/admin')->post(route('admin.assign', $report), [
            'report_id' => $report->id, 'contractor_id' => '', 'scheduled_date' => '',
        ])->assertRedirect('/admin')->assertSessionHasErrors(['contractor_id', 'scheduled_date']);
        $this->get('/admin')->assertOk()->assertSee('Revisa los siguientes datos antes de continuar:')
            ->assertSee('value="'.$report->id.'"', false);
    }

    public function test_inspection_screen_preserves_checklist_evidence_and_gps_fields(): void
    {
        $report = $this->report(1, ReportStatus::UnderInspection);
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $order = WorkOrder::create([
            'report_id' => $report->id, 'contractor_id' => $contractor->id,
            'scheduled_date' => now()->toDateString(), 'completion_date' => now(),
            'status' => WorkOrderStatus::Completed,
            'solution_photo_url' => 'reports/solution.png',
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::QualityInspector]))
            ->get(route('inspector.audit', $order))->assertOk()
            ->assertSee('audit-workspace')->assertSee('audit-form')
            ->assertSee('Lista de verificación técnica')
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('name="verification_photo"', false)
            ->assertSee('data-inspector-map', false)
            ->assertSee('name="inspector_latitude" type="hidden"', false)
            ->assertSee('name="inspector_longitude" type="hidden"', false)
            ->assertSee('name="decision"', false)
            ->assertSee('name="rejection_reason"', false);
    }

    public function test_institutional_sidebar_has_labeled_controls_and_current_section(): void
    {
        User::factory()->create(['role' => UserRole::Contractor]);
        $this->actingAs($this->admin())->get('/admin/contratistas')->assertOk()
            ->assertSee('id="primary-sidebar"', false)
            ->assertSee('data-sidebar-collapse', false)
            ->assertSee('data-sidebar-mobile', false)
            ->assertSee('aria-controls="primary-sidebar"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Buscar contratista');
    }

    public function test_registration_errors_are_associated_with_visible_field_labels(): void
    {
        $this->actingAs($this->admin())->from('/admin/contratistas')->post('/admin/contratistas', [
            'name' => 'Cuadrilla de prueba', 'email' => 'correo-invalido',
        ])->assertRedirect('/admin/contratistas')->assertSessionHasErrors('email');

        $this->get('/admin/contratistas')->assertOk()
            ->assertSee('for="rc-email"', false)
            ->assertSee('rc-email-error', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('Acceso al sistema');
    }

    public function test_pagination_uses_spanish_labels_and_accessible_navigation(): void
    {
        foreach (range(1, 11) as $number) {
            $this->report($number);
        }
        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertSee('aria-label="Paginación"', false)
            ->assertSee('Mostrando')->assertSee('Página 1 de 2')->assertSee('Siguiente')
            ->assertDontSee('Showing');
    }

    public function test_sla_display_does_not_mutate_the_original_report_date(): void
    {
        $report = $this->report(1);
        $date = $report->created_at->toDateTimeString();

        $this->get('/reportes/'.$report->ticket_code)->assertOk()
            ->assertViewHas('report', fn ($rendered) => $rendered->created_at->toDateTimeString() === $date);
    }
}
