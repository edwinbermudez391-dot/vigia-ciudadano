<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Autenticación de sesión (web) contra los mismos usuarios de la API.
 *
 * Nota de seguridad: la credencial `is_active` se inyecta en attempt(), de modo
 * que una cuenta desactivada NUNCA inicia sesión aunque la contraseña coincida;
 * el mensaje de fallo es genérico para no revelar qué correos existen.
 *
 * El sistema de autenticación es exclusivo para personal operativo e institucional
 * (Superadmin, Contratista, Inspector). Los ciudadanos reportan incidencias de
 * forma pública y anónima sin necesidad de cuenta.
 */
final class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        // Credencial adicional is_active: una cuenta desactivada NUNCA entra,
        // aunque la contraseña coincida (falla en retrieveByCredentials).
        $authenticated = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], remember: $request->boolean('remember'));

        if (! $authenticated) {
            // Campos forenses SIN secretos: existen/activo/longitud/boleano.
            // Nunca se registra la contraseña; permite distinguir en
            // laravel.log "hash roto" vs "cuenta inactiva" vs "autofill viejo"
            // (pwd_len + coincide_password123 delatan credenciales del navegador).
            $usuario = User::query()->where('email', $credentials['email'])->first();
            $plano = (string) $request->input('password');

            Log::warning('Intento de inicio de sesión fallido', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_existe' => $usuario !== null,
                'activo' => $usuario === null ? null : (bool) $usuario->is_active,
                'pwd_len' => strlen($plano),
                'coincide_password123' => $plano === 'password123',
            ]);

            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas o cuenta sin acceso al sistema.',
            ]);
        }

        // Rotación de la id de sesión tras el login: previene session fixation.
        $request->session()->regenerate();

        $user = Auth::user();

        // Verificar que el usuario tenga un rol institucional válido.
        // Si no tiene uno de los 3 roles operativos, aborta con 403.
        if (! in_array($user->role, [UserRole::SuperAdmin, UserRole::Contractor, UserRole::QualityInspector], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new HttpException(403, 'Acceso restringido a personal institucional autorizado.');
        }

        return redirect()->intended(self::homeFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Aterrizaje por rol: cada usuario institucional va directo a su superficie de trabajo.
     *
     * @throws HttpException si el rol no es institucional válido
     */
    public static function homeFor(User $user): string
    {
        return match ($user->role) {
            UserRole::SuperAdmin => route('admin.dashboard'),
            UserRole::Contractor => route('contractor.orders'),
            UserRole::QualityInspector => route('inspector.queue'),
            default => throw new HttpException(403, 'Acceso restringido a personal institucional autorizado.'),
        };
    }
}
