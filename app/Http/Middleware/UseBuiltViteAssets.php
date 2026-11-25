<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseBuiltViteAssets
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isFromTrustedProxy() || ! $request->isSecure()) {
            return $next($request);
        }

        $vite = app(Vite::class);
        $hotFile = $vite->hotFile();

        // The Vite development server is local to this machine.
        $vite->useHotFile(storage_path('framework/vite-tunnel.hot'));

        try {
            return $next($request);
        } finally {
            $vite->useHotFile($hotFile);
        }
    }
}
