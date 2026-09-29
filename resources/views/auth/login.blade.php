<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — Vigía Ciudadano</title>
    <meta name="description" content="Acceso institucional a Vigía Ciudadano: control social, trazabilidad y compuerta de calidad de la obra pública municipal.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Misma serif de la landing (Playfair Display vía Bunny); se enlaza aquí
         porque esta vista es independiente del layouts.app oscuro. --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:500,600,700" rel="stylesheet">
    <style>
        /* El `font-serif` pedido en el diseño apunta a la serif institucional
           de la landing, no a la pila de reservas de Tailwind. */
        .font-serif { font-family: 'Playfair Display', ui-serif, Georgia, 'Times New Roman', serif; }
    </style>
</head>

{{-- Página soberana (no hereda layouts.app): el login es pantalla completa
     clara, idéntica en temperatura y paleta al fondo de la landing. --}}
<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#f7fcf9] p-4 font-sans text-[#062e24] antialiased lg:p-8">

    {{-- ==================== ORBES DECORATIVOS (identidad landing) ==================== --}}
    <div aria-hidden="true" class="pointer-events-none fixed -top-32 -left-32 size-96 rounded-full bg-[#c5f2df] opacity-70 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none fixed right-[-8rem] top-1/3 size-80 rounded-full bg-[#edf9d7] opacity-70 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none fixed bottom-[-8rem] left-1/3 size-72 rounded-full bg-[#cceee0] opacity-70 blur-3xl"></div>

    {{-- Flashes heredados del layout (p. ej. redirecciones con status/error) --}}
    @if (session('status') || session('error'))
        <div class="absolute inset-x-4 top-4 z-20 mx-auto max-w-4xl space-y-2">
            @if (session('status'))
                <p class="rounded-xl border border-[#c1e5d6] bg-[#e2f5eb] px-4 py-2.5 text-sm font-medium text-[#09654e]" role="status">✅ {{ session('status') }}</p>
            @endif
            @if (session('error'))
                <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-700" role="alert">⚠️ {{ session('error') }}</p>
            @endif
        </div>
    @endif

    {{-- ==================== TARJETA CENTRAL · 2 COLUMNAS ==================== --}}
    <main class="grid w-full max-w-4xl grid-cols-1 overflow-hidden rounded-3xl border border-[#d5e8e0] bg-white/90 shadow-[0_16px_45px_rgba(6,46,36,0.08)] backdrop-blur-md md:grid-cols-12">

        {{-- ============ COLUMNA IZQUIERDA · IDENTIDAD Y VALOR ============ --}}
        <section class="flex flex-col justify-between border-b border-[#d5e8e0] bg-gradient-to-br from-[#e8f8f0] to-[#f4fbf7] p-8 md:col-span-5 md:border-r md:border-b-0">

            {{-- Marca: mismo escudo-ojo de la landing, enlaza al inicio --}}
            <header>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 transition duration-200 hover:opacity-80">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#087b5f] to-[#05664e] text-white shadow-md shadow-[#087b5f]/25">
                        <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
                            <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" fill="currentColor" opacity=".22"/>
                            <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M7.8 11.6c1.1-1.7 2.7-2.6 4.2-2.6s3.1.9 4.2 2.6c-1.1 1.7-2.7 2.6-4.2 2.6s-3.1-.9-4.2-2.6Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                            <circle cx="12" cy="11.6" r="1.15" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-sm font-bold tracking-wide text-[#062e24]">Vigía Ciudadano</span>
                        <span class="block text-xs text-[#087b5f]">Control de obra pública</span>
                    </span>
                </a>
            </header>

            {{-- Discurso de valor + widget ilustrativo de auditoría --}}
            <div class="py-8">
                <span class="inline-block w-fit rounded-full bg-[#e2f5eb] px-3 py-1 text-xs font-bold text-[#09654e]">
                    Transparencia municipal
                </span>
                <h1 class="mt-3 font-serif text-2xl leading-snug font-bold text-[#062e24]">
                    La obra pública, bajo la lupa de la ciudad
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-[#1f5142]">
                    Plataforma de control social, trazabilidad y compuerta de calidad de obra pública municipal.
                </p>

                {{-- Mini-widget: replica la tarjeta flotante del hero de la landing --}}
                <div class="mt-6 rounded-2xl border border-[#d5e8e0] bg-white p-4 shadow-[0_10px_28px_rgba(6,46,36,0.07)]">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-mono text-[10px] text-[#5d7d70]">REP-2026-0148</p>
                            <p class="mt-0.5 text-sm font-semibold leading-snug text-[#062e24]">Bache con filtración en la calzada</p>
                        </div>
                        {{-- Badge fiel a StatusUi::UnderInspection (mismo violeta que la landing) --}}
                        <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-indigo-700">En inspección</span>
                    </div>
                    <ul class="mt-3 space-y-1.5">
                        @foreach (['Foto georreferenciada', 'Acta de inspección firmada', 'Dictamen técnico'] as $chequeo)
                            <li class="flex items-center gap-2 text-[11px] font-medium text-[#1f5142]">
                                <svg viewBox="0 0 20 20" class="size-4 shrink-0 text-[#087b5f]" aria-hidden="true">
                                    <circle cx="10" cy="10" r="9" fill="currentColor" opacity=".14"/>
                                    <path d="m6 10.5 2.6 2.8L14.4 7" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ $chequeo }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 rounded-lg bg-[#f0faf5] px-3 py-2 text-[11px] font-semibold text-[#09654e]">
                        3/3 evidencias públicas · expediente íntegro
                    </p>
                </div>
            </div>

            <footer class="text-[11px] text-[#5d7d70]">
                © {{ date('Y') }} Alcaldía Municipal · Datos protegidos
            </footer>
        </section>

        {{-- ============ COLUMNA DERECHA · FORMULARIO OPERATIVO ============ --}}
        <section class="flex flex-col justify-center bg-white p-8 md:col-span-7 md:p-10">

            <header>
                <p class="text-xs font-bold tracking-wider text-[#087b5f] uppercase">Acceso institucional</p>
                <h2 class="mt-2 font-serif text-3xl font-bold text-[#062e24]">Acceso al Sistema</h2>
                <p class="mt-2 text-sm leading-relaxed text-[#1f5142]">
                    Ingresa tus credenciales para gestionar o auditar reportes.
                </p>
            </header>

            <form id="login-form" method="POST" action="{{ route('login.attempt') }}" class="mt-7 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold tracking-wide text-[#1f5142] uppercase">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="username" placeholder="tu@correo.gov.co"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-3 text-sm text-[#062e24] outline-none transition placeholder:text-slate-400 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('email')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-xs font-bold tracking-wide text-[#1f5142] uppercase">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="••••••••••"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-3 text-sm text-[#062e24] outline-none transition placeholder:text-slate-400 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('password')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex w-fit cursor-pointer items-center gap-2 text-sm font-medium text-[#1f5142]">
                    <input type="checkbox" name="remember" value="1"
                           class="size-4 cursor-pointer rounded border-[#b9daca] bg-[#fcfdfd] accent-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    Recordar mi sesión
                </label>

                <button type="submit"
                        class="w-full rounded-xl bg-[#087b5f] py-3.5 font-bold text-white shadow-[0_9px_20px_rgba(8,123,95,0.23)] transition hover:-translate-y-0.5 hover:bg-[#05664e] active:translate-y-0">
                    Entrar al Sistema
                </button>
            </form>

            @if (config('app.debug'))
                {{-- Solo en desarrollo: accesos 1-Click con las cuentas sembradas.
                     En producción este bloque desaparece por completo (app.debug=false). --}}
                <div class="mt-6 border-t border-[#d5e8e0] pt-5">
                    <p class="text-[10px] font-bold tracking-widest text-[#5d7d70] uppercase">
                        Ambiente de pruebas · password123
                    </p>

                    {{-- Login 1-Click: rellena y envía el form por JS con la credencial exacta.
                         Bypassa el autofill del navegador (causa del pwd_len != 11 en laravel.log). --}}
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <button type="button" data-quick-login="admin@vigia.gov.co"
                                class="flex items-center justify-center gap-2 rounded-lg border border-[#c1e5d6] bg-[#f0faf5] px-3 py-2 text-xs font-semibold text-[#062e24] transition hover:bg-[#e1f5eb] active:scale-[0.98]">
                            <span class="size-1.5 rounded-full bg-sky-500"></span> Alcaldía
                        </button>
                        <button type="button" data-quick-login="cuadrilla@infraestructura.com"
                                class="flex items-center justify-center gap-2 rounded-lg border border-[#c1e5d6] bg-[#f0faf5] px-3 py-2 text-xs font-semibold text-[#062e24] transition hover:bg-[#e1f5eb] active:scale-[0.98]">
                            <span class="size-1.5 rounded-full bg-amber-500"></span> Cuadrilla
                        </button>
                        <button type="button" data-quick-login="inspector@calidad.gov.co"
                                class="flex items-center justify-center gap-2 rounded-lg border border-[#c1e5d6] bg-[#f0faf5] px-3 py-2 text-xs font-semibold text-[#062e24] transition hover:bg-[#e1f5eb] active:scale-[0.98]">
                            <span class="size-1.5 rounded-full bg-violet-500"></span> Inspector
                        </button>
                    </div>
                </div>

                @once
                    <script>
                        // Botones 1-Click de desarrollo: asigna credenciales y envía el form
                        // en el mismo tick, para que el autofill no pueda sobrescribirlos.
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
        </section>
    </main>
</body>
</html>
