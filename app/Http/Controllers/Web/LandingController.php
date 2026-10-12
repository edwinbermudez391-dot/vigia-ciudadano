<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\QualityInspection;
use App\Models\Report;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Landing pública "/" — presentación institucional del programa Vigía
 * Ciudadano con indicadores reales del pulso municipal.
 *
 * Un usuario con sesión no ve la landing: vuela directo a su superficie
 * de trabajo (AuthController::homeFor), igual que antes de existir la
 * página pública.
 */
final class LandingController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect()->to(AuthController::homeFor($user));
        }

        return view('welcome', $this->publicMetrics());
    }

    /**
     * Métricas públicas: solo agregados, jamás datos personales ni de
     * cuentas (la página no exige autenticación).
     *
     * @return array{stats: array<string, int|float|null>, trend: array{labels: list<string>, created: list<int>, closed: list<int>}}
     */
    private function publicMetrics(): array
    {
        return [
            'stats' => $this->stats(),
            'trend' => $this->weeklyTrend(),
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    private function stats(): array
    {
        $inspectionsTotal = QualityInspection::query()->count();
        $inspectionsApproved = QualityInspection::query()
            ->where('decision', InspectionDecision::Approved->value)
            ->count();

        // SLA ≈ días entre reporte y cierre de las incidencias cerradas.
        // Cálculo en PHP (no SQL) para ser compatible con SQLite y MySQL.
        // No existe columna closed_at: updated_at es el sello del cierre,
        // el mismo criterio que AdminController::dashboard().
        $closedReports = Report::query()
            ->where('status', ReportStatus::Closed->value)
            ->get(['created_at', 'updated_at']);

        $slaDays = $closedReports->isNotEmpty()
            ? $closedReports->avg(fn (Report $r) => $r->created_at->diffInHours($r->updated_at) / 24)
            : null;

        return [
            'active' => Report::query()
                ->where('status', '!=', ReportStatus::Closed->value)
                ->count(),
            'orders_executing' => WorkOrder::query()
                ->whereIn('status', [
                    WorkOrderStatus::Assigned->value,
                    WorkOrderStatus::InExecution->value,
                ])
                ->count(),
            'approval_rate' => $inspectionsTotal > 0
                ? round(($inspectionsApproved / $inspectionsTotal) * 100)
                : null,
            'sla_days' => $slaDays !== null ? round((float) $slaDays, 1) : null,
        ];
    }

    /**
     * Serie de los últimos 7 días para el gráfico de gestión: reportes
     * radicados vs. cerrados por día. Doce consultas de conteo trivial
     * sobre una tabla municipal: la legibilidad manda sobre micro-optimizar.
     *
     * @return array{labels: list<string>, created: list<int>, closed: list<int>}
     */
    private function weeklyTrend(): array
    {
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $labels = $created = $closed = [];

        for ($i = 6; $i >= 0; $i--) {
            /** @var Carbon $day */
            $day = today()->subDays($i);
            $inicio = $day->copy()->startOfDay();
            $fin = $day->copy()->endOfDay();

            $labels[] = $dias[$day->dayOfWeek];

            $created[] = Report::query()
                ->whereBetween('created_at', [$inicio, $fin])
                ->count();

            $closed[] = Report::query()
                ->where('status', ReportStatus::Closed->value)
                ->whereBetween('updated_at', [$inicio, $fin])
                ->count();
        }

        return ['labels' => $labels, 'created' => $created, 'closed' => $closed];
    }
}
