<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQualityInspectionRequest;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\QualityInspectionRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Consola del inspector de calidad (SSR): cola de obras pendientes del
 * dictamen y formulario de auditoría con checklist táctil.
 *
 * Las transiciones de negocio viven en QualityInspectionRecorder (mismo
 * servicio que consume la API móvil); aquí solo guardia de estados + render.
 */
final class InspectorController extends Controller
{
    public function __construct(
        private readonly QualityInspectionRecorder $recorder,
    ) {}

    public function queue(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 12), 1), 50);
        $filters = $request->validate([
            'cuadrilla' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Contractor->value)],
        ]);
        $crewId = isset($filters['cuadrilla']) ? (int) $filters['cuadrilla'] : null;
        $contractors = User::query()->where('role', UserRole::Contractor)
            ->orderBy('name')->orderBy('id')->get(['id', 'name']);

        $queue = Report::query()
            ->when($crewId, fn ($query) => $query->whereRelation('workOrder', 'contractor_id', $crewId))
            ->with(['category', 'workOrder.contractor']);
        $pagination = $request->only(['cuadrilla', 'per_page', 'page', 'page_cerrados']);
        $pending = (clone $queue)->status(ReportStatus::UnderInspection)
            ->with(['workOrder' => fn ($query) => $query->withCount('qualityInspections')])
            ->orderBy('updated_at')->orderBy('id')
            ->paginate($perPage)->appends($pagination)->fragment('pendientes');
        $closedReports = $queue->status(ReportStatus::Closed)
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate($perPage, pageName: 'page_cerrados')->appends($pagination)->fragment('cerrados');

        return view('inspector.queue', compact('pending', 'closedReports', 'crewId', 'contractors'));
    }

    public function audit(int $workOrder): View|RedirectResponse
    {
        $order = $this->findAuditableOrder($workOrder);

        if ($order instanceof RedirectResponse) {
            return $order;
        }

        return view('inspector.audit', [
            'order' => $order,
            'report' => $order->report,
            'checklist' => \App\Support\InspectionChecklist::forCategory($order->report->category?->name),
        ]);
    }

    public function store(StoreQualityInspectionRequest $request, int $workOrder): RedirectResponse
    {
        $order = $this->findAuditableOrder($workOrder);

        if ($order instanceof RedirectResponse) {
            return $order;
        }

        $report = $order->report;

        $decision = InspectionDecision::tryFrom((string) $request->validated('decision'));

        if ($decision === null) {
            return back()->with('error', 'La decisión de dictamen no es válida.');
        }

        /** @var UploadedFile $photo */
        $photo = $request->validated('verification_photo');

        $this->recorder->record(
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

        return redirect()
            ->route('reports.show', $report->ticket_code)
            ->with('status', $decision === InspectionDecision::Approved
                ? "Dictamen registrado: la obra de {$report->ticket_code} fue APROBADA y la incidencia quedó cerrada."
                : "Dictamen registrado: {$report->ticket_code} pasó a retrabajo y la orden volvió a ejecución.");
    }

    /**
     * Orden + reporte auditable, o redirect con causa legible. Early return
     * para que audit()/store() no repliquen la guardia de estados.
     */
    private function findAuditableOrder(int $workOrder): WorkOrder|RedirectResponse
    {
        /** @var WorkOrder|null $order */
        $order = WorkOrder::query()
            ->with(['report.category', 'contractor'])
            ->find($workOrder);

        if ($order === null || $order->report === null) {
            return redirect()
                ->route('inspector.queue')
                ->with('error', 'La orden de trabajo solicitada no existe.');
        }

        if ($order->status !== WorkOrderStatus::Completed
            || $order->report->status !== ReportStatus::UnderInspection
        ) {
            return redirect()
                ->route('inspector.queue')
                ->with('error', "La orden #{$order->id} no está pendiente de dictamen "
                    .'(solo obras terminadas bajo inspección).');
        }

        return $order;
    }
}
