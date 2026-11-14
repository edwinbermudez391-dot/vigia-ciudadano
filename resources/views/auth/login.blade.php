<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — Vigía Ciudadano</title>
    <meta name="description" content="Acceso institucional a Vigía Ciudadano: control social, trazabilidad y compuerta de calidad de la obra pública municipal.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="institutional-ui login-page min-h-screen bg-white font-sans text-text-primary antialiased">
    <a href="#login-form" class="skip-link">Saltar al formulario de acceso</a>
    <div class="login-layout">
        
        {{-- ==================== COLUMNA IZQUIERDA: FORMULARIO ==================== --}}
        <main class="login-main">
            <div class="mx-auto w-full max-w-lg">
                
                {{-- Logo y Marca --}}
                <div class="mb-8">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 transition hover:opacity-80">
                        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-white shadow-md shadow-primary/10">
                            <img src="{{ asset('imagenes/logo-vigia.png') }}" alt="Logo Vigía Ciudadano" class="h-full w-auto object-contain">
                        </div>
                        <div>
                            <div class="text-lg font-bold tracking-tight text-text-primary">Vigía Ciudadano</div>
                        </div>
                    </a>
                </div>

                {{-- Título Principal --}}
                <div class="mb-8">
                    <h1 class="text-2xl font-bold tracking-tight text-text-primary">Ingresar</h1>
                    <p class="mt-3 text-base text-text-secondary">Usa la cuenta habilitada por tu entidad.</p>
                </div>

                {{-- Flashes (status/error) --}}
                @if (session('status') || session('error'))
                    <div class="mb-6 space-y-2">
                        @if (session('status'))
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                                ✅ {{ session('status') }}
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700" role="alert">
                                ⚠️ {{ session('error') }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ==================== FORMULARIO ==================== --}}
                <form id="login-form" tabindex="-1" method="POST" action="{{ route('login.attempt') }}" class="space-y-5">
                    @csrf

                    {{-- Correo electrónico --}}
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-text-primary">
                            Correo electrónico
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="h-5 w-5 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                   @if ($errors->has('email')) aria-describedby="email-error" @endif
                                   autocomplete="username" placeholder="tu@correo.gov.co"
                                   class="w-full rounded-full border border-border bg-slate-50 py-3 pl-11 pr-4 text-sm text-text-primary placeholder:text-text-secondary outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20">
                        </div>
                        <x-field-error field="email" />
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-text-primary">
                            Contraseña
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="h-5 w-5 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" required autocomplete="current-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                   @if ($errors->has('password')) aria-describedby="password-error" @endif
                                   placeholder="••••••••••"
                                   class="w-full rounded-full border border-border bg-slate-50 py-3 pl-11 pr-4 text-sm text-text-primary placeholder:text-text-secondary outline-none transition focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/20">
                        </div>
                        <x-field-error field="password" />
                    </div>

                    {{-- Recordar sesión y Recuperar contraseña --}}
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="remember" value="1"
                                   class="h-4 w-4 rounded border-border bg-white text-primary transition focus:ring-2 focus:ring-primary/20">
                            <span class="text-sm text-text-secondary">Recordarme</span>
                        </label>
                        <p class="text-sm text-text-secondary">Si olvidaste tu contraseña, solicita ayuda al administrador de tu entidad.</p>
                    </div>

                    {{-- Botón principal --}}
                    <button type="submit"
                             class="btn-primary w-full">
                        Ingresar
                    </button>
                </form>

                {{-- ==================== QUICK LOGIN (solo en desarrollo) ==================== --}}
                @if (config('app.debug'))
                    <div class="mt-8 border-t border-border pt-6">
                        <p class="mb-3 text-center text-[10px] font-semibold tracking-widest text-text-secondary uppercase">
                            Ambiente de pruebas · password
                        </p>
                        <div class="login-demo-actions">
                            <button type="button" data-quick-login="admin@vigia.gov.co"
                                    class="flex items-center justify-center gap-1.5 rounded-full border border-border bg-background px-2 py-2 text-[11px] font-semibold text-text-primary transition hover:bg-primary/5 hover:border-primary active:scale-[0.98]">
                                <span class="size-1.5 rounded-full bg-secondary"></span> Alcaldía
                            </button>
                            <button type="button" data-quick-login="cuadrilla@infraestructura.com"
                                    class="flex items-center justify-center gap-1.5 rounded-full border border-border bg-background px-2 py-2 text-[11px] font-semibold text-text-primary transition hover:bg-primary/5 hover:border-primary active:scale-[0.98]">
                                <span class="size-1.5 rounded-full bg-success"></span> Cuadrilla
                            </button>
                            <button type="button" data-quick-login="inspector1@calidad.gov.co"
                                    class="flex items-center justify-center gap-1.5 rounded-full border border-border bg-background px-2 py-2 text-[11px] font-semibold text-text-primary transition hover:bg-primary/5 hover:border-primary active:scale-[0.98]">
                                <span class="size-1.5 rounded-full bg-primary"></span> Inspector
                            </button>
                        </div>
                    </div>

                    @once
                        <script>
                            document.querySelectorAll('[data-quick-login]').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    var form = document.getElementById('login-form');
                                    var email = document.getElementById('email');
                                    var pass = document.getElementById('password');
                                    email.value = btn.getAttribute('data-quick-login');
                                    pass.value = 'password';
                                    form.submit();
                                });
                            });
                        </script>
                    @endonce
                @endif

                {{-- Enlace de regreso --}}
                <div class="mt-8 text-center">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-text-secondary transition hover:text-primary">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al inicio
                    </a>
                </div>

            </div>
        </main>

        {{-- ==================== COLUMNA DERECHA: IMAGEN HERO ==================== --}}
        <aside class="login-aside" aria-label="Acerca del espacio de trabajo">
            <span class="login-seal"><x-icon name="shield" />Gestión municipal</span>
            <h2>Una ciudad mejor atendida.</h2>
            <p>Coordina reparaciones y verifica su calidad.</p>
            <ul><li><x-icon name="report" />Reportes</li><li><x-icon name="work" />Órdenes de trabajo</li><li><x-icon name="check" />Inspecciones</li></ul>
            <img src="{{ asset('imagenes/WhatsApp Image 2026-10-16 at 11.51.55 AM.jpeg') }}" alt="" class="login-photo">
        </aside>

    </div>
</body>
</html>
