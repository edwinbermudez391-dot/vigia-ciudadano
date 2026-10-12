<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Usuarios de prueba listos para autenticarse contra la API (Sanctum).
 *
 * Clave natural: `email` (UNIQUE). Contraseña "password" para todos los usuarios,
 * que el cast `hashed` del modelo cifra al persistir. Re-ejecutar el seeder
 * restablece esa contraseña, útil si una prueba la cambió.
 */
class UserSeeder extends Seeder
{
    private const TEST_PASSWORD = 'password';

    public function run(): void
    {
        $users = [
            // Superadmin (1)
            [
                'name' => 'Administrador Vigía',
                'email' => 'admin@vigia.gov.co',
                'phone' => '+57 300 111 0001',
                'role' => UserRole::SuperAdmin,
                'department_id' => null,
            ],

            // Quality Inspectors (2)
            [
                'name' => 'Inspector de Calidad Uno',
                'email' => 'inspector1@calidad.gov.co',
                'phone' => '+57 303 444 0001',
                'role' => UserRole::QualityInspector,
                'department_id' => null,
            ],
            [
                'name' => 'Inspector de Calidad Dos',
                'email' => 'inspector2@calidad.gov.co',
                'phone' => '+57 303 444 0002',
                'role' => UserRole::QualityInspector,
                'department_id' => null,
            ],

            // Contractors (3) — uno por departamento operativo
            [
                'name' => 'Cuadrilla Infraestructura',
                'email' => 'cuadrilla@infraestructura.com',
                'phone' => '+57 302 333 0001',
                'role' => UserRole::Contractor,
                'department_id' => $this->departmentId('OBRAS_PUB'),
            ],
            [
                'name' => 'Cuadrilla Movilidad',
                'email' => 'cuadrilla@movilidad.com',
                'phone' => '+57 302 333 0002',
                'role' => UserRole::Contractor,
                'department_id' => $this->departmentId('MOVIL'),
            ],
            [
                'name' => 'Cuadrilla Servicios Públicos',
                'email' => 'cuadrilla@servicios.com',
                'phone' => '+57 302 333 0003',
                'role' => UserRole::Contractor,
                'department_id' => $this->departmentId('SERV_PUB'),
            ],

            // Citizens (5)
            [
                'name' => 'Ciudadano Uno',
                'email' => 'ciudadano1@test.com',
                'phone' => '+57 310 555 0001',
                'role' => UserRole::Citizen,
                'department_id' => null,
            ],
            [
                'name' => 'Ciudadano Dos',
                'email' => 'ciudadano2@test.com',
                'phone' => '+57 310 555 0002',
                'role' => UserRole::Citizen,
                'department_id' => null,
            ],
            [
                'name' => 'Ciudadano Tres',
                'email' => 'ciudadano3@test.com',
                'phone' => '+57 310 555 0003',
                'role' => UserRole::Citizen,
                'department_id' => null,
            ],
            [
                'name' => 'Ciudadano Cuatro',
                'email' => 'ciudadano4@test.com',
                'phone' => '+57 310 555 0004',
                'role' => UserRole::Citizen,
                'department_id' => null,
            ],
            [
                'name' => 'Ciudadano Cinco',
                'email' => 'ciudadano5@test.com',
                'phone' => '+57 310 555 0005',
                'role' => UserRole::Citizen,
                'department_id' => null,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user + [
                    'password' => self::TEST_PASSWORD,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }

    /**
     * Id de la dependencia por código, con fallo temprano y contextual si
     * DepartmentSeeder aún no se ejecutó.
     */
    private function departmentId(string $code): int
    {
        $id = Department::query()->where('code', $code)->value('id');

        if ($id === null) {
            throw new RuntimeException(
                "Dependencia '{$code}' no encontrada. Ejecuta DepartmentSeeder antes de UserSeeder."
            );
        }

        return $id;
    }
}
