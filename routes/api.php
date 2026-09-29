<?php

declare(strict_types=1);

use App\Http\Controllers\CitizenReportController;
use App\Http\Controllers\Contractor\WorkOrderController;
use App\Http\Controllers\Inspector\QualityInspectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Vigía Ciudadano
|--------------------------------------------------------------------------
| auth:sanctum es indispensable en las rutas con usuario: sin él,
| $request->user() resuelve contra el guard por defecto y un token Bearer
| jamás autenticaría (los FormRequest rechazarían por authorize()).
|
| La autenticación de primera parte (cookie de sesión web) está habilitada
| vía EnsureFrontendRequestsAreStateful en el grupo 'api' — ver bootstrap/app.php.
*/


Route::prefix('v1')->name('api.v1.')->group(function (): void {
    // Consulta de detalle: ticket codes no enumerables sin autenticación.
    Route::get('reports/{ticket_code}', [CitizenReportController::class, 'show'])
        ->where('ticket_code', 'REP-\d{4}-\d{4,5}')
        ->name('reports.show')
        ->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('reports/nearby', [CitizenReportController::class, 'checkNearby'])
            ->name('reports.nearby');

        Route::post('reports', [CitizenReportController::class, 'store'])
            ->middleware('throttle:reports')   // ver AppServiceProvider
            ->name('reports.store');

        Route::post('reports/{id}/subscribe', [CitizenReportController::class, 'subscribe'])
            ->whereNumber('id')
            ->name('reports.subscribe');
    });

    /*
    |----------------------------------------------------------------------
    | Ciclo de vida de la incidencia: asignación -> ejecución -> inspección
    |----------------------------------------------------------------------
    | auth:sanctum + role:* (EnsureUserHasRole). Los Form Requests repiten
    | la comprobación de rol/propiedad en authorize(): dos capas independientes.
    */

    // Alcaldía: crea o reasigna la orden de trabajo de un reporte.
    Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function (): void {
        Route::post('work-orders/assign', [WorkOrderController::class, 'assign'])
            ->name('work-orders.assign');
    });

    // Contratista: agenda, inicio de ejecución y cierre con evidencia.
    // El parámetro {workOrder} se nombra así (no {id}) porque
    // CompleteWorkOrderRequest::ownsWorkOrder() lee route('workOrder') para
    // validar la propiedad; con otro nombre el authorize() denegarían todo.
    Route::middleware(['auth:sanctum', 'role:contractor'])
        ->prefix('work-orders')
        ->name('work-orders.')
        ->group(function (): void {
            Route::get('/', [WorkOrderController::class, 'myOrders'])
                ->name('index');

            Route::patch('{workOrder}/start', [WorkOrderController::class, 'startWork'])
                ->whereNumber('workOrder')
                ->name('start');

            Route::post('{workOrder}/complete', [WorkOrderController::class, 'completeWork'])
                ->whereNumber('workOrder')
                ->name('complete');
        });

    // Inspector: cola de inspecciones y decisión de calidad sobre la obra.
    Route::middleware(['auth:sanctum', 'role:quality_inspector'])->group(function (): void {
        Route::get('inspections/pending', [QualityInspectionController::class, 'pendingList'])
            ->name('inspections.pending');

        Route::post('work-orders/{workOrder}/inspections', [QualityInspectionController::class, 'storeInspection'])
            ->whereNumber('workOrder')
            ->name('work-orders.inspections.store');
    });
});
