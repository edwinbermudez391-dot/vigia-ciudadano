<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use App\Exceptions\WorkExecutionException;
use App\Models\WorkOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Sede única del ciclo de EJECUCIÓN de la orden de trabajo (patrón
 * WorkOrderDispatcher): la app de campo (API) y el portal Blade consumen
 * exactamente la misma máquina de estados y las mismas compensaciones.
 *
 * Transiciones válidas:
 *   assigned ──start()──▶ in_execution ──complete()──▶ completed
 *   (el retrabajo del inspector devuelve la orden a assigned)
 */
final class WorkOrderExecution
{
    private const SOLUTION_PHOTO_FOLDER = 'work-orders/solutions';

    public function __construct(
        private readonly ImageUploader $uploader,
    ) {}

    /**
     * Marca el inicio de obra en sitio. Idempotente: reintentos del cliente
     * sobre una orden ya en ejecución no generan error ni doble transición.
     *
     * @throws WorkExecutionException si la orden ya fue completada
     */
    public function start(WorkOrder $order): WorkOrder
    {
        if ($order->status === WorkOrderStatus::Completed) {
            throw WorkExecutionException::alreadyCompleted();
        }

        if ($order->status === WorkOrderStatus::InExecution) {
            return $order;
        }

        $order->update(['status' => WorkOrderStatus::InExecution->value]);

        return $order;
    }

    /**
     * Foto + doble cambio de estado en una transacción con compensación del
     * archivo (el filesystem no participa del rollback), patrón ya usado en
     * CitizenReportController::persistReport().
     *
     * @throws WorkExecutionException si la orden no está en ejecución
     */
    public function complete(WorkOrder $order, string $description, UploadedFile $photo): WorkOrder
    {
        if ($order->status !== WorkOrderStatus::InExecution) {
            throw WorkExecutionException::notExecutable($order->status->value);
        }

        $report = $order->report;

        if ($report === null) {
            throw new RuntimeException('El reporte asociado a la orden de trabajo ya no existe.');
        }

        return DB::transaction(function () use ($order, $report, $description, $photo): WorkOrder {
            // Nombre derivado del ticket (único garantizado), nunca del cliente.
            $photoPath = $this->uploader->upload($photo, self::SOLUTION_PHOTO_FOLDER, $report->ticket_code);

            try {
                $order->update([
                    'work_description' => $description,
                    'solution_photo_url' => $photoPath,
                    'completion_date' => now(),
                    'status' => WorkOrderStatus::Completed->value,
                ]);

                $report->update(['status' => ReportStatus::UnderInspection->value]);

                return $order;
            } catch (Throwable $e) {
                $this->uploader->delete($photoPath);

                throw $e;
            }
        });
    }
}
