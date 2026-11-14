<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('imagenes/logo-vigia.png') }}">
    <title>@yield('title', 'Vigía Ciudadano')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
@php
    $user = auth()->user();
    $institutional = $user && ($user->isSuperAdmin() || $user->isContractor() || $user->isInspector());
    $citizen = !$institutional && (request()->is('reportes*') || request()->routeIs('map'));
@endphp
<body class="institutional-ui min-h-screen font-sans antialiased {{ $institutional ? 'has-sidebar' : '' }} {{ $citizen ? 'citizen-ui' : '' }}">
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    <header class="app-header">
        <div class="app-header-inner">
            <a href="{{ route('home') }}" class="brand">
                <img src="{{ asset('imagenes/logo-vigia.png') }}" alt="" width="48" height="48">
                <span><strong>Vigía Ciudadano</strong></span>
            </a>
            @auth
                <div class="session-info">
                    <span class="user-avatar" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div><strong>{{ $user->name }}</strong><span>{{ \App\Support\RoleUi::label($user->role) }}</span></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary"><x-icon name="logout" />Cerrar sesión</button>
                    </form>
                </div>
            @else
                <x-public-navigation />
            @endauth
        </div>
    </header>

    <div class="app-shell">
    @if ($institutional)
        <div class="mobile-menu-bar"><button type="button" class="btn-secondary" data-sidebar-mobile aria-controls="primary-sidebar" aria-expanded="false"><x-icon name="menu" />Menú</button></div>
        <x-sidebar />
    @endif

    <div class="app-workspace">
    <main id="main-content" tabindex="-1" class="app-main">
        @if ($citizen)<x-citizen-tools />@endif
        @if (session('status'))
            <div class="notice notice-success" role="status"><strong>Operación realizada.</strong> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="notice notice-error" role="alert"><strong>No se pudo completar la operación.</strong> {{ session('error') }}</div>
        @endif
        @if ($errors->any() && !$citizen)
            <div class="notice notice-error" role="alert" tabindex="-1" data-error-summary>
                <strong>Revisa los siguientes datos antes de continuar:</strong>
                <ul class="mt-2 list-disc pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <p class="mt-2">Si adjuntaste fotografías, vuelve a seleccionarlas.</p>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="app-footer">Vigía Ciudadano · {{ date('Y') }} · La obra pública, vigilada por todos.</footer>
    </div>
    </div>
    @stack('scripts')
</body>
</html>
