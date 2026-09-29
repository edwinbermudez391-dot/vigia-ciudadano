<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inspector;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQualityInspectionRequest;
use App\Http\Resources\QualityInspectionResource;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\WorkOrder;
use App\Services\QualityInspectionRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * API REST de inspección de calidad (bucle de cierre de la incidencia).
 *
 * El inspector revisa la evidencia del ciudadano (damage_photo_url) contra la
 * solución entregada por el contratista (solution_photo_url) y decide:
 *  - approved => reporte cerrado.
 *  - rework   => reporte a retrabajo y orden de vuelta en ejecución.
 *
 * Solo inspectores de calidad activos (role:quality_inspector en la ruta +
 * authorize() del FormRequest como segunda capa).
 */
final class QualityInspectionController extends Controller
{
    public function __construct(
        private readonly QualityInspectionRecorder $recorder,
    ) {}

    /**
     * GET /api/v1/inspections/pending — Solo Inspector.
     *
     * Cola de trabajo: reportes under_inspection con su orden de trabajo.
     * El ReportResource ya expone el par de fotos comparativas:
     * damage_photo_url (daño original) vs work_order.solution_photo_url (obra).
     */
    public function pendingList(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 50);

        $reports = Report::query()
            ->with(['category', 'user', 'workOrder.contractor'])
            ->status(ReportStatus::UnderInspection)
            // Cola FIFO por antigüedad: lo que lleva más tiempo esperando, primero.
            ->orderBy('updated_at')
            ->orderBy('id')
            ->paginate($perPage);

        return ReportResource::collection($reports)
            ->toResponse($request);
    }

    /**
     * POST /api/v1/work-orders/{workOrder}/inspections — Solo Inspector.
     *
     * El registro de la inspección y la transición de estados del reporte y
     * de la orden son atómicos: una decisión sin cambio de estado (o viceversa)
     * rompería el invariante del ciclo de vida (lógica en QualityInspectionRecorder).
     */
    public function storeInspection(StoreQualityInspectionRequest $request, int $workOrder): JsonResponse
    {
        $order = WorkOrder::query()
            ->with(['report', 'contractor'])
            ->find($workOrder);

        if ($order === null || $order->report === null) {
            return response()->json([
                'message' => 'La orden de trabajo a inspeccionar no existe.',
            ], 404);
        }

        if ($order->status !== WorkOrderStatus::Completed) {
            return response()->json([
                'message' => 'Solo órdenes completadas por el contratista pueden inspeccionarse '
                    .'(estado actual: '.$order->status->value.').',
            ], 422);
        }

        $report = $order->report;

        if ($report->status !== ReportStatus::UnderInspection) {
            return response()->json([
                'message' => 'La incidencia no está pendiente de inspección '
                    .'(estado actual: '.$report->status->value.').',
            ], 422);
        }

        // 'in:approved,rework' ya filtró el valor en el FormRequest; tryFrom
        // es la red para convertirlo a enum sin riesgo de excepción.
        $decision = InspectionDecision::tryFrom((string) $request->validated('decision'));

        if ($decision === null) {
            return response()->json([
                'message' => 'Decisión de inspección no reconocida.',
            ], 422);
        }

        /** @var UploadedFile $photo */
        $photo = $request->validated('verification_photo');

        $inspection = $this->recorder->record(
            $order,
            $report,
            $request->user(),
            $decision,
            [
                'checklist_results' => $request->validated('checklist_results'),
                'rejection_reason' => $request->validated('rejection_reason'),
                'inspector_latitude' => $request->validated('inspector_latitude'),
                'inspector_longitude' => $request->validated('inspector_longitude'),
            ],
            $photo,
        );

        return response()->json([
            'message' => $decision === InspectionDecision::Approved
                ? "Obra aprobada: la incidencia {$report->ticket_code} ha sido cerrada."
                : "Obra rechazada: la incidencia {$report->ticket_code} vuelve a retrabajo con el contratista.",
            'data' => [
                'inspection' => (new QualityInspectionResource($inspection->load('inspector')))->resolve($request),
                'work_order' => [
                    'id' => $order->id,
                    'status' => $order->fresh()->status->value,
                ],
                'report' => [
                    'id' => $report->id,
                    'ticket_code' => $report->ticket_code,
                    'status' => $report->fresh()->status->value,
                ],
            ],
        ], 201);
    }
}
