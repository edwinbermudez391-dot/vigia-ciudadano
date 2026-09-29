<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Exceptions\WorkAssignmentException;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

/**
 * Sede única del despacho de obras (patrón ReportIntake): API REST y panel
 * Blade consumen EXACTAMENTE las mismas reglas.
 *
 * El invariante de estados (orden assigned -> reporte in_progress) exige
 * escritura atómica sobre dos tablas. El UNIQUE de work_orders.report_id
 * garantiza además que reasignaciones concurrentes converjan en una sola fila.
 */
final class WorkOrderDispatcher
{
    /**
     * Estados del reporte desde los cuales Alcaldía puede (re)asignar obra.
     */
    private const ASSIGNABLE_REPORT_STATES = [ReportStatus::Reported, ReportStatus::Rework];

    /**
     * @throws WorkAssignmentException si el reporte no es asignable o el contratista no es elegible
     */
    public function dispatch(
        Report $report,
        User $contractor,
        string $scheduledDate,
        ?string $instructions = null,
    ): WorkOrder {
        $this->assertAssignable($report, $contractor);

        return DB::transaction(fn (): WorkOrder => $this->persist($report, $contractor, $scheduledDate, $instructions));
    }

    private function assertAssignable(Report $report, User $contractor): void
    {
        if (! in_array($report->status, self::ASSIGNABLE_REPORT_STATES, true)) {
            throw WorkAssignmentException::reportNotAssignable($report->status->value);
        }

        if ($contractor->role !== UserRole::Contractor || $contractor->is_active !== true) {
            throw WorkAssignmentException::contractorNotEligible();
        }
    }

    private function persist(Report $report, User $contractor, string $scheduledDate, ?string $instructions): WorkOrder
    {
        $order = WorkOrder::updateOrCreate(
            ['report_id' => $report->id],
            [
                'contractor_id' => $contractor->id,
                'scheduled_date' => $scheduledDate,
                'status' => WorkOrderStatus::Assigned->value,
                // Instrucciones del despacho viajan con la orden; en API el
                // canal pasa null y se conserva el comportamiento antiguo de
                // limpiar la evidencia del intento anterior.
                'work_description' => $instructions,
                'solution_photo_url' => null,
                'completion_date' => null,
            ],
        );

        $report->update(['status' => ReportStatus::InProgress->value]);

        return $order;
    }
}
