<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Dependencias maestras que gestionan las categorías de reporte.
 *
 * Idempotente: usa `code` (UNIQUE en departments) como clave natural,
 * por lo que puede re-ejecutarse sin generar duplicados ni violaciones.
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Secretaría de Infraestructura y Malla Vial',
                'code' => 'SEC_INFRA',
                'contact_email' => 'infraestructura@vigia.gov.co',
                'phone' => '(601) 555-0101',
            ],
            [
                'name' => 'Concesión de Alumbrado Público',
                'code' => 'ALUM_PUB',
                'contact_email' => 'alumbrado@vigia.gov.co',
                'phone' => '(601) 555-0102',
            ],
            [
                'name' => 'Empresa Municipal de Acueducto y Alcantarillado',
                'code' => 'ACUE_ALC',
                'contact_email' => 'acueducto@vigia.gov.co',
                'phone' => '(601) 555-0103',
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                $department + ['is_active' => true],
            );
        }
    }
}
