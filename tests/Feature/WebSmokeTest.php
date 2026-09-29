<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Humo de las vistas Blade: que las páginas rendericen y que la sesión
 * y el encadenamiento de rutas (login → aterrizaje por rol → vistas) funcionen.
 *
 * Nota: Los ciudadanos reportan incidencias de forma pública y anónima sin cuenta.
 * El sistema de autenticación es exclusivo para personal institucional.
 */
final class WebSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(UserRole $role): User
    {
        $user = User::factory()->create(['role' => $role->value]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('Iniciar sesión', false)
            ->assertSee('Correo electrónico', false);
    }

    public function test_guest_can_access_public_report_routes(): void
    {
        // Las rutas de reportes son públicas (sin autenticación)
        $this->get('/reportes/crear')->assertStatus(200);
    }

    public function test_credentials_login_lands_by_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::Contractor->value]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('contractor.orders'));

        // Sesión real de guard: la siguiente petición autenticada responde 200.
        $this->get('/ordenes')->assertStatus(200);
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Contractor->value,
            'is_active' => false,
        ]);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        // La cuenta quedó intacta pero SIN sesión: el flag is_active es
        // credencial de attempt(), no solo un filtro posterior.
        $this->assertGuest();
    }

    public function test_report_create_page_shows_catalog_and_form_publicly(): void
    {
        $department = Department::create([
            'name' => 'Secretaría de Infraestructura',
            'code' => 'SEC_INFRA',
            'contact_email' => 'a@b.co',
            'phone' => '123',
        ]);

        Category::create([
            'department_id' => $department->id,
            'name' => 'Baches y Malla Vial',
            'sla_hours' => 72,
            'description' => 'Hundimientos.',
        ]);

        // La página de creación de reportes es pública (sin autenticación)
        $this->get('/reportes/crear')
            ->assertStatus(200)
            ->assertSee('Baches y Malla Vial')
            ->assertSee('capture="environment"', false);
    }

    public function test_show_page_renders_timeline_for_ticket_publicly(): void
    {
        $department = Department::create([
            'name' => 'Infraestructura', 'code' => 'INF',
            'contact_email' => 'a@b.co', 'phone' => '1',
        ]);
        $category = Category::create([
            'department_id' => $department->id, 'name' => 'Baches',
            'sla_hours' => 72, 'description' => 'x',
        ]);

        // Reporte público sin usuario vinculado (ciudadano anónimo)
        $report = Report::create([
            'ticket_code' => 'REP-2026-0007',
            'user_id' => null,
            'citizen_name' => 'Ciudadano Anónimo',
            'category_id' => $category->id,
            'title' => 'Bache en la 80',
            'description' => 'Bache profundo con agua.',
            'damage_photo_url' => 'reports/damages/REP-2026-0007.jpg',
            'latitude' => 4.6,
            'longitude' => -74.1,
            'address_reference' => 'Cra 11',
            'status' => \App\Enums\ReportStatus::Reported->value,
        ]);

        // La vista de detalle es pública (sin autenticación)
        $this->get('/reportes/REP-2026-0007')
            ->assertStatus(200)
            ->assertSee('REP-2026-0007')
            ->assertSee('Reporte radado')
            ->assertSee('Asignación a contratista');
    }

    public function test_inspector_pages_are_gated_by_role(): void
    {
        // Un contratista no puede acceder a inspecciones
        $this->actingAsRole(UserRole::Contractor);
        $this->get('/inspector/inspecciones')->assertStatus(403);

        // Un inspector sí puede acceder
        $this->actingAsRole(UserRole::QualityInspector);
        $this->get('/inspector/inspecciones')->assertStatus(200);
    }

    public function test_admin_and_contractor_landing_pages_render(): void
    {
        $this->actingAsRole(UserRole::SuperAdmin);
        $this->get('/admin')->assertStatus(200)->assertSee('Pulso del municipio');

        $this->actingAsRole(UserRole::Contractor);
        $this->get('/ordenes')->assertStatus(200)->assertSee('Mis órdenes de trabajo');
    }

    public function test_only_institutional_roles_can_login(): void
    {
        // Verifica que los 3 roles institucionales pueden iniciar sesión
        $contractor = User::factory()->create(['role' => UserRole::Contractor->value]);
        $this->post('/login', ['email' => $contractor->email, 'password' => 'password'])
            ->assertRedirect(route('contractor.orders'));

        $this->post('/logout')->assertRedirect(route('login'));

        $inspector = User::factory()->create(['role' => UserRole::QualityInspector->value]);
        $this->post('/login', ['email' => $inspector->email, 'password' => 'password'])
            ->assertRedirect(route('inspector.queue'));

        $this->post('/logout')->assertRedirect(route('login'));

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin->value]);
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
