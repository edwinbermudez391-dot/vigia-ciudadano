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
                'name' => 'Secretaría de Obras Públicas e Infraestructura',
                'code' => 'OBRAS_PUB',
                'contact_email' => 'obraspublicas@vigia.gov.co',
                'phone' => '(601) 555-0101',
            ],
            [
                'name' => 'Secretaría de Movilidad y Tránsito',
                'code' => 'MOVIL',
                'contact_email' => 'movilidad@vigia.gov.co',
                'phone' => '(601) 555-0102',
            ],
            [
                'name' => 'Concesión de Alumbrado Público',
                'code' => 'ALUM',
                'contact_email' => 'alumbrado@vigia.gov.co',
                'phone' => '(601) 555-0103',
            ],
            [
                'name' => 'Secretaría de Gestión Ambiental',
                'code' => 'AMB',
                'contact_email' => 'ambiental@vigia.gov.co',
                'phone' => '(601) 555-0104',
            ],
            [
                'name' => 'Empresa de Servicios Públicos',
                'code' => 'SERV_PUB',
                'contact_email' => 'serviciospublicos@vigia.gov.co',
                'phone' => '(601) 555-0105',
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
