<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\InspectionDecision;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Exceptions\WorkAssignmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignWorkOrderRequest;
use App\Http\Requests\StoreContractorRequest;
use App\Models\Department;
use App\Models\QualityInspection;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Panel de Alcaldía (superadmin): pulso operativo del municipio, despacho
 * de incidencias a cuadrillas y gestión de contratistas.
 */
final class AdminController extends Controller
{
    public function __construct(
        private readonly WorkOrderDispatcher $dispatcher,
    ) {}

    public function dashboard(): View
    {
        $stats = [
            'open' => Report::query()->where('status', '!=', ReportStatus::Closed->value)->count(),
            'in_progress' => Report::query()->status(ReportStatus::InProgress)->count(),
            'under_inspection' => Report::query()->status(ReportStatus::UnderInspection)->count(),
            'rework' => Report::query()->status(ReportStatus::Rework)->count(),
            'closed_today' => Report::query()
                ->status(ReportStatus::Closed)
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
            'orders_pending' => WorkOrder::query()
                ->where('status', WorkOrderStatus::Assigned->value)
                ->count(),
            'contractors' => User::query()
                ->where('role', UserRole::Contractor->value)
                ->where('is_active', true)
                ->count(),
        ];

        $recent = Report::query()
            ->with(['category:id,name', 'workOrder:id,report_id,status,contractor_id'])
            ->latest()
            ->limit(10)
            ->get();

        // Inyección para los modales "Asignar Cuadrilla" del dashboard:
        // solo cuadrillas activas (la elegibilidad de fondo la re-valida
        // WorkOrderDispatcher al enviar, ante carreras entre pantallas).
        $contractors = $this->assignableContractors();

        $dictamenes = [
            'total' => QualityInspection::query()->count(),
            'rework' => QualityInspection::query()
                ->where('decision', InspectionDecision::Rework->value)
                ->count(),
        ];

        return view('admin.dashboard', compact('stats', 'recent', 'dictamenes', 'contractors'));
    }

    /**
     * POST /admin/incidencias/{report}/asignar — despacho desde el modal
     * del dashboard o del detalle de la incidencia.
     */
    public function assign(AssignWorkOrderRequest $request, Report $report): RedirectResponse
    {
        /** @var User $contractor */
        $contractor = User::query()->findOrFail((int) $request->validated('contractor_id'));

        try {
            $order = $this->dispatcher->dispatch(
                $report,
                $contractor,
                $request->validated('scheduled_date'),
                $request->validated('instructions'),
            );
        } catch (WorkAssignmentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $fecha = $order->scheduled_date->toDateString();

        return back()->with('status', $order->wasRecentlyCreated
            ? "Incidencia {$report->ticket_code} asignada a {$contractor->name} · ejecución programada para el {$fecha}."
            : "Obra de {$report->ticket_code} reasignada a {$contractor->name} con nueva fecha {$fecha}.");
    }

    /**
     * GET /admin/contratistas — módulo de gestión de cuadrillas.
     */
    public function contractors(): View
    {
        $contractors = User::query()
            ->where('role', UserRole::Contractor->value)
            ->with('department:id,name')
            ->withCount(['workOrders as active_orders_count' => fn ($q) => $q->whereIn('status', [
                WorkOrderStatus::Assigned->value,
                WorkOrderStatus::InExecution->value,
            ])])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.contractors', compact('contractors', 'departments'));
    }

    /**
     * POST /admin/contratistas — alta de cuadrilla con rol contractor
     * e is_active fijos por el servidor (el cliente no elige su rol).
     */
    public function storeContractor(StoreContractorRequest $request): RedirectResponse
    {
        $contractor = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'), // mutator 'hashed' del modelo
            'department_id' => (int) $request->validated('department_id'),
            'role' => UserRole::Contractor,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.contractors')
            ->with('status', "Cuadrilla «{$contractor->name}» registrada y habilitada para recibir despachos.");
    }

    /**
     * Cuadrillas elegibles para el selector de asignación.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function assignableContractors(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('role', UserRole::Contractor->value)
            ->where('is_active', true)
            ->with('department:id,name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Panel de analítica y métricas ejecutivas para la Alcaldía.
     */
    public function analytics(\Illuminate\Http\Request $request): View
    {
        // Filtros
        $period = $request->get('period', 'month'); // week, month, year
        $departmentId = $request->get('department_id');

        // Calcular rango de fechas según período
        $dateRange = $this->getDateRange($period);

        // Construir query base con filtros
        $reportsQuery = Report::query();
        if ($departmentId) {
            $reportsQuery->whereHas('category', fn($q) => $q->where('department_id', $departmentId));
        }

        // KPIs Principales
        $kpis = $this->calculateKPIs($reportsQuery, $dateRange);

        // Datos para gráficos
        $charts = $this->getChartData($reportsQuery, $dateRange);

        // Rendimiento de contratistas
        $contractorPerformance = $this->getContractorPerformance($dateRange, $departmentId);

        // Departamentos para filtro
        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.analytics', compact(
            'kpis',
            'charts',
            'contractorPerformance',
            'departments',
            'period',
            'departmentId'
        ));
    }

    /**
     * Calcula rango de fechas según período seleccionado
     */
    private function getDateRange(string $period): array
    {
        $now = now();

        return match($period) {
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'prev_start' => $now->copy()->subWeek()->startOfWeek(),
                'prev_end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'prev_start' => $now->copy()->subYear()->startOfYear(),
                'prev_end' => $now->copy()->subYear()->endOfYear(),
            ],
            default => [ // month
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'prev_start' => $now->copy()->subMonth()->startOfMonth(),
                'prev_end' => $now->copy()->subMonth()->endOfMonth(),
            ]
        };
    }

    /**
     * Calcula KPIs principales
     */
    private function calculateKPIs($baseQuery, array $dateRange): array
    {
        // Total reportes mes actual vs anterior
        $currentTotal = (clone $baseQuery)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $previousTotal = (clone $baseQuery)
            ->whereBetween('created_at', [$dateRange['prev_start'], $dateRange['prev_end']])
            ->count();

        $variation = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1)
            : 0;

        // Tiempo promedio de resolución (horas desde reported hasta closed)
        $avgResolutionHours = (clone $baseQuery)
            ->where('status', ReportStatus::Closed->value)
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->avg(function($report) {
                return $report->created_at->diffInHours($report->updated_at);
            });

        // Tasa de aprobación en primer intento
        $totalInspections = QualityInspection::query()
            ->whereHas('workOrder.report', function($q) use ($baseQuery, $dateRange) {
                $q->mergeConstraintsFrom($baseQuery->getQuery());
            })
            ->whereBetween('inspected_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $approvedFirstTime = QualityInspection::query()
            ->where('decision', InspectionDecision::Approved->value)
            ->whereHas('workOrder.report', function($q) use ($baseQuery, $dateRange) {
                $q->mergeConstraintsFrom($baseQuery->getQuery());
            })
            ->whereBetween('inspected_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $approvalRate = $totalInspections > 0
            ? round(($approvedFirstTime / $totalInspections) * 100, 1)
            : 0;

        // Cumplimiento de SLA
        $slaCompliance = $this->calculateSLACompliance($baseQuery, $dateRange);

        return [
            'total_reports' => $currentTotal,
            'previous_reports' => $previousTotal,
            'variation' => $variation,
            'avg_resolution_hours' => $avgResolutionHours ? round($avgResolutionHours, 1) : 0,
            'approval_rate' => $approvalRate,
            'sla_compliance' => $slaCompliance,
        ];
    }

    /**
     * Calcula porcentaje de cumplimiento de SLA
     */
    private function calculateSLACompliance($baseQuery, array $dateRange): float
    {
        $closedReports = (clone $baseQuery)
            ->where('status', ReportStatus::Closed->value)
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->with('category')
            ->get();

        if ($closedReports->isEmpty()) {
            return 0;
        }

        $withinSLA = $closedReports->filter(function($report) {
            $slaHours = $report->category->sla_hours ?? 0;
            if ($slaHours === 0) return false;

            $resolutionHours = $report->created_at->diffInHours($report->updated_at);
            return $resolutionHours <= $slaHours;
        })->count();

        return round(($withinSLA / $closedReports->count()) * 100, 1);
    }

    /**
     * Obtiene datos para gráficos
     */
    private function getChartData($baseQuery, array $dateRange): array
    {
        // Distribución por categoría
        $byCategory = (clone $baseQuery)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->join('categories', 'reports.category_id', '=', 'categories.id')
            ->select('categories.name', \DB::raw('count(*) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Distribución por departamento
        $byDepartment = (clone $baseQuery)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->join('categories', 'reports.category_id', '=', 'categories.id')
            ->join('departments', 'categories.department_id', '=', 'departments.id')
            ->select('departments.name', \DB::raw('count(*) as total'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get();

        // Evolución semanal (últimas 8 semanas)
        $weeklyTrend = [];
        $received = [];
        $resolved = [];
        $labels = [];

        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $labels[] = 'Sem ' . $weekStart->format('d/m');

            $received[] = (clone $baseQuery)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();

            $resolved[] = (clone $baseQuery)
                ->where('status', ReportStatus::Closed->value)
                ->whereBetween('updated_at', [$weekStart, $weekEnd])
                ->count();
        }

        return [
            'by_category' => [
                'labels' => $byCategory->pluck('name')->toArray(),
                'data' => $byCategory->pluck('total')->toArray(),
            ],
            'by_department' => [
                'labels' => $byDepartment->pluck('name')->toArray(),
                'data' => $byDepartment->pluck('total')->toArray(),
            ],
            'weekly_trend' => [
                'labels' => $labels,
                'received' => $received,
                'resolved' => $resolved,
            ],
        ];
    }

    /**
     * Obtiene rendimiento por contratista
     */
    private function getContractorPerformance(array $dateRange, ?int $departmentId): \Illuminate\Support\Collection
    {
        $contractors = User::query()
            ->where('role', UserRole::Contractor->value)
            ->where('is_active', true);

        if ($departmentId) {
            $contractors->where('department_id', $departmentId);
        }

        return $contractors->get()->map(function($contractor) use ($dateRange) {
            $workOrders = WorkOrder::query()
                ->where('contractor_id', $contractor->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->with(['report.category', 'qualityInspections'])
                ->get();

            $assigned = $workOrders->count();
            $completed = $workOrders->where('status', WorkOrderStatus::Completed->value)->count();

            // Completadas a tiempo (dentro del SLA de la categoría)
            $onTime = $workOrders->filter(function($wo) {
                if (!$wo->completion_date || !$wo->report) return false;

                $slaHours = $wo->report->category->sla_hours ?? 0;
                if ($slaHours === 0) return false;

                $hours = $wo->report->created_at->diffInHours($wo->completion_date);
                return $hours <= $slaHours;
            })->count();

            // Tasa de rechazo en inspección
            $totalInspections = $workOrders->sum(fn($wo) => $wo->qualityInspections->count());
            $rejections = $workOrders->sum(function($wo) {
                return $wo->qualityInspections->where('decision', InspectionDecision::Rework->value)->count();
            });

            $rejectionRate = $totalInspections > 0
                ? round(($rejections / $totalInspections) * 100, 1)
                : 0;

            return [
                'name' => $contractor->name,
                'assigned' => $assigned,
                'completed' => $completed,
                'on_time' => $onTime,
                'rejection_rate' => $rejectionRate,
            ];
        });
    }
}
