<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vigía Ciudadano')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="bg-[#f7fcf9] text-[#062e24] min-h-screen relative overflow-x-hidden antialiased flex flex-col font-sans">

    {{-- ==================== ORBES DECORATIVOS (identidad landing) ==================== --}}
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-20 -right-20 w-96 h-96 rounded-full bg-[#c5f2df] opacity-60 blur-3xl"></div>
        <div class="absolute top-1/2 -left-24 w-80 h-80 rounded-full bg-[#edf9d7] opacity-60 blur-3xl"></div>
        <div class="absolute -bottom-20 right-1/4 w-96 h-96 rounded-full bg-[#cceee0] opacity-50 blur-3xl"></div>
    </div>

    {{-- ============================ NAV ============================ --}}
    @php $user = auth()->user(); @endphp
    @unless (request()->routeIs('login'))
        <nav class="bg-white/90 backdrop-blur-md border-b border-[#d5e8e0] sticky top-0 z-40 text-[#062e24] shadow-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 transition duration-200 hover:opacity-80">
                    <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-[#087b5f] to-[#05664e] text-white shadow-md shadow-[#087b5f]/25">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" fill="currentColor" opacity=".22"/>
                            <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M7.8 11.6c1.1-1.7 2.7-2.6 4.2-2.6s3.1.9 4.2 2.6c-1.1 1.7-2.7 2.6-4.2 2.6s-3.1-.9-4.2-2.6Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                            <circle cx="12" cy="11.6" r="1.15" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-sm font-bold tracking-wide text-[#062e24]">Vigía Ciudadano</span>
                        <span class="block text-xs text-[#087b5f]">Control de obra pública</span>
                    </span>
                </a>

                @auth
                    {{-- Menú según rol autenticado --}}
                    <div class="hidden items-center gap-1 md:flex">
                        @if ($user->isContractor())
                            <x-nav-link :href="route('contractor.orders')" :active="request()->routeIs('contractor.*')">
                                🧰 Mis órdenes
                            </x-nav-link>
                        @elseif ($user->isInspector())
                            <x-nav-link :href="route('inspector.queue')" :active="request()->routeIs('inspector.*')">
                                🔍 Inspecciones
                            </x-nav-link>
                        @else
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                                🏛️ Panel
                            </x-nav-link>
                            <x-nav-link :href="route('admin.contractors')" :active="request()->routeIs('admin.contractors*')">
                                👷 Cuadrillas
                            </x-nav-link>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="hidden items-center gap-2 rounded-xl border border-[#d5e8e0] bg-[#e8f8f0] px-3 py-1.5 sm:flex">
                            <span class="grid size-7 place-items-center rounded-lg bg-[#087b5f] text-xs font-bold uppercase text-white">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            </span>
                            <span class="leading-tight">
                                <span class="block max-w-36 truncate text-xs font-medium text-[#062e24]">{{ $user->name }}</span>
                                <span class="block text-[11px] font-bold text-[#087b5f] bg-[#e8f8f0] px-2.5 py-1 rounded-full">
                                    {{ \App\Support\RoleUi::label($user->role) }}
                                </span>
                            </span>
                        </div>

                        {{-- La sesión se cierra por POST con CSRF (logout() del AuthController) --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-lg text-sm font-semibold transition">
                                Salir
                            </button>
                        </form>
                    </div>
                @endauth

                @guest
                    {{-- En rutas públicas de reportes, mostrar botón de retorno en lugar de login --}}
                    @if (request()->routeIs('reports.*'))
                        <a href="{{ route('home') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-[#1f5142] bg-white border border-[#d5e8e0] hover:bg-[#f0faf5] hover:text-[#087b5f] hover:border-[#b9daca] transition shadow-sm">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Volver al inicio
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="rounded-xl bg-[#087b5f] px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-[#05664e] active:scale-[0.98]">
                            Iniciar sesión
                        </a>
                    @endif
                @endguest
            </div>

            {{-- Accesibilidad del menú en pantallas medianas-chicas --}}
            @auth
                <div class="flex gap-1 overflow-x-auto px-4 pb-2 md:hidden">
                    @if ($user->isContractor())
                        <x-nav-link :href="route('contractor.orders')" :active="request()->routeIs('contractor.*')">🧰 Órdenes</x-nav-link>
                    @elseif ($user->isInspector())
                        <x-nav-link :href="route('inspector.queue')" :active="request()->routeIs('inspector.*')">🔍 Inspecciones</x-nav-link>
                    @else
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">🏛️ Panel</x-nav-link>
                        <x-nav-link :href="route('admin.contractors')" :active="request()->routeIs('admin.contractors*')">👷 Cuadrillas</x-nav-link>
                    @endif
                </div>
            @endauth
        </nav>
    @endunless

    {{-- ========================= FLASHES ========================= --}}
    <div class="mx-auto max-w-7xl space-y-3 px-4 pt-4 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl border border-[#c1e5d6] bg-[#e2f5eb] px-4 py-3 text-sm font-medium text-[#09654e] shadow-sm"
                 role="status">
                <span class="mt-0.5">✅</span>
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 shadow-sm"
                 role="alert">
                <span class="mt-0.5">⚠️</span>
                <p>{{ session('error') }}</p>
            </div>
        @endif
    </div>

    {{-- ========================= CONTENT ========================= --}}
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        @yield('content')
    </main>

    <footer class="border-t border-[#d5e8e0] py-6 text-center text-xs text-[#5d7d70]">
        Vigía Ciudadano · {{ date('Y') }} — La obra pública, vigilada por todos.
    </footer>

    @stack('scripts')
</body>
</html>
