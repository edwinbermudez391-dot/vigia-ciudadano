<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Catálogo de tipos de reporte con su SLA, asociado a la dependencia responsable.
 *
 * Idempotente: usa la clave natural UNIQUE(department_id, name) de la tabla
 * categories. Requiere que DepartmentSeeder se haya ejecutado primero
 * (así lo garantiza DatabaseSeeder en su orden de llamadas).
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // code de dependencia => categorías que administra.
        $catalog = [
            'SEC_INFRA' => [
                [
                    'name' => 'Baches y Malla Vial',
                    'sla_hours' => 72,
                    'description' => 'Hundimientos, baches, deterioro o pérdida de pavimentación en vías urbanas y rurales.',
                ],
            ],
            'ALUM_PUB' => [
                [
                    'name' => 'Luminaria Apagada / En Falla',
                    'sla_hours' => 48,
                    'description' => 'Puntos de alumbrado público sin iluminación, parpadeo, cables expuestos o postes dañados.',
                ],
            ],
            'ACUE_ALC' => [
                [
                    'name' => 'Fuga de Agua Potable',
                    'sla_hours' => 24,
                    'description' => 'Pérdida de agua en redes de acueducto: rotura de tubería, brocales o válvulas en vía pública.',
                ],
                [
                    'name' => 'Alcantarillado / Sumidero Tapado',
                    'sla_hours' => 48,
                    'description' => 'Aguadas por alcantarillado obstruido, sumideros tapados o malos olores en redes de desagüe.',
                ],
            ],
        ];

        foreach ($catalog as $code => $categories) {
            $department = $this->resolveDepartment($code);

            foreach ($categories as $category) {
                Category::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'name' => $category['name'],
                    ],
                    $category + ['is_active' => true],
                );
            }
        }
    }

    /**
     * Falla temprano y con contexto si falta la dependencia base, en lugar
     * de poblar categorías huérfanas o ignorar el error silenciosamente.
     */
    private function resolveDepartment(string $code): Department
    {
        $department = Department::query()->where('code', $code)->first();

        if ($department === null) {
            throw new RuntimeException(
                "Dependencia '{$code}' no encontrada. Ejecuta DepartmentSeeder antes de CategorySeeder."
            );
        }

        return $department;
    }
}
