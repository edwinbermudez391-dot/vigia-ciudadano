<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQualityInspectionRequest;
use App\Models\Report;
use App\Models\WorkOrder;
use App\Services\QualityInspectionRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
    /**
     * Checklist del dictamen (clave técnica => enunciado visible).
     *
     * @var array<string, string>
     */
    private const CHECKLIST = [
        'superficie_uniforme' => 'Superficie uniforme y correctamente compactada',
        'pendiente_drenaje' => 'Pendiente adecuada para el drenaje de aguas',
        'area_senalizada' => 'Área de trabajo señalizada y libre de escombros',
        'materiales_especificados' => 'Materiales acordes a la especificación técnica',
        'sin_afectacion_residual' => 'Sin afectación vehicular o peatonal residual',
    ];

    public function __construct(
        private readonly QualityInspectionRecorder $recorder,
    ) {}

    public function queue(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 12), 1), 50);

        $reports = Report::query()
            ->with(['category', 'workOrder.contractor', 'workOrder.qualityInspections'])
            ->status(ReportStatus::UnderInspection)
            ->orderBy('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('inspector.queue', compact('reports'));
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
            'checklist' => self::CHECKLIST,
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
