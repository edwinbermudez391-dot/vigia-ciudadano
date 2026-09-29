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
}
