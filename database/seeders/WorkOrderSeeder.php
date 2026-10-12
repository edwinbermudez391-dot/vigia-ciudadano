<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InspectionDecision;
use App\Enums\WorkOrderStatus;
use App\Models\QualityInspection;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use App\Enums\ReportStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Órdenes de trabajo e inspecciones de calidad coherentes con el estado de los reportes.
 *
 * Genera 8 órdenes de trabajo y 5 inspecciones de calidad distribuidas así:
 * - 3 órdenes en estado "assigned" o "in_execution" (sin inspección)
 * - 2 órdenes en estado "completed" sin inspección (esperando en cola del inspector)
 * - 3 órdenes en estado "completed" con inspección "approved" (reporte closed)
 * - 2 órdenes en estado "completed" con inspección "rework" (reporte rework)
 *
 * Requiere que ReportSeeder y UserSeeder se hayan ejecutado primero.
 */
class WorkOrderSeeder extends Seeder
{
    /**
     * Coordenadas GPS del inspector (mismas localidades de Bogotá, con ligera variación).
     */
    private const INSPECTOR_LOCATIONS = [
        ['4.60971200', '-74.08174200'],
        ['4.65418200', '-74.06362200'],
        ['4.74196200', '-74.10235200'],
        ['4.61897200', '-74.12378200'],
        ['4.70134200', '-74.04153200'],
    ];

    public function run(): void
    {
        $contractors = User::query()->where('role', 'contractor')->pluck('id')->all();
        $inspectors = User::query()->where('role', 'quality_inspector')->pluck('id')->all();

        if (count($contractors) < 3) {
            throw new RuntimeException(
                'Se requieren al menos 3 contratistas. Ejecuta UserSeeder antes de WorkOrderSeeder.'
            );
        }

        if (count($inspectors) < 2) {
            throw new RuntimeException(
                'Se requieren al menos 2 inspectores. Ejecuta UserSeeder antes de WorkOrderSeeder.'
            );
        }

        $workOrders = [
            // REP-2026-0003: in_progress, work order assigned
            [
                'report_code' => 'REP-2026-0003',
                'contractor_id' => $contractors[2], // Servicios Públicos
                'scheduled_date' => now()->subDays(5),
                'completion_date' => null,
                'work_description' => null,
                'solution_photo_url' => null,
                'status' => WorkOrderStatus::Assigned,
                'created_at' => now()->subDays(6),
            ],

            // REP-2026-0004: in_progress, work order in execution
            [
                'report_code' => 'REP-2026-0004',
                'contractor_id' => $contractors[1], // Movilidad
                'scheduled_date' => now()->subDays(4),
                'completion_date' => null,
                'work_description' => null,
                'solution_photo_url' => null,
                'status' => WorkOrderStatus::InExecution,
                'created_at' => now()->subDays(5),
            ],

            // REP-2026-0005: under_inspection, work order completed, no inspection yet
            [
                'report_code' => 'REP-2026-0005',
                'contractor_id' => $contractors[0], // Infraestructura
                'scheduled_date' => now()->subDays(3),
                'completion_date' => now()->subDays(1),
                'work_description' => 'Se realizó poda del árbol y se instaló soporte de refuerzo.',
                'solution_photo_url' => '/storage/reports/solution/arbol-0005-sol.jpg',
                'status' => WorkOrderStatus::Completed,
                'created_at' => now()->subDays(4),
            ],

            // REP-2026-0006: under_inspection, work order completed, no inspection yet
            [
                'report_code' => 'REP-2026-0006',
                'contractor_id' => $contractors[2], // Servicios Públicos
                'scheduled_date' => now()->subDays(2),
                'completion_date' => now()->subHours(12),
                'work_description' => 'Limpieza completa del sumidero y desobstrucción del alcantarillado.',
                'solution_photo_url' => '/storage/reports/solution/sumidero-0006-sol.jpg',
                'status' => WorkOrderStatus::Completed,
                'created_at' => now()->subDays(3),
            ],

            // REP-2026-0007: closed, work order completed, inspection approved
            [
                'report_code' => 'REP-2026-0007',
                'contractor_id' => $contractors[0], // Infraestructura
                'scheduled_date' => now()->subDays(12),
                'completion_date' => now()->subDays(10),
                'work_description' => 'Reparación completa del andén: retiro de baldosas rotas, nivelación y colocación de nueva pavimentación.',
                'solution_photo_url' => '/storage/reports/solution/anden-0007-sol.jpg',
                'status' => WorkOrderStatus::Completed,
                'created_at' => now()->subDays(14),
            ],

            // REP-2026-0008: closed, work order completed, inspection approved
            [
                'report_code' => 'REP-2026-0008',
                'contractor_id' => $contractors[0], // Infraestructura
                'scheduled_date' => now()->subDays(11),
                'completion_date' => now()->subDays(9),
                'work_description' => 'Retiro completo de escombros, limpieza del área y disposición final en escombrera autorizada.',
                'solution_photo_url' => '/storage/reports/solution/escombros-0008-sol.jpg',
                'status' => WorkOrderStatus::Completed,
                'created_at' => now()->subDays(13),
            ],

            // REP-2026-0009: closed, work order completed, inspection approved
            [
                'report_code' => 'REP-2026-0009',
                'contractor_id' => $contractors[2], // Servicios Públicos
                'scheduled_date' => now()->subDays(10),
                'completion_date' => now()->subDays(8),
                'work_description' => 'Reparación de válvula principal y restablecimiento del servicio de agua a la manzana.',
                'solution_photo_url' => '/storage/reports/solution/agua-0009-sol.jpg',
                'status' => WorkOrderStatus::Completed,
                'created_at' => now()->subDays(12),
            ],

            // REP-2026-0010: rework, work order completed, inspection rejected
            [
                'report_code' => 'REP-2026-0010',
                'contractor_id' => $contractors[1], // Movilidad
                'scheduled_date' => now()->subDays(9),
                'completion_date' => now()->subDays(7),
                'work_description' => 'Reemplazo de las señales de tránsito dañadas.',
                'solution_photo_url' => '/storage/reports/solution/señal-0010-sol.jpg',
                'status' => WorkOrderStatus::Completed,
                'created_at' => now()->subDays(11),
            ],

            // REP-2026-0011: rework, work order completed, inspection rejected
            [
                'report_code' => 'REP-2026-0011',
                'contractor_id' => $contractors[0], // Infraestructura
                'scheduled_date' => now()->subDays(8),
                'completion_date' => now()->subDays(6),
                'work_description' => 'Retiro de cableado expuesto y reubicación de conexiones eléctricas.',
                'solution_photo_url' => '/storage/reports/solution/cable-0011-sol.jpg',
                'status' => WorkOrderStatus::Completed,
                'created_at' => now()->subDays(10),
            ],
        ];

        $inspections = [
            // REP-2026-0007: approved
            [
                'report_code' => 'REP-2026-0007',
                'inspector_id' => $inspectors[0],
                'checklist_results' => [
                    'superficie_nivelada' => 'si',
                    'material_calidad' => 'si',
                    'accesibilidad_peatonal' => 'si',
                    'senalizacion_temporal' => 'no_aplica',
                ],
                'decision' => InspectionDecision::Approved,
                'rejection_reason' => null,
                'verification_photo_url' => '/storage/reports/verification/anden-0007-ver.jpg',
                'inspector_latitude' => self::INSPECTOR_LOCATIONS[0][0],
                'inspector_longitude' => self::INSPECTOR_LOCATIONS[0][1],
                'inspected_at' => now()->subDays(9),
            ],

            // REP-2026-0008: approved
            [
                'report_code' => 'REP-2026-0008',
                'inspector_id' => $inspectors[1],
                'checklist_results' => [
                    'area_limpia' => 'si',
                    'disposicion_final' => 'si',
                    'sin_residuos' => 'si',
                    'impacto_ambiental' => 'minimo',
                ],
                'decision' => InspectionDecision::Approved,
                'rejection_reason' => null,
                'verification_photo_url' => '/storage/reports/verification/escombros-0008-ver.jpg',
                'inspector_latitude' => self::INSPECTOR_LOCATIONS[1][0],
                'inspector_longitude' => self::INSPECTOR_LOCATIONS[1][1],
                'inspected_at' => now()->subDays(8),
            ],

            // REP-2026-0009: approved
            [
                'report_code' => 'REP-2026-0009',
                'inspector_id' => $inspectors[0],
                'checklist_results' => [
                    'servicio_restaurado' => 'si',
                    'presion_adecuada' => 'si',
                    'sin_fugas' => 'si',
                    'calidad_agua' => 'potable',
                ],
                'decision' => InspectionDecision::Approved,
                'rejection_reason' => null,
                'verification_photo_url' => '/storage/reports/verification/agua-0009-ver.jpg',
                'inspector_latitude' => self::INSPECTOR_LOCATIONS[2][0],
                'inspector_longitude' => self::INSPECTOR_LOCATIONS[2][1],
                'inspected_at' => now()->subDays(7),
            ],

            // REP-2026-0010: rework
            [
                'report_code' => 'REP-2026-0010',
                'inspector_id' => $inspectors[1],
                'checklist_results' => [
                    'senalizacion_visible' => 'parcial',
                    'material_reflectivo' => 'no',
                    'angulo_correcto' => 'no',
                    'fijacion_estable' => 'si',
                ],
                'decision' => InspectionDecision::Rework,
                'rejection_reason' => 'Las señales instaladas no tienen material reflectivo adecuado y están mal orientadas. Se requiere reemplazo.',
                'verification_photo_url' => '/storage/reports/verification/señal-0010-ver.jpg',
                'inspector_latitude' => self::INSPECTOR_LOCATIONS[3][0],
                'inspector_longitude' => self::INSPECTOR_LOCATIONS[3][1],
                'inspected_at' => now()->subDays(6),
            ],

            // REP-2026-0011: rework
            [
                'report_code' => 'REP-2026-0011',
                'inspector_id' => $inspectors[0],
                'checklist_results' => [
                    'cableado_retirado' => 'parcial',
                    'conexion_segura' => 'no',
                    'aislamiento_adecuado' => 'no',
                    'senalizacion_peligro' => 'no',
                ],
                'decision' => InspectionDecision::Rework,
                'rejection_reason' => 'Queda cableado expuesto en el tramo norte. Las conexiones no tienen aislamiento adecuado. Riesgo de electrocución persiste.',
                'verification_photo_url' => '/storage/reports/verification/cable-0011-ver.jpg',
                'inspector_latitude' => self::INSPECTOR_LOCATIONS[4][0],
                'inspector_longitude' => self::INSPECTOR_LOCATIONS[4][1],
                'inspected_at' => now()->subDays(5),
            ],
        ];

        // Crear órdenes de trabajo
        foreach ($workOrders as $workOrder) {
            $report = $this->resolveReport($workOrder['report_code']);

            WorkOrder::updateOrCreate(
                ['report_id' => $report->id],
                [
                    'contractor_id' => $workOrder['contractor_id'],
                    'scheduled_date' => $workOrder['scheduled_date'],
                    'completion_date' => $workOrder['completion_date'],
                    'work_description' => $workOrder['work_description'],
                    'solution_photo_url' => $workOrder['solution_photo_url'],
                    'status' => $workOrder['status'],
                    'created_at' => $workOrder['created_at'],
                ],
            );
        }

        // Crear inspecciones de calidad
        foreach ($inspections as $inspection) {
            $report = $this->resolveReport($inspection['report_code']);
            $workOrder = WorkOrder::query()->where('report_id', $report->id)->firstOrFail();

            QualityInspection::updateOrCreate(
                ['work_order_id' => $workOrder->id],
                [
                    'inspector_id' => $inspection['inspector_id'],
                    'checklist_results' => $inspection['checklist_results'],
                    'decision' => $inspection['decision'],
                    'rejection_reason' => $inspection['rejection_reason'],
                    'verification_photo_url' => $inspection['verification_photo_url'],
                    'inspector_latitude' => $inspection['inspector_latitude'],
                    'inspector_longitude' => $inspection['inspector_longitude'],
                    'inspected_at' => $inspection['inspected_at'],
                ],
            );
        }
    }

    /**
     * Busca un reporte por ticket_code con fallo temprano si no existe.
     */
    private function resolveReport(string $ticketCode): Report
    {
        $report = Report::query()->where('ticket_code', $ticketCode)->first();

        if ($report === null) {
            throw new RuntimeException(
                "Reporte '{$ticketCode}' no encontrado. Ejecuta ReportSeeder antes de WorkOrderSeeder."
            );
        }

        return $report;
    }
}
