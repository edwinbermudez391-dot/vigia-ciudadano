<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportIntake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/**
 * Portal ciudadano (SSR): alta de reportes y trazabilidad por ticket.
 *
 * Reutiliza el StoreReportRequest de la API (mismas reglas de negocio y
 * validación de fotos por contenido real) y el servicio ReportIntake que
 * comparten ambos canales. El mapa interactivo y la alerta de reportes
 * cercanos se resuelven en el navegador contra /api/v1 (Sanctum, primera
 * parte); el envío del formulario es POST clásico con multipart.
 *
 * Flujo público: ciudadanos sin cuenta pueden radicar reportes. Si están
 * autenticados, se vincula el reporte a su cuenta; si son invitados, se
 * registran los datos de contacto opcionales.
 */
final class ReportController extends Controller
{
    private const DEVICE_COOKIE = 'vigia_device_reports';

    public function __construct(
        private readonly ReportIntake $intake,
    ) {}

    public function create(Request $request): View
    {
        if ($this->deviceToken($request) === null) {
            $this->rememberDevice($request, bin2hex(random_bytes(32)));
        }

        $categories = Category::query()
            ->with('department')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reports.create', compact('categories'));
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        /** @var UploadedFile|null $photo */
        $photo = $request->validated('damage_photo');

        // Si el usuario está autenticado, vincula el reporte a su cuenta.
        // Si es invitado, user_id será null y se guardan los datos de contacto.
        $data = $request->validated();
        $data['address_reference'] = mb_substr(implode(' · ', array_filter([
            $data['location_label'] ?? '', $data['address_reference'] ?? '',
        ])), 0, 255);
        $token = $this->deviceToken($request) ?? bin2hex(random_bytes(32));
        $report = $this->intake->create($data, $photo, $request->user(), hash('sha256', $token));
        if (! $report->wasRecentlyCreated) {
            return redirect()->route('reports.create')
                ->withInput()
                ->with('duplicate_report_ticket', $report->ticket_code);
        }

        $this->rememberDevice($request, $token);

        // Redirige a la vista de éxito con el ticket en sesión flash.
        return redirect()
            ->route('reports.success', ['ticket_code' => $report->ticket_code])
            ->with('device_report_ticket', $report->ticket_code);
    }

    public function device(Request $request): Response
    {
        $token = $this->deviceToken($request);
        $reports = $token === null ? collect() : Report::query()
            ->where('device_token_hash', hash('sha256', $token))
            ->latest('created_at')
            ->orderByDesc('id')
            ->get(['ticket_code', 'created_at']);

        if ($token !== null && $reports->isNotEmpty()) {
            $this->rememberDevice($request, $token);
        }

        return response()->view('reports.device', compact('reports'), headers: ['Cache-Control' => 'private, no-store']);
    }

    private function deviceToken(Request $request): ?string
    {
        $token = $request->cookie(self::DEVICE_COOKIE);

        return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1 ? $token : null;
    }

    private function rememberDevice(Request $request, string $token): void
    {
        Cookie::queue(Cookie::forever(
            self::DEVICE_COOKIE,
            $token,
            path: $request->getBasePath().'/reportes',
            secure: $request->isSecure() || (bool) config('session.secure'),
            sameSite: 'lax',
        ));
    }

    /**
     * Vista de confirmación tras radicar exitosamente.
     * Muestra el ticket en grande con opciones para copiarlo y ver el seguimiento.
     */
    public function success(string $ticket_code): View
    {
        $report = Report::query()
            ->with('category.department')
            ->where('ticket_code', $ticket_code)
            ->first();

        if ($report === null) {
            abort(404, 'No existe un reporte con ese código de radicado.');
        }

        return view('reports.success', compact('report'));
    }

    public function show(string $ticketCode): View
    {
        $report = Report::query()
            ->with([
                'category.department',
                'user',
                'workOrder.contractor',
                'workOrder.qualityInspections.inspector',
                'subscribers',
            ])
            ->where('ticket_code', $ticketCode)
            ->first();

        if ($report === null) {
            abort(404, 'No existe un reporte con ese código de radicado.');
        }

        $userId = auth()->id();

        // Datos del despacho (solo Alcaldía, solo cuando el ciclo de vida lo
        // permite): evita una consulta de cuadrillas en cada vista ciudadana.
        $assignable = auth()->user()?->isSuperAdmin() === true
            && in_array($report->status, [ReportStatus::Reported, ReportStatus::Rework], true);

        $contractors = $assignable
            ? User::query()
                ->where('role', UserRole::Contractor->value)
                ->where('is_active', true)
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
            : collect();

        return view('reports.show', [
            'report' => $report,
            'steps' => $this->timeline($report),
            'subscribed' => $report->subscribers->contains('id', $userId),
            'workOrder' => $report->workOrder,
            'latestInspection' => $report->workOrder
                ?->qualityInspections
                ?->sortByDesc('inspected_at')
                ?->first(),
            'userId' => $userId,
            'assignable' => $assignable,
            'contractors' => $contractors,
        ]);
    }

    /**
     * Línea de tiempo cronológica derivada del estado del dominio.
     *
     * @return list<array{title: string, detail: string, at: ?\Illuminate\Support\Carbon, done: bool}>
     */
    private function timeline(Report $report): array
    {
        $steps = [[
            'title' => 'Reporte radado',
            'detail' => 'Radicado por '.($report->user?->name ?? $report->citizen_name ?? 'ciudadano'),
            'at' => $report->created_at,
            'done' => true,
        ]];

        $order = $report->workOrder;

        if ($order === null) {
            $steps[] = [
                'title' => 'Asignación a contratista',
                'detail' => 'En espera de que Alcaldía asigne la obra',
                'at' => null,
                'done' => false,
            ];

            return $steps;
        }

        $steps[] = [
            'title' => 'Orden de trabajo asignada',
            'detail' => 'Contratista: '.$order->contractor->name
                .' · fecha programada '.$order->scheduled_date?->toDateString(),
            'at' => $order->created_at,
            'done' => true,
        ];

        if ($order->completion_date !== null && $order->status !== WorkOrderStatus::InExecution) {
            $steps[] = [
                'title' => 'Obra ejecutada por el contratista',
                'detail' => (string) $order->work_description,
                'at' => $order->completion_date,
                'done' => true,
            ];
        } else {
            $steps[] = [
                'title' => $order->status === WorkOrderStatus::InExecution ? 'Obra en ejecución' : 'Ejecución de la obra',
                'detail' => $order->status === WorkOrderStatus::Assigned
                    ? 'Pendiente que el contratista inicie'
                    : 'Trabajos en curso',
                'at' => null,
                'done' => false,
                'active' => $order->status === WorkOrderStatus::InExecution,
            ];
        }

        foreach ($order->qualityInspections->sortBy('inspected_at') as $inspection) {
            $approved = $inspection->decision === InspectionDecision::Approved;

            $steps[] = [
                'title' => $approved ? 'Inspección: obra aprobada' : 'Inspección: obra rechazada',
                'detail' => $approved
                    ? 'Validada por '.$inspection->inspector->name
                    : 'Retrabajo exigido: '.$inspection->rejection_reason,
                'at' => $inspection->inspected_at,
                'done' => true,
            ];
        }

        $steps[] = [
            'title' => 'Incidencia cerrada',
            'detail' => $report->status === ReportStatus::Closed
                    ? 'Obra verificada en campo por control de calidad'
                    : 'Pendiente de dictamen del inspector',
            'at' => $report->status === ReportStatus::Closed
                ? $order->qualityInspections->sortByDesc('inspected_at')->first()?->inspected_at
                : null,
            'done' => $report->status === ReportStatus::Closed,
        ];

        return $steps;
    }
}
