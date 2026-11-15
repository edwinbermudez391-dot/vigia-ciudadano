<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vigía Ciudadano — Gestión ciudadana</title>
    <meta name="description" content="Reporta problemas de tu comunidad y consulta su solución. Fácil, rápido y sin registro.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('imagenes/logo-vigia.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="institutional-ui public-portal citizen-ui min-h-screen bg-white font-sans text-slate-800 antialiased">
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

    {{-- ============================ HEADER ============================ --}}
    <header class="app-header">
        <div class="app-header-inner">
            <a href="{{ route('home') }}" class="brand">
                <img src="{{ asset('imagenes/logo-vigia.png') }}" alt="" width="40" height="40">
                <strong>Vigía Ciudadano</strong>
            </a>
            <x-public-navigation />
        </div>
    </header>

    {{-- ============================ HERO ============================ --}}
    <main id="main-content" tabindex="-1">
    <div class="public-section-inner"><x-citizen-tools /></div>
    <section class="public-hero">

        <div class="hero-layout">
            {{-- Columna de mensaje --}}
            <div>
                <p class="eyebrow">Sin registro</p>

                <h1 class="hero-title font-semibold text-slate-900">
                    Mejoremos nuestra comunidad
                </h1>

                <p class="mt-4 max-w-xl text-slate-600">
                    Reporta daños en tu barrio y consulta cómo va su solución.
                </p>

                <div class="hero-actions">
                    <a href="{{ route('reports.create') }}"
                       class="btn-primary">
                        <x-icon name="camera" />Reportar un problema
                    </a>
                    <a href="{{ route('reports.device') }}" class="btn-secondary"><x-icon name="report" />Reportes en este dispositivo</a>
                </div>

            </div>

            <aside class="public-service-card" aria-label="Antes de enviar tu reporte">
                <h2>Reportar es sencillo</h2>
                <ol class="report-steps">
                    <li><span class="step-icon"><x-icon name="camera" /></span><div><strong>1. Problema y foto</strong><p>Elige qué pasó, cuéntanos y agrega una foto.</p></div></li>
                    <li><span class="step-icon"><x-icon name="map" /></span><div><strong>2. Tu ubicación</strong><p>Usa la ubicación del celular y confirma el punto en el mapa.</p></div></li>
                    <li><span class="step-icon"><x-icon name="check" /></span><div><strong>3. Revisar y enviar</strong><p>Comprueba los datos y envía el reporte.</p></div></li>
                </ol>
                <p class="citizen-help">Después, consulta el avance en «Reportes en este dispositivo».</p>
            </aside>
        </div>
    </section>

    <div class="public-section-inner">
    <details class="citizen-extras">
        <summary>Ver el mapa, cómo funciona y los resultados</summary>
        <div>
    {{-- ============================ MAPA INTERACTIVO ============================ --}}
    <section class="public-section border-y border-emerald-100 bg-white">
        <div class="public-section-inner">
            <div class="max-w-2xl">
                <h2 class="font-semibold text-slate-900">Reportes en tu comunidad</h2>
                <p class="mt-2 text-slate-600">Selecciona un punto para ver el reporte.</p>
            </div>

            {{-- Contenedor del mapa embebido --}}
            <div class="relative mt-8 overflow-hidden rounded-2xl border border-emerald-100 shadow-lg shadow-emerald-900/10">
                {{-- CSS de Leaflet inline para la landing --}}
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
                <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
                <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

                <div id="landing-map" role="region" aria-label="Mapa de reportes activos"></div>

                {{-- Stats flotante --}}
                <div class="absolute top-3 left-14 z-[1000] rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#062e24] shadow-md">
                    <span id="landing-map-count" class="text-emerald-700">0</span> reportes activos
                </div>

                {{-- Leyenda de colores --}}
                <div class="absolute bottom-3 right-3 z-[1000] rounded-xl bg-white/95 px-4 py-3 text-xs shadow-md backdrop-blur-sm">
                    <p class="mb-2 font-bold text-[#062e24]">Estado del reporte</p>
                    <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full border-2 border-white shadow" style="background:#eab308"></span> Reportado</div>
                    <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full border-2 border-white shadow" style="background:#3b82f6"></span> En progreso</div>
                    <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full border-2 border-white shadow" style="background:#8b5cf6"></span> Bajo inspección</div>
                    <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full border-2 border-white shadow" style="background:#ef4444"></span> Retrabajo</div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="text-sm text-slate-600">Fuente: reportes de la plataforma.</p>
                <a href="{{ route('map') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Ver mapa completo →
                </a>
            </div>
        </div>
    </section>

    {{-- ====================== ¿CÓMO FUNCIONA? (4 pasos) ====================== --}}
    <section id="como-funciona" class="public-section scroll-mt-24 border-y border-emerald-100 bg-emerald-50/40">
        <div class="public-section-inner">
            <div class="max-w-2xl">
                <h2 class="font-semibold text-slate-900">Cómo funciona</h2>
                <p class="mt-2 text-slate-600">Acompaña tu reporte en cada paso.</p>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['01', 'report', 'Reporta', 'Envía una foto y la ubicación.'],
                    ['02', 'users', 'Asignamos', 'La Alcaldía encarga la reparación.'],
                    ['03', 'work', 'Reparamos', 'La cuadrilla registra el trabajo.'],
                    ['04', 'check', 'Verificamos', 'Un inspector revisa la calidad.'],
                ] as [$numero, $icono, $titulo, $descripcion])
                    <article class="relative rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm transition duration-200 hover:scale-[1.01] hover:shadow-md">
                        <span class="font-display text-2xl font-bold text-primary">{{ $numero }}</span>
                        <span class="absolute right-5 top-5 action-icon"><x-icon :name="$icono" /></span>
                        <h3 class="mt-3 text-base font-semibold text-slate-900">{{ $titulo }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $descripcion }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ TRANSPARENCIA Y COMPUERTA DE CALIDAD (bloque verde bosque) ============ --}}
    <section id="transparencia" class="public-section scroll-mt-24 px-4">
        <div class="mx-auto max-w-6xl overflow-hidden rounded-3xl bg-[#062e24] p-8 text-white shadow-xl shadow-emerald-950/30 lg:p-12">
            <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
                {{-- Columna principal --}}
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">Evidencia pública</p>
                    <h2 class="mt-2 font-semibold">Cada paso, visible</h2>
                    <p class="mt-4 max-w-2xl leading-relaxed text-emerald-100/80">
                        Consulta las fotos y la revisión de cada obra. Este es un ejemplo ilustrativo.
                    </p>

                    {{-- Pestañas Antes / Durante / Después --}}
                    <div role="tablist" aria-label="Momentos de la evidencia" class="mt-7 inline-flex rounded-xl bg-white/10 p-1">
                        <button id="tab-antes" type="button" role="tab" data-tab-btn="antes" data-state="active" aria-selected="true" aria-controls="card-dano"
                                class="rounded-lg px-4 py-2 text-sm text-emerald-50/70 transition duration-200 hover:text-white data-[state=active]:bg-white data-[state=active]:font-semibold data-[state=active]:text-emerald-900 data-[state=active]:shadow-sm">
                            Antes
                        </button>
                        <button id="tab-durante" type="button" role="tab" data-tab-btn="durante" aria-selected="false" aria-controls="card-obra"
                                class="rounded-lg px-4 py-2 text-sm text-emerald-50/70 transition duration-200 hover:text-white data-[state=active]:bg-white data-[state=active]:font-semibold data-[state=active]:text-emerald-900 data-[state=active]:shadow-sm">
                            Durante
                        </button>
                        <button id="tab-despues" type="button" role="tab" data-tab-btn="despues" aria-selected="false" aria-controls="card-sello"
                                class="rounded-lg px-4 py-2 text-sm text-emerald-50/70 transition duration-200 hover:text-white data-[state=active]:bg-white data-[state=active]:font-semibold data-[state=active]:text-emerald-900 data-[state=active]:shadow-sm">
                            Después
                        </button>
                    </div>

                    {{-- Leyenda dinámica por pestaña --}}
                    <p class="mt-4 text-sm text-blue-100" data-tab-legend="antes">12/09 · Daño reportado.</p>
                    <p class="mt-4 hidden text-sm text-blue-100" data-tab-legend="durante">20/09 · Reparación realizada.</p>
                    <p class="mt-4 hidden text-sm text-blue-100" data-tab-legend="despues">23/09 · Calidad aprobada.</p>

                    {{-- Tarjetas de evidencia --}}
                    <div class="mt-6 grid gap-5">
                        <article id="card-dano" role="tabpanel" aria-labelledby="tab-antes" tabindex="0" data-tab-card="antes"
                                 class="rounded-2xl bg-white/95 p-4 text-slate-700 ring-emerald-300 transition duration-200">
                            <div class="overflow-hidden rounded-xl bg-slate-100">
                                <svg viewBox="0 0 200 130" class="h-28 w-full" role="img" aria-label="Foto del daño original: bache en la vía">
                                    <rect width="200" height="130" fill="#94a3b8"/>
                                    <path d="M0 40h200M0 90h200" stroke="#64748b" stroke-width="3"/>
                                    <ellipse cx="100" cy="72" rx="34" ry="18" fill="#334155"/>
                                    <ellipse cx="100" cy="68" rx="34" ry="18" fill="#1e293b"/>
                                    <path d="M60 58l-14 8M140 58l14 8" stroke="#475569" stroke-width="3" stroke-linecap="round"/>
                                    <circle cx="188" cy="14" r="4" fill="#ef4444"/>
                                    <text x="10" y="18" font-size="9" fill="#f8fafc">REC · GPS ✓</text>
                                </svg>
                            </div>
                            <h3 class="mt-3 font-semibold text-slate-900">Daño original</h3>
                        </article>

                        <article id="card-obra" role="tabpanel" aria-labelledby="tab-durante" tabindex="0" data-tab-card="durante"
                                 class="rounded-2xl bg-white/95 p-4 text-slate-700 ring-emerald-300 transition duration-200">
                            <div class="overflow-hidden rounded-xl bg-slate-100">
                                <svg viewBox="0 0 200 130" class="h-28 w-full" role="img" aria-label="Foto de obra ejecutada: parcheo asfáltico">
                                    <rect width="200" height="130" fill="#94a3b8"/>
                                    <rect x="62" y="50" width="76" height="38" rx="10" fill="#1e293b"/>
                                    <rect x="70" y="56" width="60" height="26" rx="8" fill="#0f172a"/>
                                    <path d="M0 100h200" stroke="#eab308" stroke-width="4" stroke-dasharray="14 10"/>
                                    <path d="M164 74l9-26 9 26z" fill="#fb923c"/>
                                    <path d="M168 66h10l2 8h-14z" fill="#fff" opacity=".85"/>
                                    <text x="10" y="18" font-size="9" fill="#f8fafc">12/09 · cuadrilla 3</text>
                                </svg>
                            </div>
                            <h3 class="mt-3 font-semibold text-slate-900">Reparación</h3>
                        </article>

                        <article id="card-sello" role="tabpanel" aria-labelledby="tab-despues" tabindex="0" data-tab-card="despues"
                                 class="rounded-2xl bg-white/95 p-4 text-slate-700 ring-emerald-300 transition duration-200">
                            <div class="grid h-28 place-items-center rounded-xl bg-slate-100">
                                <svg viewBox="0 0 120 120" class="h-20 w-20" role="img" aria-label="Sello de verificación técnica: aprobado">
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="#059669" stroke-width="4" stroke-dasharray="7 5"/>
                                    <circle cx="60" cy="60" r="40" fill="#ecfdf5" stroke="#059669" stroke-width="2.5"/>
                                    <path d="m44 60 11 12 22-26" stroke="#047857" stroke-width="7" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                    <text x="60" y="97" font-size="8" text-anchor="middle" fill="#047857" font-weight="bold">ACTA Nº 482</text>
                                </svg>
                            </div>
                            <h3 class="mt-3 font-semibold text-slate-900">Calidad verificada</h3>
                        </article>
                    </div>
                </div>

                {{-- Barra lateral: estado de verificación + línea de tiempo --}}
                <aside class="w-full space-y-6 lg:w-80">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h3 class="text-sm font-semibold text-white">Estado de verificación</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            @foreach (['Foto georreferenciada original', 'Acta de inspección firmada', 'Dictamen técnico aprobado'] as $chequeo)
                                <li class="flex items-start gap-2.5 text-emerald-50/85">
                                    <svg viewBox="0 0 20 20" class="mt-0.5 size-4 shrink-0 text-emerald-300" aria-hidden="true">
                                        <circle cx="10" cy="10" r="9" fill="currentColor" opacity=".18"/>
                                        <path d="m6 10.5 2.6 2.8L14.4 7" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    {{ $chequeo }}
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-4 rounded-lg bg-emerald-400/15 px-3 py-2 text-xs font-medium text-emerald-200">
                            3/3 requisitos públicos · expediente íntegro
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h3 class="text-sm font-semibold text-white">Historial del ejemplo</h3>
                        <ol class="mt-4 space-y-4 border-l border-white/15 pl-4 text-sm">
                            @foreach ([
                                ['12/09', 'Radicado ciudadano', 'ticket REP-2026-0148'],
                                ['13/09', 'Despacho a cuadrilla', 'orden OT-089 programada'],
                                ['20/09', 'Reparación en sitio', 'foto de solución cargada'],
                                ['23/09', 'Dictamen aprobado', 'auditoría técnica conforme'],
                            ] as [$fecha, $titulo, $detalle])
                                <li class="relative">
                                    <span class="absolute -left-[21px] top-1.5 size-2.5 rounded-full bg-emerald-300 ring-4 ring-[#062e24]"></span>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-300">{{ $fecha }} · {{ $titulo }}</p>
                                    <p class="text-xs text-emerald-50/70">{{ $detalle }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    {{-- ============================ MÉTRICAS / KPIs ============================ --}}
    @php
        // Geometría del gráfico (640×240): series radicados vs cerrados, últimos 7 días.
        $chartW = 640; $chartH = 240; $padX = 14; $padTop = 20; $padBottom = 34;
        $chartMax = max(1, max($trend['created']), max($trend['closed']));
        $xAt = fn (int $i): float => $padX + $i * (($chartW - 2 * $padX) / 6);
        $yAt = fn (int $v): float => $chartH - $padBottom - ($v / $chartMax) * ($chartH - $padTop - $padBottom);
        $linePoints = fn (array $s): string => implode(' ', array_map(
            fn (int $v, int $i) => round($xAt($i), 1) . ',' . round($yAt($v), 1), $s, array_keys($s)
        ));
        $areaPath = fn (array $s): string => 'M' . implode(' L', array_map(
            fn (int $v, int $i) => round($xAt($i), 1) . ',' . round($yAt($v), 1), $s, array_keys($s)
        )) . ' L' . round($xAt(6), 1) . ',' . round($chartH - $padBottom, 1) . ' L' . round($xAt(0), 1) . ',' . round($chartH - $padBottom, 1) . ' Z';
    @endphp
    <section id="metricas" class="public-section scroll-mt-24 border-y border-emerald-100 bg-emerald-50/40">
        <div class="public-section-inner">
            <div class="max-w-2xl">
                <h2 class="font-semibold text-slate-900">Resultados del municipio</h2>
                <p class="mt-2 text-slate-600">Avances y tiempos de atención.</p>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['report', 'Reportes activos', number_format($stats['active']), 'Pendientes de solución'],
                    ['work', 'Obras en curso', number_format($stats['orders_executing']), 'Asignadas a una cuadrilla'],
                    ['check', 'Aprobación', $stats['approval_rate'] !== null ? $stats['approval_rate'] . '%' : '—', 'Inspecciones aprobadas'],
                    ['clock', 'Tiempo de solución', $stats['sla_days'] !== null ? number_format($stats['sla_days'], 1) . ' días' : '—', 'Promedio hasta el cierre'],
                ] as [$icono, $etiqueta, $valor, $pie])
                    <article class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm transition duration-200 hover:scale-[1.01] hover:shadow-md">
                        <div class="flex items-center gap-2 text-sm font-medium text-slate-500">
                            <x-icon :name="$icono" /> {{ $etiqueta }}
                        </div>
                        <p class="font-display mt-4 text-4xl font-bold text-emerald-700">{{ $valor }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $pie }}</p>
                    </article>
                @endforeach
            </div>

            {{-- Gráfico de tendencia semanal --}}
            <div class="mt-8 rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">Tendencia de gestión · últimos 7 días</h3>
                    <div class="flex items-center gap-4 text-xs font-medium text-slate-500">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-600"></span>Radicados</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-sky-500"></span>Cerrados</span>
                    </div>
                </div>

                <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="mt-6 w-full" role="img"
                     aria-label="Gráfico de líneas: reportes radicados y cerrados durante los últimos siete días">
                    <defs>
                        <linearGradient id="area-radicados" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#1e40af" stop-opacity=".2"/>
                            <stop offset="1" stop-color="#1e40af" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    {{-- Cuadrícula + escala --}}
                    @for ($g = 0; $g <= 3; $g++)
                        @php $gy = $yAt((int) round($chartMax * $g / 3)); $gv = (int) round($chartMax * $g / 3); @endphp
                        <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $chartW - $padX }}" y2="{{ $gy }}" stroke="#e2e8f0" stroke-dasharray="4 5"/>
                        <text x="{{ $padX - 4 }}" y="{{ $gy + 3 }}" text-anchor="end" font-size="10" fill="#94a3b8">{{ $gv }}</text>
                    @endfor

                    <path d="{{ $areaPath($trend['created']) }}" fill="url(#area-radicados)"/>
                    <polyline points="{{ $linePoints($trend['created']) }}" fill="none" stroke="#1e40af" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="{{ $linePoints($trend['closed']) }}" fill="none" stroke="#0ea5e9" stroke-width="3" stroke-dasharray="7 6" stroke-linecap="round"/>

                    @foreach ($trend['created'] as $i => $v)
                        <circle cx="{{ round($xAt($i), 1) }}" cy="{{ round($yAt($v), 1) }}" r="4.5" fill="#ffffff" stroke="#1e40af" stroke-width="2.5">
                            <title>{{ $trend['labels'][$i] }}: {{ $v }} radicados</title>
                        </circle>
                    @endforeach
                    @foreach ($trend['closed'] as $i => $v)
                        <circle cx="{{ round($xAt($i), 1) }}" cy="{{ round($yAt($v), 1) }}" r="4" fill="#ffffff" stroke="#0ea5e9" stroke-width="2.5">
                            <title>{{ $trend['labels'][$i] }}: {{ $v }} cerrados</title>
                        </circle>
                    @endforeach

                    @foreach ($trend['labels'] as $i => $lbl)
                        <text x="{{ round($xAt($i), 1) }}" y="{{ $chartH - 10 }}" text-anchor="middle" font-size="11" fill="#64748b">{{ $lbl }}</text>
                    @endforeach
                </svg>

                <p class="mt-4 text-sm text-slate-600">Fuente: reportes de la plataforma.</p>
                <details class="mt-4">
                    <summary class="font-semibold text-primary">Ver datos de la semana</summary>
                    <div class="table-scroll mt-3">
                        <table class="reports-table" role="table">
                            <caption class="sr-only">Reportes de los últimos siete días</caption>
                            <thead><tr><th scope="col">Día</th><th scope="col">Recibidos</th><th scope="col">Cerrados</th></tr></thead>
                            <tbody>
                                @foreach ($trend['labels'] as $i => $label)
                                    <tr><td data-label="Día">{{ $label }}</td><td data-label="Recibidos">{{ $trend['created'][$i] }}</td><td data-label="Cerrados">{{ $trend['closed'][$i] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>
    </section>

    {{-- ============================ PARTICIPA ============================ --}}
    <section id="participa" class="public-section scroll-mt-24">
        <div class="public-section-inner">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-semibold text-slate-900">Una tarea compartida</h2>
                <p class="mt-2 text-slate-600">Ciudadanía y entidad, conectadas.</p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['report', 'Ciudadanía', 'text-primary', 'bg-blue-50', 'Reporta y consulta sin crear una cuenta.'],
                    ['work', 'Contratistas', 'text-primary', 'bg-blue-50', 'Reparan y registran los trabajos.'],
                    ['shield', 'Entidad', 'text-primary', 'bg-blue-50', 'Coordina y verifica la calidad.'],
                ] as [$icono, $titulo, $color, $fondo, $descripcion])
                    <article class="rounded-2xl border border-emerald-100 bg-white p-7 shadow-sm transition duration-200 hover:scale-[1.01] hover:shadow-md">
                        <span class="action-icon"><x-icon :name="$icono" /></span>
                        <h3 class="mt-4 text-lg font-semibold {{ $color }}">{{ $titulo }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $descripcion }}</p>
                    </article>
                @endforeach
            </div>

            {{-- CTA: acceso institucional (sin registro público) --}}
            <div class="mt-12 rounded-3xl bg-[#062e24] p-10 text-center shadow-xl shadow-emerald-950/20 sm:p-14">
                <h3 class="font-semibold text-white">Espacio de trabajo</h3>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-emerald-100/75">
                    Solo para personal de la entidad, contratistas e inspectores con cuenta habilitada.
                </p>
                <a href="{{ route('login') }}"
                   class="mt-7 inline-flex rounded-xl bg-white px-6 py-3 text-sm font-semibold text-emerald-700 shadow-md transition duration-200 hover:bg-emerald-50 hover:scale-[1.01] active:scale-[0.98]">
                    Ingresar
                </a>
            </div>
        </div>
    </section>

        </div>
    </details>
    </div>
    </main>
    {{-- ============================ FOOTER ============================ --}}
    <footer class="bg-[#041c16] text-slate-300">
        <div class="mx-auto grid max-w-6xl gap-4 px-4 py-6 sm:grid-cols-2">
            <div class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-xl bg-white/10 overflow-hidden">
                    <img src="{{ asset('imagenes/logo-vigia.png') }}" alt="" class="h-full w-auto object-contain">
                </span>
                <span class="text-sm font-semibold text-white">Vigía Ciudadano</span>
            </div>

            <details>
                <summary class="min-h-12 py-3 font-semibold text-white">Enlaces y acceso</summary>
                <nav aria-label="Enlaces informativos">
                    <ul class="space-y-1 text-sm">
                        <li><a href="#como-funciona" class="inline-flex min-h-12 items-center underline">Cómo funciona</a></li>
                        <li><a href="#transparencia" class="inline-flex min-h-12 items-center underline">Transparencia</a></li>
                        <li><a href="#metricas" class="inline-flex min-h-12 items-center underline">Métricas de gestión</a></li>
                        <li><a href="#participa" class="inline-flex min-h-12 items-center underline">Participa</a></li>
                        <li><a href="{{ route('login') }}" class="inline-flex min-h-12 items-center underline">Iniciar sesión</a></li>
                    </ul>
                </nav>
            </details>

            <details>
                <summary class="min-h-12 py-3 font-semibold text-white">Contacto y atención</summary>
                <ul class="space-y-2 text-sm">
                    <li><a href="mailto:contacto@vigiaciudadano.gov.co" class="inline-flex min-h-12 items-center underline">contacto@vigiaciudadano.gov.co</a></li>
                    <li><a href="tel:+576015550148" class="inline-flex min-h-12 items-center underline">PQRSD: (601) 555 0148</a></li>
                    <li>Lunes a viernes, 8:00–17:00</li>
                    <li>Alcaldía Municipal, piso 2</li>
                </ul>
            </details>

            <p class="text-sm leading-relaxed">Reportes y seguimiento abiertos a la ciudadanía.</p>
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-center text-xs text-slate-500">
            © {{ date('Y') }} Vigía Ciudadano
        </div>
    </footer>


    {{-- Pestañas del ejemplo de evidencia. La consulta ciudadana está en línea. --}}
    <script>
        (function () {
            'use strict';

            // --- Pestañas Antes / Durante / Después ---
            var tabButtons = document.querySelectorAll('[data-tab-btn]');
            var tabCards = document.querySelectorAll('[data-tab-card]');
            var tabLegends = document.querySelectorAll('[data-tab-legend]');

            function activateTab(key) {
                tabButtons.forEach(function (btn) {
                    var active = btn.getAttribute('data-tab-btn') === key;
                    btn.setAttribute('data-state', active ? 'active' : 'inactive');
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                    btn.tabIndex = active ? 0 : -1;
                });
                tabLegends.forEach(function (p) {
                    p.classList.toggle('hidden', p.getAttribute('data-tab-legend') !== key);
                });
                tabCards.forEach(function (card) {
                    var active = card.getAttribute('data-tab-card') === key;
                    card.hidden = !active;
                    card.classList.toggle('ring-4', active);
                    card.classList.toggle('opacity-100', active);
                });
            }

            tabButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activateTab(btn.getAttribute('data-tab-btn'));
                });
                btn.addEventListener('keydown', function (event) {
                    var buttons = Array.from(tabButtons);
                    var index = buttons.indexOf(btn);
                    var next;
                    if (event.key === 'ArrowRight') next = (index + 1) % buttons.length;
                    if (event.key === 'ArrowLeft') next = (index - 1 + buttons.length) % buttons.length;
                    if (event.key === 'Home') next = 0;
                    if (event.key === 'End') next = buttons.length - 1;
                    if (next === undefined) return;
                    event.preventDefault();
                    activateTab(buttons[next].getAttribute('data-tab-btn'));
                    buttons[next].focus();
                });
            });
            activateTab('antes');

        })();
    </script>

    {{-- ============ Leaflet: mapa interactivo de incidencias ============ --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        (function () {
            'use strict';

            var mapEl = document.getElementById('landing-map');
            if (!mapEl || typeof L === 'undefined') return;

            var statsEl = document.getElementById('landing-map-count');
            var apiUrl = @json(url('/api/v1/reports/map-data'));

            var map = L.map('landing-map', {
                zoomControl: true,
                scrollWheelZoom: false,
            }).setView([3.0095, -76.4849], 14);
            document.querySelector('.citizen-extras')?.addEventListener('toggle', function (event) {
                if (event.currentTarget.open) requestAnimationFrame(function () { map.invalidateSize(); });
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            var clusterGroup = L.markerClusterGroup({
                maxClusterRadius: 45,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                iconCreateFunction: function (cluster) {
                    var count = cluster.getChildCount();
                    var dim = count >= 50 ? 56 : (count >= 10 ? 48 : 38);
                    return L.divIcon({
                        html: '<div style="width:' + dim + 'px;height:' + dim + 'px;border-radius:50%;background:#1e40af;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.2);">' + count + '</div>',
                        className: '',
                        iconSize: L.point(dim, dim),
                    });
                }
            });

            function createIcon(color) {
                return L.divIcon({
                    html: '<div style="width:26px;height:26px;border-radius:50%;background:' + color + ';border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;">'
                        + '<div style="width:8px;height:8px;border-radius:50%;background:white;opacity:0.8;"></div>'
                        + '</div>',
                    className: '',
                    iconSize: L.point(26, 26),
                    iconAnchor: L.point(13, 13),
                    popupAnchor: L.point(0, -15),
                });
            }

            function escHtml(s) {
                return String(s).replace(/[&<>"']/g, function (c) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
                });
            }

            function buildPopup(r) {
                var photoHtml = r.photo_url
                    ? '<img src="' + escHtml(r.photo_url) + '" alt="Evidencia" style="width:100%;height:110px;object-fit:cover;border-radius:8px 8px 0 0;" loading="lazy">'
                    : '<div style="width:100%;height:60px;background:#f0faf5;display:flex;align-items:center;justify-content:center;border-radius:8px 8px 0 0;"><span style="font-size:24px;">📷</span></div>';

                var badgeColors = {
                    'reported': { bg: '#fef9c3', text: '#854d0e' },
                    'in_progress': { bg: '#dbeafe', text: '#1e40af' },
                    'under_inspection': { bg: '#ede9fe', text: '#5b21b6' },
                    'rework': { bg: '#fee2e2', text: '#991b1b' },
                    'closed': { bg: '#dcfce7', text: '#166534' },
                };
                var bc = badgeColors[r.status] || { bg: '#f1f5f9', text: '#475569' };

                return '<div style="font-family:-apple-system,BlinkMacSystemFont,sans-serif;">'
                    + photoHtml
                    + '<div style="padding:10px 12px;">'
                    + '<p style="font-family:monospace;font-size:16px;color:#4c6075;font-weight:600;margin:0;">' + escHtml(r.ticket_code) + '</p>'
                    + '<p style="font-size:18px;font-weight:700;color:#172b43;margin:8px 0;line-height:1.4;">' + escHtml(r.title) + '</p>'
                    + '<p style="font-size:16px;color:#4c6075;margin:0;">' + escHtml(r.category_name) + '</p>'
                    + '<span style="display:inline-block;padding:4px 8px;border-radius:9999px;font-size:16px;font-weight:600;margin-top:8px;background:' + bc.bg + ';color:' + bc.text + ';">' + escHtml(r.status_label) + '</span>'
                    + '<a href="' + escHtml(r.detail_url) + '" style="display:block;text-align:center;margin-top:12px;min-height:48px;padding:12px;background:#173d68;color:white;border-radius:8px;text-decoration:none;font-size:16px;font-weight:600;">Ver detalles</a>'
                    + '</div></div>';
            }

            // Cargar datos del endpoint público
            fetch(apiUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (res) { return res.ok ? res.json() : null; })
                .then(function (json) {
                    if (!json || !json.data) return;

                    var reports = json.data;
                    statsEl.textContent = reports.length;

                    reports.forEach(function (r) {
                        var marker = L.marker([r.latitude, r.longitude], { icon: createIcon(r.marker_color), title: r.ticket_code + ': ' + r.title, alt: r.ticket_code + ': ' + r.title });
                        marker.bindPopup(buildPopup(r), { maxWidth: 210, minWidth: 210, closeButton: true });
                        clusterGroup.addLayer(marker);
                    });

                    map.addLayer(clusterGroup);

                    if (reports.length > 0) {
                        var bounds = clusterGroup.getBounds();
                        if (bounds.isValid()) {
                            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                        }
                    }
                })
                .catch(function () {
                    // Sin red o endpoint no disponible: mapa vacío, no bloquea la landing
                });
        })();
    </script>
</body>
</html>
