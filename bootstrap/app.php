<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // El túnel local termina HTTPS y reenvía la petición a Laravel por HTTP.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->append(\App\Http\Middleware\UseBuiltViteAssets::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // Cookie de sesión + CSRF para llamadas fetch()/axios desde Blade al
        // mismo dominio (primera parte). Sin esto, auth:sanctum solo acepta
        // tokens Bearer y el portal ciudadano recibiría 401 en /reports/nearby.
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
