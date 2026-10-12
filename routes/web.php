<?php

declare(strict_types=1);

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ContractorController;
use App\Http\Controllers\Web\InspectorController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web — Vigía Ciudadano (Blade SSR)
|--------------------------------------------------------------------------
| Las páginas reutilizan el pipeline de la API (mismos FormRequests y
| Services); el JS de cliente (mapa, geolocalización, alerta de cercanos)
| consume /api/v1 con la cookie de sesión vía auth de primera parte de
| Sanctum — ver EnsureFrontendRequestsAreStateful en bootstrap/app.php.
|
| Flujo público: ciudadanos sin cuenta pueden radicar reportes y consultar
| su trazabilidad. Las rutas de reportes están fuera del middleware auth.
*/

// Aterrizaje: invitados ven la landing institucional; usuarios con sesión
// van directo a su superficie de trabajo (AuthController::homeFor).
Route::get('/', LandingController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');

    // Throttle contra brute force de credenciales.
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.attempt');
});

// ========================================================================
// RUTAS PÚBLICAS: Radicación y consulta de reportes (sin autenticación)
// ========================================================================
// Cualquier ciudadano puede radicar incidencias sin cuenta. Si está
// autenticado, se vincula a su cuenta; si es invitado, se registran los
// datos de contacto opcionales (citizen_name, citizen_phone, citizen_email).
Route::prefix('reportes')->name('reports.')->group(function (): void {
    Route::get('crear', [ReportController::class, 'create'])
        ->name('create');

    Route::post('/', [ReportController::class, 'store'])
        ->middleware('throttle:reports') // mismo rate limiter que la API
        ->name('store');

    Route::get('exito/{ticket_code}', [ReportController::class, 'success'])
        ->where('ticket_code', 'REP-\d{4}-\d{4,5}')
        ->name('success');

    Route::get('{ticket_code}', [ReportController::class, 'show'])
        ->where('ticket_code', 'REP-\d{4}-\d{4,5}')
        ->name('show');
});

// Mapa interactivo público: visualización geográfica de todos los reportes.
Route::get('mapa', [\App\Http\Controllers\Web\MapController::class, '__invoke'])
    ->name('map');

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Contratista: agenda de órdenes + ejecución en sitio (iniciar/finalizar).
    // La propiedad de la orden (IDOR) se re-verifica en el controller y en
    // CompleteWorkOrderRequest::authorize(); el middleware role es la 1ª capa.
    Route::middleware('role:contractor')->group(function (): void {
        Route::get('ordenes', [ContractorController::class, 'orders'])
            ->name('contractor.orders');

        Route::patch('ordenes/{workOrder}/iniciar', [ContractorController::class, 'startWork'])
            ->whereNumber('workOrder')
            ->name('contractor.startWork');

        Route::post('ordenes/{workOrder}/finalizar', [ContractorController::class, 'completeWork'])
            ->whereNumber('workOrder')
            ->name('contractor.completeWork');
    });

    // Inspector: cola de dictámenes + formulario de auditoría táctil.
    Route::middleware('role:quality_inspector')
        ->prefix('inspector')
        ->name('inspector.')
        ->group(function (): void {
            Route::get('inspecciones', [InspectorController::class, 'queue'])
                ->name('queue');

            Route::get('auditoria/{workOrder}', [InspectorController::class, 'audit'])
                ->whereNumber('workOrder')
                ->name('audit');

            Route::post('auditoria/{workOrder}', [InspectorController::class, 'store'])
                ->whereNumber('workOrder')
                ->name('audit.store');
        });

    // Alcaldía: pulso operativo, despacho de incidencias y gestión de
    // cuadrillas. TODA la superficie /admin exige rol superadmin.
    Route::middleware('role:superadmin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

            Route::get('analitica', [AdminController::class, 'analytics'])->name('analytics');

            Route::post('incidencias/{report}/asignar', [AdminController::class, 'assign'])
                ->whereNumber('report')
                ->name('assign');

            Route::get('contratistas', [AdminController::class, 'contractors'])->name('contractors');

            Route::post('contratistas', [AdminController::class, 'storeContractor'])
                ->name('contractors.store');
        });
});
