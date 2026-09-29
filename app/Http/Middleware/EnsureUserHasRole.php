<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Exige que el usuario autenticado tenga uno de los roles permitidos.
 *
 * Defense-in-depth: los Form Requests ya validan rol+propiedad en authorize(),
 * pero las rutas sin Form Request (listados, startWork) quedarían desprotegidas
 * sin este middleware. Ninguna mutación del ciclo de vida depende de un único
 * punto de control.
 *
 * Uso: ->middleware('role:superadmin') o 'role:contractor,quality_inspector'.
 */
final class EnsureUserHasRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles,
    ): ResponseAlias|JsonResponse {
        $allowed = $this->parseRoles($roles);

        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Debes autenticarte para acceder a este recurso.',
            ], 401);
        }

        if ($user->is_active !== true) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al administrador del sistema.',
            ], 403);
        }

        if (! in_array($user->role?->value, $allowed, true)) {
            return response()->json([
                'message' => 'Tu rol no está autorizado para operar estos endpoints.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Falla temprano ante un rol mal escrito en las rutas: un parámetro
     * desconocido dejaría el endpoint abierto de forma silenciosa.
     *
     * @param  list<string>  $roles
     * @return list<string>
     */
    private function parseRoles(array $roles): array
    {
        $allowed = [];

        foreach ($roles as $role) {
            $parsed = UserRole::tryFrom(trim($role));

            if ($parsed === null) {
                throw new RuntimeException("Rol desconocido en la ruta: '{$role}'.");
            }

            $allowed[] = $parsed->value;
        }

        return $allowed;
    }
}
