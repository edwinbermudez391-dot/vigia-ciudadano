<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vigía Ciudadano — Control social de la obra pública</title>
    <meta name="description" content="Plataforma municipal de veeduría ciudadana: reporta baches y obras públicas con trazabilidad total — radicación, reparación e inspección de calidad ante los ojos de la ciudad.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tipografía serif para titulares, con stack de reserva si la fuente no carga --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:500,600,700" rel="stylesheet">
    <style>
        .font-display { font-family: 'Playfair Display', ui-serif, Georgia, 'Times New Roman', serif; }
    </style>
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">

    {{-- ============================ HEADER ============================ --}}
    <header class="sticky top-0 z-40 border-b border-emerald-100 bg-white/85 backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-600/25">
                    {{-- Escudo con ojo vigilante --}}
                    <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
                        <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" fill="currentColor" opacity=".22"/>
                        <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" stroke="currentColor" stroke-width="1.4"/>
                        <path d="M7.8 11.6c1.1-1.7 2.7-2.6 4.2-2.6s3.1.9 4.2 2.6c-1.1 1.7-2.7 2.6-4.2 2.6s-3.1-.9-4.2-2.6Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
                        <circle cx="12" cy="11.6" r="1.15" fill="currentColor"/>
                    </svg>
                </span>
                <span class="leading-tight">
                    <span class="block text-sm font-semibold tracking-wide text-slate-900">Vigía Ciudadano</span>
                    <span class="block text-xs text-emerald-700">Control de obra pública</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 text-sm font-medium text-slate-600 lg:flex" aria-label="Navegación rápida">
                <a href="#como-funciona" class="rounded-lg px-3 py-2 transition duration-200 hover:bg-emerald-50 hover:text-emerald-800">Cómo funciona</a>
                <a href="#transparencia" class="rounded-lg px-3 py-2 transition duration-200 hover:bg-emerald-50 hover:text-emerald-800">Transparencia</a>
                <a href="#metricas" class="rounded-lg px-3 py-2 transition duration-200 hover:bg-emerald-50 hover:text-emerald-800">Métricas</a>
                <a href="#participa" class="rounded-lg px-3 py-2 transition duration-200 hover:bg-emerald-50 hover:text-emerald-800">Participa</a>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}"
                   class="rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition duration-200 hover:bg-emerald-50 sm:px-5">
                    Iniciar sesión
                </a>
                <a href="{{ route('reports.create') }}"
                   class="hidden rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition duration-200 hover:bg-emerald-700 active:scale-[0.98] sm:inline-flex">
                    Radicar Reporte
                </a>
            </div>
        </div>
    </header>

    {{-- ============================ HERO ============================ --}}
    <section class="relative overflow-hidden">
        {{-- Manchas suaves estilo Canva --}}
        <div class="pointer-events-none absolute -left-24 -top-24 size-96 rounded-full bg-emerald-100/70 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-16 top-1/3 size-80 rounded-full bg-sky-100/70 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/3 size-72 rounded-full bg-violet-100/60 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-4 py-14 lg:grid-cols-2 lg:py-20">
            {{-- Columna de mensaje --}}
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                    Control social&nbsp;•&nbsp;Obras públicas&nbsp;•&nbsp;Transparencia
                </p>

                <h1 class="font-display mt-6 text-4xl font-semibold leading-[1.15] text-slate-900 sm:text-5xl">
                    Vigía Ciudadano: <span class="text-emerald-700">cada bache, cada obra,</span> bajo la lupa de la ciudad
                </h1>

                <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600">
                    Reporta el deterioro de tu barrio con foto y ubicación exactas, sigue el despacho de la cuadrilla,
                    la reparación en sitio y la inspección de calidad final. Todo el ciclo, con evidencia pública y
                    trazabilidad verificable desde el minuto uno.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('reports.create') }}"
                       class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition duration-200 hover:bg-emerald-700 active:scale-[0.98]">
                        Radicar Reporte
                    </a>
                    <button type="button" data-open-ticket-modal
                            class="rounded-xl border border-emerald-200 bg-white px-5 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition duration-200 hover:bg-emerald-50">
                        🔎 Consultar Incidencia
                    </button>
                </div>

                <p class="mt-5 text-xs text-slate-500">
                    ingresa tu ticket <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-emerald-700">REP-AAAA-NNNN</code> para ver su trazabilidad completa.
                </p>
            </div>

            {{-- Mockup visual: mapa con pin + tarjeta móvil flotante --}}
            <div class="relative mx-auto w-full max-w-sm lg:mr-0 lg:ml-auto" aria-hidden="true">
                <div class="relative aspect-[4/5] overflow-hidden rounded-[2rem] border border-emerald-100 bg-emerald-50/70 shadow-xl shadow-emerald-900/10">
                    {{-- Callecitas del mapa --}}
                    <svg viewBox="0 0 400 500" class="absolute inset-0 h-full w-full">
                        <rect width="400" height="500" fill="#ecfdf5"/>
                        <g opacity=".65">
                            <rect x="18" y="22" width="150" height="112" rx="14" fill="#a7f3d0"/>
                            <rect x="212" y="22" width="170" height="112" rx="14" fill="#99f6e4"/>
                            <rect x="18" y="208" width="150" height="132" rx="14" fill="#99f6e4"/>
                            <rect x="212" y="208" width="170" height="132" rx="14" fill="#a7f3d0"/>
                            <rect x="18" y="408" width="364" height="76" rx="14" fill="#a7f3d0"/>
                        </g>
                        <path d="M0 182h400M0 378h400M195 0v500" stroke="#ffffff" stroke-width="26" stroke-linecap="round"/>
                        <path d="M0 182h400M0 378h400M195 0v500" stroke="#d1fae5" stroke-width="2"/>
                        <path d="M60 182v28M330 378v28" stroke="#fbbf24" stroke-width="6" stroke-linecap="round"/>
                        <circle cx="96" cy="60" r="7" fill="#ffffff" opacity=".8"/>
                        <circle cx="300" cy="300" r="7" fill="#ffffff" opacity=".8"/>
                    </svg>

                    {{-- Chip superior: monitoreo en vivo --}}
                    <span class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1.5 text-[11px] font-semibold text-emerald-800 shadow-sm ring-1 ring-emerald-100">
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Monitoreo en vivo
                    </span>

                    {{-- Pin con onda expansiva --}}
                    <div class="absolute left-1/2 top-[44%] -translate-x-1/2 -translate-y-1/2">
                        <span class="absolute left-1/2 top-1/2 size-20 -translate-x-1/2 -translate-y-1/2 animate-ping rounded-full bg-emerald-400/30"></span>
                        <span class="absolute left-1/2 top-1/2 size-10 -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/25"></span>
                        <svg viewBox="0 0 24 24" class="relative size-11 drop-shadow-md">
                            <path d="M12 2c-4.4 0-8 3.6-8 8 0 5.4 8 13 8 13s8-7.6 8-13c0-4.4-3.6-8-8-8Z" fill="#059669"/>
                            <circle cx="12" cy="10" r="3.2" fill="#ecfdf5"/>
                        </svg>
                    </div>
                    <span class="absolute left-1/2 top-[54%] -translate-x-1/2 whitespace-nowrap rounded-full bg-slate-900/85 px-3 py-1 text-[11px] font-medium text-white shadow">
                        Bache reportado · hace 2 h
                    </span>

                    {{-- Dirección en el pie del mapa --}}
                    <span class="absolute bottom-4 left-4 right-4 flex items-center justify-between rounded-xl bg-white/90 px-3 py-2 text-[11px] font-medium text-slate-600 shadow-sm ring-1 ring-emerald-100">
                        <span>📍 Cra 5 #28-14 · Centro</span>
                        <span class="text-emerald-700">radio 500 m</span>
                    </span>
                </div>

                {{-- Tarjeta móvil flotante: caso de ejemplo --}}
                <div class="absolute -bottom-8 left-2 w-64 rounded-2xl bg-white p-4 shadow-2xl shadow-emerald-950/25 ring-1 ring-emerald-100 sm:-left-8">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-mono text-[10px] text-slate-400">REP-2026-0148</p>
                            <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-800">Bache con filtración en la calzada</p>
                        </div>
                        {{-- Badge fiel a StatusUi::UnderInspection (violeta) --}}
                        <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-indigo-700">En inspección</span>
                    </div>

                    {{-- Barra de progreso del ciclo --}}
                    <div class="mt-4">
                        <div class="relative h-1.5 rounded-full bg-emerald-100">
                            <div class="absolute inset-y-0 left-0 w-3/4 rounded-full bg-gradient-to-r from-emerald-500 to-teal-400"></div>
                        </div>
                        <div class="mt-2 flex justify-between text-[9px] font-medium text-slate-400">
                            <span class="text-emerald-600">Reportado</span>
                            <span class="text-emerald-600">Ejecución</span>
                            <span class="text-violet-600">Inspección</span>
                            <span>Cerrado</span>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">Paso 3 de 4 · <b class="font-semibold text-slate-700">auditoría técnica en curso</b></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ====================== ¿CÓMO FUNCIONA? (4 pasos) ====================== --}}
    <section id="como-funciona" class="scroll-mt-24 border-y border-emerald-100 bg-emerald-50/40 py-16 lg:py-20">
        <div class="mx-auto max-w-6xl px-4">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-600">¿Cómo funciona?</p>
                <h2 class="font-display mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Un ciclo cerrado, cuatro estaciones visibles</h2>
                <p class="mt-4 text-slate-600">Cada incidencia recorre el mismo camino auditado. La ciudadanía acompaña el proceso en todas sus etapas, sin cajas negras.</p>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['01', '📸', 'Reporte Ciudadano', 'Reporta el daño con foto y georreferenciación exacta. Recibes un ticket REP-AAAA-NNNN al instante.'],
                    ['02', '🏛️', 'Asignación Municipal', 'La Alcaldía valida la incidencia y despacha la orden a una cuadrilla contratista con fecha programada.'],
                    ['03', '🚧', 'Reparación en Sitio', 'La cuadrilla ejecuta la obra y registra la foto de solución como evidencia de cierre técnico.'],
                    ['04', '🔍', 'Inspección de Calidad', 'Un inspector independiente audita y emite dictamen aprobado o retrabajo. La compuerta de calidad.'],
                ] as [$numero, $icono, $titulo, $descripcion])
                    <article class="relative rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm transition duration-200 hover:scale-[1.01] hover:shadow-md">
                        <span class="font-display text-3xl font-bold text-emerald-600">{{ $numero }}</span>
                        <span class="absolute right-5 top-5 grid size-11 place-items-center rounded-xl bg-emerald-50 text-lg">{{ $icono }}</span>
                        <h3 class="mt-3 text-base font-semibold text-slate-900">{{ $titulo }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $descripcion }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ TRANSPARENCIA Y COMPUERTA DE CALIDAD (bloque verde bosque) ============ --}}
    <section id="transparencia" class="scroll-mt-24 px-4 py-16 lg:py-20">
        <div class="mx-auto max-w-6xl overflow-hidden rounded-3xl bg-[#062e24] p-8 text-white shadow-xl shadow-emerald-950/30 lg:p-12">
            <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
                {{-- Columna principal --}}
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">Evidencia pública</p>
                    <h2 class="font-display mt-3 text-3xl font-semibold sm:text-4xl">Transparencia y compuerta de calidad</h2>
                    <p class="mt-4 max-w-2xl leading-relaxed text-emerald-100/80">
                        Ningún peso público se cierra sin tres fotos: el daño original, la obra ejecutada y el sello
                        del dictamen técnico. Explora el caso REP-2026-0148 en sus tres momentos.
                    </p>

                    {{-- Pestañas Antes / Durante / Después --}}
                    <div role="tablist" aria-label="Momentos de la evidencia" class="mt-7 inline-flex rounded-xl bg-white/10 p-1">
                        <button type="button" role="tab" data-tab-btn="antes" data-state="active" aria-selected="true" aria-controls="card-dano"
                                class="rounded-lg px-4 py-2 text-sm text-emerald-50/70 transition duration-200 hover:text-white data-[state=active]:bg-white data-[state=active]:font-semibold data-[state=active]:text-emerald-900 data-[state=active]:shadow-sm">
                            Antes
                        </button>
                        <button type="button" role="tab" data-tab-btn="durante" aria-selected="false" aria-controls="card-obra"
                                class="rounded-lg px-4 py-2 text-sm text-emerald-50/70 transition duration-200 hover:text-white data-[state=active]:bg-white data-[state=active]:font-semibold data-[state=active]:text-emerald-900 data-[state=active]:shadow-sm">
                            Durante
                        </button>
                        <button type="button" role="tab" data-tab-btn="despues" aria-selected="false" aria-controls="card-sello"
                                class="rounded-lg px-4 py-2 text-sm text-emerald-50/70 transition duration-200 hover:text-white data-[state=active]:bg-white data-[state=active]:font-semibold data-[state=active]:text-emerald-900 data-[state=active]:shadow-sm">
                            Después
                        </button>
                    </div>

                    {{-- Leyenda dinámica por pestaña --}}
                    <p class="mt-4 min-h-10 text-sm text-emerald-100/75" data-tab-legend="antes">📷 12/09 · Foto del daño original con coordenadas y hora del GPS intactas.</p>
                    <p class="mt-4 hidden min-h-10 text-sm text-emerald-100/75" data-tab-legend="durante">🚧 20/09 · Cuadrilla en sitio: parcheo asfáltico con foto de ejecución firmada por el residente.</p>
                    <p class="mt-4 hidden min-h-10 text-sm text-emerald-100/75" data-tab-legend="despues">✅ 23/09 · Sello de verificación técnica: dictamen APROBADO, medición de espesor conforme.</p>

                    {{-- Tarjetas de evidencia --}}
                    <div class="mt-6 grid gap-5 sm:grid-cols-3">
                        <article id="card-dano" data-tab-card="antes"
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
                            <h3 class="mt-3 text-sm font-semibold text-slate-900">Foto Daño Original</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">Prueba ciudadana georreferenciada al momento de la radicación.</p>
                        </article>

                        <article id="card-obra" data-tab-card="durante"
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
                            <h3 class="mt-3 text-sm font-semibold text-slate-900">Foto Obra Ejecutada</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">Evidencia de la cuadrilla al finalizar la reparación en sitio.</p>
                        </article>

                        <article id="card-sello" data-tab-card="despues"
                                 class="rounded-2xl bg-white/95 p-4 text-slate-700 ring-emerald-300 transition duration-200">
                            <div class="grid h-28 place-items-center rounded-xl bg-slate-100">
                                <svg viewBox="0 0 120 120" class="h-20 w-20" role="img" aria-label="Sello de verificación técnica: aprobado">
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="#059669" stroke-width="4" stroke-dasharray="7 5"/>
                                    <circle cx="60" cy="60" r="40" fill="#ecfdf5" stroke="#059669" stroke-width="2.5"/>
                                    <path d="m44 60 11 12 22-26" stroke="#047857" stroke-width="7" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                    <text x="60" y="97" font-size="8" text-anchor="middle" fill="#047857" font-weight="bold">ACTA Nº 482</text>
                                </svg>
                            </div>
                            <h3 class="mt-3 text-sm font-semibold text-slate-900">Sello de Verificación Técnica</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">Dictamen del inspector: compuerta que aprueba o devuelve la obra.</p>
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
                        <h3 class="text-sm font-semibold text-white">Línea de tiempo de auditoría</h3>
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
    <section id="metricas" class="scroll-mt-24 border-y border-emerald-100 bg-emerald-50/40 py-16 lg:py-20">
        <div class="mx-auto max-w-6xl px-4">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-600">Pulso del municipio</p>
                <h2 class="font-display mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Métricas abiertas de gestión</h2>
                <p class="mt-4 text-slate-600">Cálculo directo sobre el expediente público de incidencias, órdenes de trabajo y dictámenes de calidad.</p>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['🟠', 'Incidencias Activas', number_format($stats['active']), 'abiertas en el ciclo actual'],
                    ['🚧', 'Obras en Ejecución', number_format($stats['orders_executing']), 'órdenes despachadas a cuadrilla'],
                    ['✅', 'Tasa de Aprobación', $stats['approval_rate'] !== null ? $stats['approval_rate'] . '%' : '—', 'dictámenes conformes'],
                    ['⏱️', 'SLA Promedio', $stats['sla_days'] !== null ? number_format($stats['sla_days'], 1) . ' días' : '—', 'de la radicación al cierre'],
                ] as [$icono, $etiqueta, $valor, $pie])
                    <article class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm transition duration-200 hover:scale-[1.01] hover:shadow-md">
                        <div class="flex items-center gap-2 text-sm font-medium text-slate-500">
                            <span>{{ $icono }}</span> {{ $etiqueta }}
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
                            <stop offset="0" stop-color="#059669" stop-opacity=".28"/>
                            <stop offset="1" stop-color="#059669" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    {{-- Cuadrícula + escala --}}
                    @for ($g = 0; $g <= 3; $g++)
                        @php $gy = $yAt((int) round($chartMax * $g / 3)); $gv = (int) round($chartMax * $g / 3); @endphp
                        <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $chartW - $padX }}" y2="{{ $gy }}" stroke="#e2e8f0" stroke-dasharray="4 5"/>
                        <text x="{{ $padX - 4 }}" y="{{ $gy + 3 }}" text-anchor="end" font-size="10" fill="#94a3b8">{{ $gv }}</text>
                    @endfor

                    <path d="{{ $areaPath($trend['created']) }}" fill="url(#area-radicados)"/>
                    <polyline points="{{ $linePoints($trend['created']) }}" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="{{ $linePoints($trend['closed']) }}" fill="none" stroke="#0ea5e9" stroke-width="3" stroke-dasharray="7 6" stroke-linecap="round"/>

                    @foreach ($trend['created'] as $i => $v)
                        <circle cx="{{ round($xAt($i), 1) }}" cy="{{ round($yAt($v), 1) }}" r="4.5" fill="#ffffff" stroke="#059669" stroke-width="2.5">
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

                <p class="mt-4 text-xs text-slate-500">Fuente: expediente público de la plataforma · se actualiza con cada radicación y cada cierre de obra.</p>
            </div>
        </div>
    </section>

    {{-- ============================ PARTICIPA ============================ --}}
    <section id="participa" class="scroll-mt-24 px-4 py-16 lg:py-20">
        <div class="mx-auto max-w-6xl">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-600">Participa en el control social</p>
                <h2 class="font-display mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Control social abierto, veeduría activa</h2>
                <p class="mt-4 text-slate-600">Cualquier habitante puede auditar y radicar reportes sin registro previo. La plataforma articula ciudadanía, contratistas y funcionarios alrededor del mismo expediente.</p>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['👁️', 'Control Social Abierto', 'text-emerald-600', 'bg-emerald-50', 'Cualquier ciudadano puede radicar reportes de daños en la vía pública sin necesidad de crear una cuenta. Sin fricción, sin registro.'],
                    ['🧰', 'Contratista', 'text-sky-600', 'bg-sky-50', 'Recibe órdenes despachadas, agenda la ejecución en sitio y carga la foto de solución como evidencia.'],
                    ['🏛️', 'Funcionario Municipal', 'text-violet-600', 'bg-violet-50', 'Valida incidencias, asigna cuadrillas, audita calidad y monitorea el pulso operativo del municipio.'],
                ] as [$icono, $titulo, $color, $fondo, $descripcion])
                    <article class="rounded-2xl border border-emerald-100 bg-white p-7 shadow-sm transition duration-200 hover:scale-[1.01] hover:shadow-md">
                        <span class="grid size-12 place-items-center rounded-2xl {{ $fondo }} text-2xl">{{ $icono }}</span>
                        <h3 class="mt-4 text-lg font-semibold {{ $color }}">{{ $titulo }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $descripcion }}</p>
                    </article>
                @endforeach
            </div>

            {{-- CTA: acceso institucional (sin registro público) --}}
            <div class="mt-12 rounded-3xl bg-[#062e24] p-10 text-center shadow-xl shadow-emerald-950/20 sm:p-14">
                <h3 class="font-display text-2xl font-semibold text-white sm:text-3xl">¿Eres personal institucional autorizado?</h3>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-emerald-100/75">
                    El acceso al sistema está restringido a personal de Alcaldía, contratistas e inspectores de calidad. Si tu cuenta fue habilitada, ingresa con tus credenciales.
                </p>
                <a href="{{ route('login') }}"
                   class="mt-7 inline-flex rounded-xl bg-white px-6 py-3 text-sm font-semibold text-emerald-700 shadow-md transition duration-200 hover:bg-emerald-50 hover:scale-[1.01] active:scale-[0.98]">
                    Acceso Institucional (Personal Autorizado)
                </a>
            </div>
        </div>
    </section>

    {{-- ============================ FOOTER ============================ --}}
    <footer class="bg-[#041c16] text-slate-300">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="grid size-9 place-items-center rounded-xl bg-emerald-500/20 text-emerald-300">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M12 2 4 5v6.2c0 4.9 3.4 8.6 8 10.8 4.6-2.2 8-5.9 8-10.8V5l-8-3Z" stroke="currentColor" stroke-width="1.4"/>
                            <circle cx="12" cy="11" r="2.4" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="text-sm font-semibold text-white">Vigía Ciudadano</span>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">
                    Plataforma de control social y veeduría de obra pública municipal. Cada reporte deja evidencia pública, auditable e inmodificable.
                </p>
            </div>

            <nav aria-label="Enlaces informativos">
                <h4 class="text-sm font-semibold text-white">Navegación</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-400">
                    <li><a href="#como-funciona" class="transition duration-200 hover:text-emerald-300">Cómo funciona</a></li>
                    <li><a href="#transparencia" class="transition duration-200 hover:text-emerald-300">Transparencia</a></li>
                    <li><a href="#metricas" class="transition duration-200 hover:text-emerald-300">Métricas de gestión</a></li>
                    <li><a href="#participa" class="transition duration-200 hover:text-emerald-300">Participa</a></li>
                    <li><a href="{{ route('login') }}" class="transition duration-200 hover:text-emerald-300">Iniciar sesión</a></li>
                </ul>
            </nav>

            <div>
                <h4 class="text-sm font-semibold text-white">Canales oficiales</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-400">
                    <li>✉️ contacto@vigiaciudadano.gov.co</li>
                    <li>📞 Línea PQRSD: (601) 555 0148</li>
                    <li>🕗 Atención: lunes a viernes, 8:00 – 17:00</li>
                    <li>🏛️ Ventanilla única: Alcaldía Municipal, piso 2</li>
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-white">Aviso de veeduría</h4>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">
                    Los expedientes de incidencias, órdenes de trabajo y dictámenes de calidad son <b class="font-semibold text-emerald-300">información pública</b>
                    en los términos de la ley de transparencia. Su difusión y auditoría ciudadana es el propósito central de este programa.
                </p>
            </div>
        </div>

        <div class="border-t border-white/10 py-5 text-center text-xs text-slate-500">
            © {{ date('Y') }} Vigía Ciudadano · La obra pública, vigilada por todos.
        </div>
    </footer>

    {{-- ============ MODAL: consulta rápida de ticket ============ --}}
    <div id="ticket-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="ticket-modal-title">
        <div class="absolute inset-0 bg-emerald-950/60 backdrop-blur-sm" data-close-ticket-modal></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h3 id="ticket-modal-title" class="font-display text-xl font-semibold text-slate-900">Consultar Incidencia</h3>
            <p class="mt-2 text-sm text-slate-600">Ingresa el código que recibiste al radicar tu reporte.</p>

            <form id="ticket-form" class="mt-5">
                <label for="ticket-code" class="mb-1.5 block text-sm font-medium text-slate-700">Código de ticket</label>
                <input id="ticket-code" type="text" autocomplete="off" placeholder="REP-2026-0148"
                       spellcheck="false"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm uppercase text-slate-800 outline-none transition duration-200 placeholder:normal-case placeholder:font-sans placeholder:text-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/25">
                <p id="ticket-error" class="mt-1.5 hidden text-xs text-rose-600">Formato inválido. Ejemplo válido: REP-2026-0148.</p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-close-ticket-modal
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition duration-200 hover:bg-emerald-700 active:scale-[0.98]">
                        Consultar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ JS de la landing: pestañas de evidencia + modal de ticket ============ --}}
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
                });
                tabLegends.forEach(function (p) {
                    p.classList.toggle('hidden', p.getAttribute('data-tab-legend') !== key);
                });
                tabCards.forEach(function (card) {
                    var active = card.getAttribute('data-tab-card') === key;
                    card.classList.toggle('ring-4', active);
                    card.classList.toggle('opacity-100', active);
                    card.classList.toggle('opacity-60', !active);
                    card.classList.toggle('-translate-y-1', active);
                });
            }

            tabButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activateTab(btn.getAttribute('data-tab-btn'));
                });
            });
            activateTab('antes');

            // --- Modal de consulta rápida de ticket ---
            var modal = document.getElementById('ticket-modal');
            var form = document.getElementById('ticket-form');
            var input = document.getElementById('ticket-code');
            var error = document.getElementById('ticket-error');
            var consultBase = @json(route('reports.show', ['ticket_code' => '__CODE__']));

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                input.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                error.classList.add('hidden');
            }

            document.querySelectorAll('[data-open-ticket-modal]').forEach(function (btn) {
                btn.addEventListener('click', openModal);
            });
            document.querySelectorAll('[data-close-ticket-modal]').forEach(function (btn) {
                btn.addEventListener('click', closeModal);
            });
            document.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });

            form.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var code = input.value.trim().toUpperCase();
                if (!/^REP-\d{4}-\d{4,5}$/.test(code)) {
                    error.classList.remove('hidden');
                    input.focus();
                    return;
                }
                // Invitado → el middleware auth pide login y recuerda el destino;
                // tras autenticarse cae directo en la trazabilidad del ticket.
                window.location.href = consultBase.replace('__CODE__', encodeURIComponent(code));
            });
        })();
    </script>
</body>
</html>
