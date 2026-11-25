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

    public function test_dashboard_has_clear_metrics_and_navigation(): void
    {
        $this->report(1);
        $this->report(2, ReportStatus::Closed);

        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSee('Total de reportes')->assertSee('Reportes pendientes')
            ->assertSee('Trabajos en proceso')->assertSee('Reportes solucionados')
            ->assertSee('Saltar al contenido principal')
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Reportes por estado')
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 2 && $stats['pending'] === 1 && $stats['closed'] === 1);
    }

    public function test_dashboard_uses_fcfs_with_stable_ties_across_filtered_pages(): void
    {
        $reports = collect(range(1, 12))->map(fn ($number) => $this->report($number));
        foreach ($reports as $index => $report) {
            $report->forceFill(['created_at' => now()->startOfDay()->subDays($index === 0 ? 1 : ($index < 3 ? 3 : 2))])->save();
        }
        $expected = $reports->slice(1)->pluck('id')->push($reports->first()->id)->values();
        $this->actingAs($this->admin())->get('/admin/reportes?q=prueba&status=reported')
            ->assertOk()
            ->assertSee('Orden de llegada:')
            ->assertViewHas('reportGroups', fn ($groups) => $groups['reported']->pluck('id')->all() === $expected->take(10)->all());
        $this->get('/admin/reportes?q=prueba&status=reported&page=2')->assertOk()
            ->assertViewHas('reportGroups', fn ($groups) => $groups['reported']->pluck('id')->all() === $expected->slice(10)->values()->all());
    }

    public function test_dashboard_search_and_status_filter_apply_together(): void
    {
        $this->report(1);
        $match = $this->report(2, ReportStatus::Rework);
        $this->report(3, ReportStatus::Closed);

        $this->actingAs($this->admin())->get('/admin/reportes?q=REP-2026-0002&status=rework')
            ->assertOk()
            ->assertViewHas('reportGroups', fn ($groups) => array_keys($groups) === ['rework'] && $groups['rework']->total() === 1 && $groups['rework']->first()->id === $match->id)
            ->assertViewHas('totalReports', 1);
        $this->get('/admin')->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 3);
    }

    public function test_pagination_keeps_filters_and_empty_results_are_explained(): void
    {
        foreach (range(1, 11) as $number) {
            $this->report($number);
        }

        $this->actingAs($this->admin())->get('/admin/reportes?q=prueba&status=reported&page=2')
            ->assertOk()
            ->assertViewHas('reportGroups', fn ($groups) => $groups['reported']->count() === 1 && $groups['reported']->total() === 11)
            ->assertSee('q=prueba', false)->assertSee('status=reported', false);

        $this->get('/admin/reportes?q=NoExiste')->assertOk()->assertSee('No hay reportes para mostrar.');
    }

    public function test_dashboard_separates_statuses_and_paginates_each_group_independently(): void
    {
        $this->report(30, ReportStatus::Closed);
        $this->report(31, ReportStatus::InProgress);
        $this->report(32, ReportStatus::UnderInspection);
        $this->report(33, ReportStatus::Rework);
        foreach (range(1, 11) as $number) {
            $this->report($number);
        }

        $this->actingAs($this->admin())->get('/admin/reportes?q=prueba&grupo=reported&page_reported=2')
            ->assertOk()
            ->assertSeeInOrder([
                'id="reportes-reported"',
                'id="reportes-in_progress"',
                'id="reportes-under_inspection"',
                'id="reportes-rework"',
                'id="reportes-closed"',
            ], false)
            ->assertViewHas('totalReports', 15)
            ->assertViewHas('reportGroups', fn ($groups) =>
                $groups['reported']->pluck('ticket_code')->all() === ['REP-2026-0011']
                && $groups['closed']->currentPage() === 1
                && $groups['closed']->first()->ticket_code === 'REP-2026-0030')
            ->assertSee('q=prueba', false)
            ->assertSee('grupo=reported', false)
            ->assertSee('page_reported=1#reportes-reported', false)
            ->assertViewHas('openStatus', 'reported')
            ->assertSee('aria-label="Asignar REP-2026-0033"', false)
            ->assertDontSee('aria-label="Asignar REP-2026-0030"', false);
    }

    public function test_reports_open_the_first_status_by_default(): void
    {
        $this->report(1);
        $this->report(2, ReportStatus::Closed);

        $general = $this->actingAs($this->admin())->get('/admin/reportes')->assertOk()
            ->assertSee('Reportes ciudadanos')->assertSee('Ver detalles')->assertSee('Confirmar asignación')
            ->assertDontSee('Total de reportes')->assertDontSee('Seguimiento operativo')
            ->assertViewHas('openStatus', 'reported');
        $this->assertSame(2, substr_count($general->getContent(), 'name="report-status-groups"'));
        $this->assertMatchesRegularExpression('/<details[^>]*id="reportes-reported"[^>]*\sopen(?:\s|>)/', $general->getContent());

        $filtered = $this->get('/admin/reportes?status=closed')->assertOk();
        $this->assertMatchesRegularExpression('/<details[^>]*id="reportes-closed"[^>]*\sopen(?:\s|>)/', $filtered->getContent());
        $this->get('/admin?status=closed')->assertRedirect('/admin/reportes?status=closed');
    }

    public function test_dashboard_rejects_invalid_filter_values(): void
    {
        $this->actingAs($this->admin())->from('/admin/reportes')->get('/admin/reportes?status=unknown')
            ->assertRedirect('/admin/reportes')->assertSessionHasErrors('status');
    }

    public function test_role_navigation_does_not_expose_administrative_links(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Contractor]))
            ->get('/ordenes')->assertOk()->assertSee('Órdenes de trabajo')
            ->assertDontSee('href="'.route('admin.contractors').'"', false);
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/reportes')->assertForbidden();
    }

    public function test_public_pages_and_api_authentication_remain_distinct(): void
    {
        $report = $this->report(1);
        $this->get('/')->assertOk()->assertSee('Reportar un problema')->assertSee('Reportes en este dispositivo');
        $this->get('/mapa')->assertOk()->assertSee('Mapa de reportes');
        $this->get('/reportes/crear')->assertOk()->assertSee('1. ¿Qué pasó?');
        $this->get('/reportes/'.$report->ticket_code)->assertOk()->assertSee('Así va tu reporte');
        $this->get('/reportes/exito/'.$report->ticket_code)->assertOk()
            ->assertSee('Volver al inicio')->assertSee('Mis reportes en este dispositivo')
            ->assertDontSee('Consultar mi reporte')->assertDontSee('Reportar otro problema');
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
                ->assertDontSee('public-login', false)
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
            ->get('/ordenes')->assertOk()->assertSee('No tienes órdenes asignadas');
        $this->actingAs(User::factory()->create(['role' => UserRole::QualityInspector]))
            ->get('/inspector/inspecciones')->assertOk()->assertSee('class="work-grid"', false);
    }

    public function test_contractor_orders_are_grouped_and_new_assignments_follow_arrival_order(): void
    {
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $other = User::factory()->create(['role' => UserRole::Contractor]);
        $reassigned = WorkOrder::create([
            'report_id' => $this->report(1)->id, 'contractor_id' => $contractor->id,
            'scheduled_date' => now()->toDateString(), 'status' => WorkOrderStatus::Assigned,
            'assigned_at' => now(),
        ]);
        $reassigned->forceFill(['created_at' => now()->subDays(7)])->save();
        $legacy = WorkOrder::create([
            'report_id' => $this->report(2)->id, 'contractor_id' => $contractor->id,
            'scheduled_date' => now()->addDays(3)->toDateString(), 'status' => WorkOrderStatus::Assigned,
        ]);
        $legacy->forceFill(['created_at' => now()->subDay()])->save();
        $inExecution = WorkOrder::create([
            'report_id' => $this->report(3)->id, 'contractor_id' => $contractor->id,
            'scheduled_date' => now()->toDateString(), 'status' => WorkOrderStatus::InExecution,
        ]);
        WorkOrder::create([
            'report_id' => $this->report(4)->id, 'contractor_id' => $other->id,
            'scheduled_date' => now()->toDateString(), 'status' => WorkOrderStatus::Assigned,
        ]);

        $this->actingAs($contractor)->get('/ordenes')->assertOk()
            ->assertSeeInOrder(['data-list-search="work-list"', 'data-status-switcher', 'id="ordenes-assigned"'], false)
            ->assertViewHas('openStatus', 'assigned')
            ->assertViewHas('groups', fn ($groups) => array_keys($groups) === ['assigned', 'in_execution']
                && $groups['assigned']->pluck('id')->all() === [$legacy->id, $reassigned->id]
                && $groups['in_execution']->pluck('id')->all() === [$inExecution->id])
            ->assertSee('class="work-grid"', false)
            ->assertDontSee('REP-2026-0004');
        $this->get('/ordenes?grupo=in_execution')->assertOk()->assertViewHas('openStatus', 'in_execution');
    }

    public function test_statistics_have_explicit_filters_and_non_graphical_data(): void
    {
        $this->report(1);
        $this->actingAs($this->admin())->get('/admin/analitica?period=month')
            ->assertOk()->assertSee('Estadísticas del municipio')
            ->assertSee('Aplicar filtros')->assertSee('Consultar los datos de las gráficas en tablas')
            ->assertSee('Reportes por categoría')->assertSee('Baches');
    }

    public function test_inspector_filters_by_crew_across_pending_and_closed_reports(): void
    {
        $inspector = User::factory()->create(['role' => UserRole::QualityInspector]);
        $firstCrew = User::factory()->create(['role' => UserRole::Contractor, 'name' => 'Cuadrilla Norte']);
        $otherCrew = User::factory()->create(['role' => UserRole::Contractor, 'name' => 'Cuadrilla Sur']);

        foreach ([1 => $firstCrew, 2 => $otherCrew, 3 => $firstCrew, 4 => $otherCrew, 5 => $firstCrew] as $number => $crew) {
            $report = $this->report($number, $number < 3 ? ReportStatus::UnderInspection : ReportStatus::Closed);
            WorkOrder::create([
                'report_id' => $report->id, 'contractor_id' => $crew->id,
                'scheduled_date' => now()->toDateString(), 'status' => WorkOrderStatus::Completed,
            ]);
        }

        $this->actingAs($inspector)->get(route('inspector.queue', ['cuadrilla' => $firstCrew->id]))
            ->assertOk()->assertSee('Cuadrilla Norte')->assertSee('Cuadrilla Sur')
            ->assertSee('Todas las cuadrillas')
            ->assertSee(route('inspector.queue', ['cuadrilla' => $otherCrew->id]))
            ->assertDontSee('aria-label="Estados de inspección"', false)
            ->assertSeeInOrder(['Inspecciones pendientes', 'REP-2026-0001', 'Revisar obra', 'Reportes cerrados', 'REP-2026-0005', 'Ver detalle', 'REP-2026-0003'])
            ->assertDontSee('REP-2026-0002')->assertDontSee('REP-2026-0004');

        $nextClosedPage = route('inspector.queue', ['cuadrilla' => $firstCrew->id, 'per_page' => 1, 'page_cerrados' => 2]);
        $this->get(route('inspector.queue', ['cuadrilla' => $firstCrew->id, 'per_page' => 1]))
            ->assertOk()->assertSee('REP-2026-0001')->assertSee('REP-2026-0005')
            ->assertDontSee('REP-2026-0003')->assertSee($nextClosedPage.'#cerrados');
        $this->get($nextClosedPage)
            ->assertOk()->assertSee('REP-2026-0001')->assertSee('REP-2026-0003')->assertDontSee('REP-2026-0005');
    }

    public function test_web_assignment_still_redirects_with_confirmation_and_reopens_on_errors(): void
    {
        Notification::fake();
        $report = $this->report(1);
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $this->actingAs($this->admin())->from('/admin/reportes')->post(route('admin.assign', $report), [
            'report_id' => $report->id,
            'contractor_id' => $contractor->id,
            'scheduled_date' => now()->toDateString(),
        ])->assertRedirect('/admin/reportes')->assertSessionHas('status');
        $this->assertSame(ReportStatus::InProgress, $report->fresh()->status);

        $this->from('/admin/reportes')->post(route('admin.assign', $report), [
            'report_id' => $report->id, 'contractor_id' => '', 'scheduled_date' => '',
        ])->assertRedirect('/admin/reportes')->assertSessionHasErrors(['contractor_id', 'scheduled_date']);
        $this->get('/admin/reportes')->assertOk()->assertSee('Revisa los siguientes datos antes de continuar:')
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

    public function test_admin_edits_contractor_details_without_changing_credentials(): void
    {
        $department = Department::create([
            'code' => 'EDIT',
            'name' => 'Dependencia de prueba',
            'contact_email' => 'edit@example.test',
            'phone' => '123',
            'is_active' => false,
        ]);
        $contractor = User::factory()->create([
            'role' => UserRole::Contractor,
            'department_id' => $department->id,
        ]);
        $token = $contractor->createToken('prueba')->accessToken;
        $editUrl = route('admin.contractors.edit', $contractor);

        $this->actingAs($this->admin())->get(route('admin.contractors'))->assertOk()->assertSee($editUrl);
        $this->get($editUrl)->assertOk()->assertSee('Nueva contraseña')
            ->assertSee($contractor->email)->assertDontSee($contractor->password);

        $this->patch(route('admin.contractors.update', $contractor), [
            'name' => 'Cuadrilla actualizada',
            'email' => $contractor->email,
            'phone' => '3001234567',
            'department_id' => $department->id,
            'password' => '',
            'password_confirmation' => '',
            'role' => UserRole::SuperAdmin->value,
        ])->assertRedirect(route('admin.contractors'))->assertSessionHas('status');

        $updated = $contractor->fresh();
        $this->assertSame('Cuadrilla actualizada', $updated->name);
        $this->assertSame('3001234567', $updated->phone);
        $this->assertSame($department->id, $updated->department_id);
        $this->assertSame(UserRole::Contractor, $updated->role);
        $this->assertSame($contractor->password, $updated->password);
        $this->assertModelExists($token);
    }

    public function test_admin_can_reset_and_revoke_contractor_credentials(): void
    {
        config(['session.driver' => 'database']);
        $admin = $this->admin();
        $contractor = User::factory()->create([
            'role' => UserRole::Contractor,
            'remember_token' => 'contractor-remember-token',
        ]);
        $token = $contractor->createToken('prueba')->accessToken;
        foreach ([$contractor, $admin] as $user) {
            \Illuminate\Support\Facades\DB::table('sessions')->insert([
                'id' => 'fixture-'.$user->id,
                'user_id' => $user->id,
                'payload' => '',
                'last_activity' => now()->timestamp,
            ]);
        }
        $password = 'Nueva-clave-fixture-123';
        $this->actingAs($admin)->patch(route('admin.contractors.update', $contractor), [
            'name' => $contractor->name,
            'email' => 'nuevo-correo@example.test',
            'department_id' => null,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect(route('admin.contractors'))->assertSessionHas('status');

        $updated = $contractor->fresh();
        $this->assertSame('nuevo-correo@example.test', $updated->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($password, $updated->password));
        $this->assertNotSame($password, $updated->password);
        $this->assertNull($updated->remember_token);
        $this->assertModelMissing($token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $contractor->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'fixture-'.$admin->id]);
    }

    public function test_contractor_edit_validates_unique_email_and_password_confirmation(): void
    {
        $admin = $this->admin();
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $editUrl = route('admin.contractors.edit', $contractor);

        $this->actingAs($admin)->from($editUrl)->patch(route('admin.contractors.update', $contractor), [
            'name' => $contractor->name,
            'email' => $admin->email,
            'password' => 'nueva-clave-no-confirmada',
            'password_confirmation' => 'otra',
        ])->assertRedirect($editUrl)
            ->assertSessionHasErrors(['email', 'password'])
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        $this->assertSame($contractor->password, $contractor->fresh()->password);
        $this->assertSame($contractor->email, $contractor->fresh()->email);
    }

    public function test_contractor_edit_rejects_unauthorized_users_and_archived_or_unrelated_accounts(): void
    {
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $archived = User::factory()->create([
            'role' => UserRole::Contractor,
            'is_active' => false,
            'contractor_archived_at' => now(),
        ]);
        $citizen = User::factory()->create(['role' => UserRole::Citizen]);

        $this->actingAs($contractor)->patch(route('admin.contractors.update', $contractor))->assertForbidden();
        $this->actingAs($this->admin());
        foreach ([$archived, $citizen] as $account) {
            $this->get(route('admin.contractors.edit', $account))->assertNotFound();
            $this->patch(route('admin.contractors.update', $account))->assertNotFound();
        }
    }

    public function test_admin_can_delete_an_unused_contractor_account_and_its_tokens(): void
    {
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $token = $contractor->createToken('prueba')->accessToken;
        $deleteUrl = route('admin.contractors.destroy', $contractor);

        $this->actingAs($this->admin())->get(route('admin.contractors'))->assertOk()
            ->assertSee($deleteUrl)->assertSee('Eliminar cuenta de '.$contractor->name);
        $this->delete($deleteUrl)->assertRedirect(route('admin.contractors'))->assertSessionHas('status');

        $this->assertModelMissing($contractor);
        $this->assertModelMissing($token);
    }

    public function test_admin_can_archive_contractor_accounts_and_preserve_active_and_completed_work(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        foreach ([WorkOrderStatus::Assigned, WorkOrderStatus::Completed] as $index => $status) {
            $contractor = User::factory()->create(['role' => UserRole::Contractor, 'remember_token' => 'old-session']);
            $token = $contractor->createToken('prueba')->accessToken;
            $report = $this->report($index + 1, ReportStatus::InProgress);
            $order = WorkOrder::create([
                'report_id' => $report->id, 'contractor_id' => $contractor->id,
                'scheduled_date' => now()->toDateString(), 'status' => $status,
            ]);
            $deleteUrl = route('admin.contractors.destroy', $contractor);

            $this->get(route('admin.contractors'))->assertOk()->assertSee($deleteUrl);
            $this->delete($deleteUrl)->assertRedirect(route('admin.contractors'))->assertSessionHas('status');

            $archived = $contractor->fresh();
            $this->assertNotNull($archived->contractor_archived_at);
            $this->assertFalse($archived->is_active);
            $this->assertNull($archived->remember_token);
            $this->assertModelMissing($token);
            $this->assertSame($contractor->id, $order->fresh()->contractor->id);
            $this->assertSame($order->getRawOriginal('status'), $order->fresh()->getRawOriginal('status'));
            $this->assertModelExists($report);

            $this->get(route('admin.contractors'))->assertOk()->assertDontSee($deleteUrl);
            $this->get(route('admin.analytics'))->assertOk()->assertSee('Cuenta archivada')
                ->assertViewHas('contractorPerformance', fn ($rows) => $rows->contains(
                    fn ($row) => $row['name'] === $contractor->name && $row['archived'] && $row['assigned'] === 1,
                ));
            $this->actingAs($archived)->get('/ordenes')->assertRedirect(route('login'));
            $this->actingAs($admin);
        }
    }

    public function test_assignment_rechecks_an_out_of_date_contractor_account(): void
    {
        $report = $this->report(1);
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        User::query()->whereKey($contractor->id)->update(['is_active' => false, 'contractor_archived_at' => now()]);

        $this->expectException(\App\Exceptions\WorkAssignmentException::class);
        app(\App\Services\WorkOrderDispatcher::class)->dispatch($report, $contractor, now()->toDateString());
    }

    public function test_contractor_deletion_rejects_other_roles_and_unauthorized_staff(): void
    {
        $contractor = User::factory()->create(['role' => UserRole::Contractor]);
        $citizen = User::factory()->create(['role' => UserRole::Citizen]);

        $this->actingAs($contractor)->delete(route('admin.contractors.destroy', $contractor))->assertForbidden();
        $this->actingAs($this->admin())->delete(route('admin.contractors.destroy', $citizen))->assertNotFound();

        $this->assertModelExists($contractor);
        $this->assertModelExists($citizen);
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
        $this->actingAs($this->admin())->get('/admin/reportes')->assertOk()
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
