<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use App\Models\QualityInspection;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Registro de la inspección de calidad con su transición de estados.
 *
 * El par (quality_inspections, reports.status, work_orders.status) se
 * persiste atómicamente y la foto se compensa si la BD falla: es el único
 * punto que conoce la máquina de estados del cierre de la incidencia.
 * Compartido por la API (app móvil) y la consola web del inspector.
 */
final class QualityInspectionRecorder
{
    private const VERIFICATION_PHOTO_FOLDER = 'work-orders/verifications';

    public function __construct(
        private readonly ImageUploader $uploader,
    ) {}

    /**
     * @param  array{checklist_results: array<array-key, mixed>, rejection_reason?: ?string, inspector_latitude: string|float, inspector_longitude: string|float}  $payload
     */
    public function record(
        WorkOrder $order,
        Report $report,
        User $inspector,
        InspectionDecision $decision,
        array $payload,
        UploadedFile $photo,
    ): QualityInspection {
        return DB::transaction(function () use ($order, $report, $inspector, $decision, $payload, $photo): QualityInspection {
            // Sufijo temporal: admite múltiples inspecciones (rework tras
            // rework) sin sobrescribir la evidencia de visitas anteriores.
            $baseName = sprintf('%s-insp%d-%d', $report->ticket_code, $order->id, time());
            $photoPath = $this->uploader->upload($photo, self::VERIFICATION_PHOTO_FOLDER, $baseName);

            try {
                $inspection = QualityInspection::create([
                    'work_order_id' => $order->id,
                    'inspector_id' => $inspector->id,
                    'checklist_results' => $payload['checklist_results'],
                    'decision' => $decision->value,
                    // Un "approved" no admite motivo de rechazo: se normaliza
                    // a null aunque el cliente lo haya enviado.
                    'rejection_reason' => $decision === InspectionDecision::Rework
                        ? ($payload['rejection_reason'] ?? null)
                        : null,
                    'verification_photo_url' => $photoPath,
                    'inspector_latitude' => (float) $payload['inspector_latitude'],
                    'inspector_longitude' => (float) $payload['inspector_longitude'],
                    'inspected_at' => now(),
                ]);

                $this->applyBusinessTransition($order, $report, $decision);

                return $inspection;
            } catch (Throwable $e) {
                $this->uploader->delete($photoPath);

                throw $e;
            }
        });
    }

    /**
     * Máquina de estados del cierre: única sede de las transiciones
     * derivadas de la decisión del inspector.
     */
    private function applyBusinessTransition(WorkOrder $order, Report $report, InspectionDecision $decision): void
    {
        if ($decision === InspectionDecision::Approved) {
            $report->update(['status' => ReportStatus::Closed->value]);

            return;
        }

        $report->update(['status' => ReportStatus::Rework->value]);
        // La orden vuelve a ejecución para que el contratista reintente la
        // obra (completeWork exige in_execution: el ciclo se reabre solo).
        $order->update(['status' => WorkOrderStatus::InExecution->value]);
    }
}
