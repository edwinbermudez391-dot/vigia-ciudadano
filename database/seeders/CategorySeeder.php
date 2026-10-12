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
            'OBRAS_PUB' => [
                [
                    'name' => 'Baches y Malla Vial',
                    'sla_hours' => 48,
                    'description' => 'Hundimientos, baches, deterioro o pérdida de pavimentación en vías urbanas y rurales.',
                ],
                [
                    'name' => 'Grietas en Fachadas y Muros',
                    'sla_hours' => 72,
                    'description' => 'Fisuras, grietas o deterioro estructural en fachadas de edificios públicos y muros de contención.',
                ],
                [
                    'name' => 'Andenes Deteriorados',
                    'sla_hours' => 96,
                    'description' => 'Baldosas rotas, hundimientos o levantamientos en andenes peatonales que representan riesgo de caída.',
                ],
            ],
            'MOVIL' => [
                [
                    'name' => 'Semáforo en Falla',
                    'sla_hours' => 12,
                    'description' => 'Semáforos sin funcionamiento, parpadeo intermitente o luces desincronizadas en cruces viales.',
                ],
                [
                    'name' => 'Señalización Vial Deteriorada',
                    'sla_hours' => 48,
                    'description' => 'Señales de tránsito dañadas, borrosas, faltantes o inclinadas en vías principales y secundarias.',
                ],
                [
                    'name' => 'Congestionamiento Vial Anormal',
                    'sla_hours' => 4,
                    'description' => 'Tapones inusuales por accidentes, vehículos averiados o cierre no autorizado de vías.',
                ],
            ],
            'ALUM' => [
                [
                    'name' => 'Luminaria Apagada o en Falla',
                    'sla_hours' => 24,
                    'description' => 'Puntos de alumbrado público sin iluminación, parpadeo constante o encendido diurno permanente.',
                ],
                [
                    'name' => 'Cableado Expuesto',
                    'sla_hours' => 12,
                    'description' => 'Cables colgantes, postes dañados o conexiones eléctricas expuestas en espacio público.',
                ],
            ],
            'AMB' => [
                [
                    'name' => 'Árbol en Riesgo de Caída',
                    'sla_hours' => 24,
                    'description' => 'Árboles inclinados, con ramas secas o raíces expuestas que amenazan infraestructura o personas.',
                ],
                [
                    'name' => 'Basuras y Escombros en Vía Pública',
                    'sla_hours' => 48,
                    'description' => 'Acumulación de residuos sólidos, escombros de construcción o residuos vegetales en espacio público.',
                ],
                [
                    'name' => 'Contaminación Auditiva',
                    'sla_hours' => 24,
                    'description' => 'Ruidos excesivos por obras, establecimientos comerciales o actividades industriales fuera de norma.',
                ],
            ],
            'SERV_PUB' => [
                [
                    'name' => 'Fuga de Agua Potable',
                    'sla_hours' => 12,
                    'description' => 'Pérdida de agua en redes de acueducto: rotura de tubería, brocales o válvulas en vía pública.',
                ],
                [
                    'name' => 'Alcantarillado o Sumidero Tapado',
                    'sla_hours' => 48,
                    'description' => 'Aguados por alcantarillado obstruido, sumideros tapados o malos olores en redes de desagüe.',
                ],
                [
                    'name' => 'Sin Suministro de Agua',
                    'sla_hours' => 8,
                    'description' => 'Interrupción del servicio de acueducto en viviendas o zonas residenciales sin aviso previo.',
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
