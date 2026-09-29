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
 * Clave natural: `email` (UNIQUE). Credenciales para los tres perfiles institucionales:
 * contraseña "password123", que el cast `hashed` del modelo cifra al persistir.
 * Re-ejecutar el seeder restablece esa contraseña, útil si una prueba la cambió.
 *
 * Nota: Los ciudadanos reportan incidencias de forma pública y anónima sin necesidad de cuenta.
 */
class UserSeeder extends Seeder
{
    private const TEST_PASSWORD = 'password123';

    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrador Vigía',
                'email' => 'admin@vigia.gov.co',
                'phone' => '+57 300 111 0001',
                'role' => UserRole::SuperAdmin,
                'department_id' => null,
            ],
            [
                'name' => 'Cuadrilla Infraestructura',
                'email' => 'cuadrilla@infraestructura.com',
                'phone' => '+57 302 333 0003',
                'role' => UserRole::Contractor,
                'department_id' => $this->departmentId('SEC_INFRA'),
            ],
            [
                'name' => 'Inspector de Calidad',
                'email' => 'inspector@calidad.gov.co',
                'phone' => '+57 303 444 0004',
                'role' => UserRole::QualityInspector,
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
