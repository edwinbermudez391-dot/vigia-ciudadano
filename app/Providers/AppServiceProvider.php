<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Anti-abuso de creación de reportes: el costo real lo paga la
        // dependencia (gestión de la incidencia), no el ciudadano. Se limita
        // por usuario autenticado y, como red alternativa, por IP.
        RateLimiter::for('reports', function (Request $request): array {
            $perMinute = $request->user() !== null ? 5 : 2;

            return [
                Limit::perMinute($perMinute)->by(
                    $request->user()?->id ?? $request->ip()
                ),
            ];
        });
    }
}
