<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\WorkOrderStatus;
use App\Exceptions\WorkExecutionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteWorkOrderRequest;
use App\Models\WorkOrder;
use App\Services\WorkOrderExecution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

/**
 * Panel del contratista: agenda de órdenes y ejecución en sitio (iniciar
 * obra / finalizar con evidencia fotográfica).
 *
 * La máquina de estados es sede única de WorkOrderDispatcher-equivalente:
 * WorkOrderExecution, compartida con la API de campo. Aquí solo se traduce
 * a HTTP con redirecciones limpias y mensajes de sesión.
 *
 * IDOR (defense-in-depth): el middleware 'role:contractor' acota el rol; la
 * propiedad de la orden se re-verifica SIEMPRE con una búsqueda acotada a
 * workOrders() del usuario autenticado — una id ajena responde 404 (no 403)
 * para no habilitar enumeración de recursos.
 */
final class ContractorController extends Controller
{
    public function __construct(
        private readonly WorkOrderExecution $execution,
    ) {}

    public function orders(Request $request): View
    {
        $orders = WorkOrder::query()
            ->where('contractor_id', $request->user()->id);
        $totals = (clone $orders)->toBase()->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')->pluck('total', 'status');
        $perPage = 15;
        $groups = [];
        foreach (WorkOrderStatus::cases() as $status) {
            $count = (int) $totals->get($status->value, 0);
            if ($count === 0) {
                continue;
            }

            $pageName = 'page_'.$status->value;
            $page = min(Paginator::resolveCurrentPage($pageName), (int) ceil($count / $perPage));
            $groups[$status->value] = (clone $orders)->where('status', $status)
                ->with(['report:id,ticket_code,title,address_reference,category_id', 'report.category:id,name'])
                ->when($status === WorkOrderStatus::Assigned,
                    fn ($query) => $query->orderByRaw('COALESCE(assigned_at, created_at)'),
                    fn ($query) => $query->orderBy('scheduled_date'))
                ->orderBy('id')
                ->paginate($perPage, pageName: $pageName, page: $page, total: $count)
                ->withQueryString()
                ->appends('grupo', $status->value)
                ->fragment('ordenes-'.$status->value);
        }

        $requestedGroup = $request->query('grupo');
        $openStatus = is_string($requestedGroup) && isset($groups[$requestedGroup]) ? $requestedGroup : array_key_first($groups);
        $totalOrders = $totals->sum();

        return view('contractor.orders', compact('groups', 'openStatus', 'totalOrders'));
    }

    /**
     * PATCH /ordenes/{workOrder}/iniciar — marca el inicio de obra en sitio.
     */
    public function startWork(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $owned = $this->ownedOrder($request, $workOrder);

        try {
            $this->execution->start($owned);
        } catch (WorkExecutionException $e) {
            return $this->backToReport($owned, 'error', $e->getMessage());
        }

        return $this->backToReport($owned, 'status', $owned->wasChanged('status')
            ? 'Obra iniciada. Cuando termines, sube la evidencia desde esta misma pantalla.'
            : 'Esta orden ya estaba en ejecución: continúa y sube la evidencia al finalizar.');
    }

    /**
     * POST /ordenes/{workOrder}/finalizar — evidencia + paso a inspección.
     *
     * CompleteWorkOrderRequest ya resolvió autorización (rol activo + IDOR)
     * y la validación de la foto por contenido real (finfo).
     */
    public function completeWork(CompleteWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $owned = $this->ownedOrder($request, $workOrder);

        if ($owned->report === null) {
            return back()->with('error', 'La orden de trabajo no existe o su reporte fue eliminado.');
        }

        try {
            $this->execution->complete(
                $owned,
                $request->validated('work_description'),
                $request->validated('solution_photo'),
            );
        } catch (WorkExecutionException $e) {
            return $this->backToReport($owned, 'error', $e->getMessage());
        }

        return $this->backToReport(
            $owned,
            'status',
            "Obra terminada. La incidencia {$owned->report->ticket_code} pasa a inspección de calidad. "
            .'Ya no puedes editarla: el panel queda bloqueado hasta el dictamen.',
        );
    }

    /**
     * Re-verificación IDOR: la ruta ya ligó el modelo, pero se confirma que
     * pertenece al usuario de la sesión. Ajena o inexistente -> 404.
     */
    private function ownedOrder(Request $request, WorkOrder $workOrder): WorkOrder
    {
        /** @var WorkOrder|null $owned */
        $owned = $request->user()
            ?->workOrders()
            ->with('report')
            ->whereKey($workOrder->getKey())
            ->first();

        abort_if($owned === null, 404, 'No existe una orden de trabajo asignada a ti con esa identificación.');

        return $owned;
    }

    /**
     * Redirección limpia: siempre al detalle del reporte afectado, donde la
     * cuadrilla ve el panel actualizado (y el flash que aporta el layout).
     */
    private function backToReport(WorkOrder $order, string $bag, string $message): RedirectResponse
    {
        if ($order->report === null) {
            return redirect()->route('contractor.orders')->with($bag, $message);
        }

        return redirect()
            ->route('reports.show', $order->report->ticket_code)
            ->with($bag, $message);
    }
}
