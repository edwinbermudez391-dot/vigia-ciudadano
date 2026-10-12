<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — Vigía Ciudadano</title>
    <meta name="description" content="Acceso institucional a Vigía Ciudadano: control social, trazabilidad y compuerta de calidad de la obra pública municipal.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:500,600,700" rel="stylesheet">
    <style>
        .font-serif { font-family: 'Playfair Display', ui-serif, Georgia, 'Times New Roman', serif; }
    </style>
</head>

{{-- Fondo neutro frío con resplandor esmeralda central --}}
<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-100 p-4 font-sans text-[#062e24] antialiased lg:p-8">

    {{-- Esfera radial esmeralda (atmósfera difuminada en el centro) --}}
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-emerald-100/60 via-transparent to-transparent"></div>
    {{-- Orbe sutil superior-izquierdo (profundidad ambiental) --}}
    <div aria-hidden="true" class="pointer-events-none fixed -top-32 -left-20 size-96 rounded-full bg-emerald-200/25 blur-3xl"></div>
    {{-- Orbe sutil inferior-derecho (contrapunto cromático frío) --}}
    <div aria-hidden="true" class="pointer-events-none fixed -right-16 -bottom-24 size-80 rounded-full bg-teal-300/20 blur-3xl"></div>

    {{-- Flashes (status/error heredados de redirecciones) --}}
    @if (session('status') || session('error'))
        <div class="relative z-20 mx-auto mb-4 w-full max-w-md space-y-2 px-4">
            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-medium text-emerald-800 shadow-sm" role="status">✅ {{ session('status') }}</p>
            @endif
            @if (session('error'))
                <p class="rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-medium text-rose-700 shadow-sm" role="alert">⚠️ {{ session('error') }}</p>
            @endif
        </div>
    @endif

    {{-- ==================== TARJETA CENTRAL ==================== --}}
    <main class="relative z-10 w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-[0_20px_50px_rgba(8,112,84,0.08)] shadow-xl sm:p-10">

        {{-- ==================== MARCA + BIENVENIDA ==================== --}}
        <div class="text-center">
            <a href="{{ route('home') }}" class="group inline-flex flex-col items-center gap-3 transition duration-200 hover:opacity-80">
                <span class="grid size-14 place-items-center rounded-2xl bg-gradient-to-br from-[#087b5f] to-[#05664e] text-white shadow-lg shadow-emerald-900/15">
                    <svg viewBox="0 0 24 24" fill="none" class="size-7" aria-hidden="true">
                        <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" fill="currentColor" opacity=".22"/>
                        <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" stroke="currentColor" stroke-width="1.4"/>
                        <path d="M7.8 11.6c1.1-1.7 2.7-2.6 4.2-2.6s3.1.9 4.2 2.6c-1.1 1.7-2.7 2.6-4.2 2.6s-3.1-.9-4.2-2.6Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                        <circle cx="12" cy="11.6" r="1.15" fill="currentColor"/>
                    </svg>
                </span>
                <span>
                    <span class="block font-serif text-xl font-bold text-[#062e24]">Vigía Ciudadano</span>
                    <span class="mt-0.5 block text-xs tracking-wide text-[#5d7d70]">Portal de gestión institucional</span>
                </span>
            </a>
        </div>

        {{-- ==================== FORMULARIO ==================== --}}
        <form id="login-form" method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-4">
            @csrf

            {{-- Correo electrónico --}}
            <div>
                <label for="email" class="mb-1.5 block text-xs font-semibold tracking-wide text-[#1f5142] uppercase">Correo electrónico</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-emerald-600/70">
                        <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="username" placeholder="tu@correo.gov.co"
                           class="w-full rounded-full border border-slate-200 bg-slate-50/70 py-2.5 pl-11 pr-4 text-sm text-[#062e24] outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contraseña --}}
            <div>
                <label for="password" class="mb-1.5 block text-xs font-semibold tracking-wide text-[#1f5142] uppercase">Contraseña</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-emerald-600/70">
                        <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </span>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="••••••••••"
                           class="w-full rounded-full border border-slate-200 bg-slate-50/70 py-2.5 pl-11 pr-4 text-sm text-[#062e24] outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Recordar sesión --}}
            <label class="flex w-fit cursor-pointer items-center gap-2 text-sm text-[#1f5142] transition hover:text-emerald-700">
                <input type="checkbox" name="remember" value="1"
                       class="size-4 cursor-pointer rounded border-emerald-300 bg-white accent-emerald-700 transition focus:ring-2 focus:ring-emerald-500/20">
                <span>Recordar mi sesión</span>
            </label>

            {{-- Botón principal --}}
            <button type="submit"
                    class="w-full rounded-full bg-[#087b5f] py-3 text-sm font-bold tracking-wide text-white shadow-md shadow-emerald-900/15 transition duration-200 hover:bg-[#05664e] hover:shadow-lg active:scale-[0.99]">
                Entrar al Sistema
            </button>
        </form>

        {{-- Enlace de regreso --}}
        <div class="mt-6 text-center">
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-1.5 text-xs font-medium text-[#5d7d70] transition hover:text-emerald-700">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al inicio
            </a>
        </div>

        {{-- ==================== QUICK LOGIN (solo en desarrollo) ==================== --}}
        @if (config('app.debug'))
            <div class="mt-6 border-t border-slate-200 pt-4">
                <p class="text-center text-[10px] font-semibold tracking-widest text-[#5d7d70] uppercase">
                    Ambiente de pruebas · password123
                </p>
                <div class="mt-2.5 grid grid-cols-3 gap-2">
                    <button type="button" data-quick-login="admin@vigia.gov.co"
                            class="flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-[11px] font-semibold text-[#062e24] transition hover:bg-emerald-50 hover:border-emerald-300 active:scale-[0.98]">
                        <span class="size-1.5 rounded-full bg-sky-500"></span> Alcaldía
                    </button>
                    <button type="button" data-quick-login="cuadrilla@infraestructura.com"
                            class="flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-[11px] font-semibold text-[#062e24] transition hover:bg-emerald-50 hover:border-emerald-300 active:scale-[0.98]">
                        <span class="size-1.5 rounded-full bg-amber-500"></span> Cuadrilla
                    </button>
                    <button type="button" data-quick-login="inspector@calidad.gov.co"
                            class="flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-[11px] font-semibold text-[#062e24] transition hover:bg-emerald-50 hover:border-emerald-300 active:scale-[0.98]">
                        <span class="size-1.5 rounded-full bg-violet-500"></span> Inspector
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
                            pass.value = 'password123';
                            form.submit();
                        });
                    });
                </script>
            @endonce
        @endif
    </main>
</body>
</html>
