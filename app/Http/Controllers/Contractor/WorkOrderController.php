<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contractor;

use App\Exceptions\WorkAssignmentException;
use App\Exceptions\WorkExecutionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteWorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderDispatcher;
use App\Services\WorkOrderExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API REST de órdenes de trabajo (recurso /api/v1/work-orders).
 *
 * Dos audiencias sobre el mismo recurso, diferenciadas por middleware de rol:
 *  - Alcaldía (superadmin): asigna/reasigna la orden a un contratista.
 *  - Contratista: consulta su agenda, inicia la ejecución y la cierra con
 *    evidencia fotográfica.
 *
 * Los cambios de estado del reporte y de la orden SIEMPRE se persisten juntos
 * dentro de DB::transaction: el par (reports.status, work_orders.status) es el
 * invariante del flujo y nunca puede quedar inconsistente.
 */
final class WorkOrderController extends Controller
{
    public function __construct(
        private readonly WorkOrderDispatcher $dispatcher,
        private readonly WorkOrderExecution $execution,
    ) {}

    /**
     * POST /api/v1/work-orders/assign — Solo Superadmin (Alcaldía).
     *
     * Crea la orden en reportes nuevos o reasigna la existente en retrabajos.
     * Las reglas de elegibilidad (estado del reporte, contratista activo) son
     * sede de WorkOrderDispatcher, compartida con el despacho Blade.
     */
    public function assign(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'report_id' => ['required', 'integer', 'exists:reports,id'],
            'contractor_id' => ['required', 'integer'],
            'scheduled_date' => ['required', 'date'],
        ]);

        $report = Report::query()->find((int) $payload['report_id']);

        if ($report === null) {
            return response()->json([
                'message' => 'El reporte indicado no existe.',
            ], 404);
        }

        $contractor = User::query()->find((int) $payload['contractor_id']);

        if ($contractor === null) {
            return response()->json([
                'message' => 'El usuario indicado no existe o no es un contratista activo.',
            ], 422);
        }

        try {
            $order = $this->dispatcher->dispatch(
                $report,
                $contractor,
                $payload['scheduled_date'],
            );
        } catch (WorkAssignmentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $order->load(['report.category', 'contractor']);

        return response()->json([
            'message' => $order->wasRecentlyCreated
                ? "Orden de trabajo asignada al contratista {$contractor->name}."
                : "Orden reasignada al contratista {$contractor->name} con nueva fecha programada.",
            'data' => (new WorkOrderResource($order))->resolve($request),
        ], $order->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * GET /api/v1/work-orders — Solo Contratista: su agenda paginada.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $orders = $request->user()
            ->workOrders()
            ->with('report.category')
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->paginate($perPage);

        return WorkOrderResource::collection($orders)
            ->additional([
                'meta' => ['total_pending' => $request->user()->workOrders()->count()],
            ])
            ->toResponse($request);
    }

    /**
     * PATCH /api/v1/work-orders/{workOrder}/start — Solo Contratista asignado.
     *
     * La máquina de estados es sede de WorkOrderExecution (compartida con el
     * portal Blade); aquí solo se traduce a HTTP.
     */
    public function startWork(Request $request, int $workOrder): JsonResponse
    {
        $order = $this->findOwnedOrder($request, $workOrder);

        if ($order instanceof JsonResponse) {
            return $order;
        }

        try {
            $this->execution->start($order);
        } catch (WorkExecutionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // wasChanged() distingue el arranque real del reintento idempotente
        // (start() no toca la orden si ya estaba en ejecución).
        if (! $order->wasChanged('status')) {
            return response()->json([
                'message' => 'Esta orden ya se encuentra en ejecución.',
            ]);
        }

        $order->load('report');

        return response()->json([
            'message' => 'Ejecución iniciada. Registra la evidencia al terminar la obra.',
            'data' => (new WorkOrderResource($order))->resolve($request),
        ]);
    }

    /**
     * POST /api/v1/work-orders/{workOrder}/complete — Solo Contratista asignado.
     *
     * La autorización (rol activo + propiedad de la orden, IDOR) la resuelve
     * CompleteWorkOrderRequest::authorize() antes de ejecutar este método.
     */
    public function completeWork(CompleteWorkOrderRequest $request, int $workOrder): JsonResponse
    {
        $order = WorkOrder::query()->with('report')->find($workOrder);

        // Inalcanzable en la práctica: authorize() ya verificó que la orden
        // existe y es del contratista. Red de seguridad ante carrera de borrado.
        if ($order === null || $order->report === null) {
            return response()->json([
                'message' => 'La orden de trabajo no existe o su reporte fue eliminado.',
            ], 404);
        }

        try {
            $order = $this->execution->complete(
                $order,
                $request->validated('work_description'),
                $request->validated('solution_photo'),
            );
        } catch (WorkExecutionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $report = $order->report;

        $order->load(['report.category', 'contractor']);

        return response()->json([
            'message' => "Obra terminada. La incidencia {$report->ticket_code} pasa a inspección de calidad.",
            'data' => (new WorkOrderResource($order))->resolve($request),
        ]);
    }

    /**
     * Búsqueda acotada a las órdenes del contratista autenticado: si la id
     * pertenece a otro usuario responde 404 (no 403) para no revelar la
     * existencia de recursos ajenos ni habilitar enumeración de IDs.
     *
     * @return WorkOrder|JsonResponse
     */
    private function findOwnedOrder(Request $request, int $orderId): WorkOrder|JsonResponse
    {
        $order = $request->user()
            ->workOrders()
            ->whereKey($orderId)
            ->first();

        if ($order === null) {
            return response()->json([
                'message' => 'No existe una orden de trabajo asignada a ti con esa identificación.',
            ], 404);
        }

        return $order;
    }

    /**
     * Cota de recursos: la API nunca sirve páginas arbitrariamente grandes.
     */
    private function resolvePerPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 50);
    }
}
