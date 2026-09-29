@extends('layouts.app')

@section('title', 'Panel de control — Vigía Ciudadano')

@section('content')
@php
    $cards = [
        ['label' => 'Incidencias activas', 'value' => $stats['open'], 'icon' => '🔥', 'tone' => 'text-amber-700 border-amber-200 bg-amber-50'],
        ['label' => 'Contratistas en obra', 'value' => $stats['in_progress'], 'icon' => '⚙️', 'tone' => 'text-sky-800 border-sky-200 bg-sky-50'],
        ['label' => 'Esperando dictamen', 'value' => $stats['under_inspection'], 'icon' => '🔍', 'tone' => 'text-indigo-700 border-violet-200 bg-violet-50'],
        ['label' => 'En retrabajo', 'value' => $stats['rework'], 'icon' => '↩️', 'tone' => 'text-orange-700 border-orange-200 bg-orange-50'],
        ['label' => 'Cerradas hoy', 'value' => $stats['closed_today'], 'icon' => '✅', 'tone' => 'text-emerald-700 border-emerald-200 bg-emerald-50'],
    ];
    $overdue = $stats['orders_pending'];
    $quality = $dictamenes['total'] > 0
        ? round(100 - (100 * $dictamenes['rework'] / $dictamenes['total']))
        : 100;
@endphp

<div class="space-y-8">
    <header>
        <h1 class="text-2xl font-semibold tracking-tight text-[#062e24]">Pulso del municipio</h1>
        <p class="mt-1 text-sm text-[#1f5142]">
            Estado operativo del ciclo de incidencias urbanas.
        </p>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($cards as $card)
            <div class="rounded-2xl border p-5 {{ $card['tone'] }} transition duration-200 hover:scale-[1.02]">
                <div class="flex items-center justify-between">
                    <span class="text-xl">{{ $card['icon'] }}</span>
                    <span class="text-3xl font-bold tabular-nums">{{ $card['value'] }}</span>
                </div>
                <p class="mt-3 text-xs font-medium opacity-80">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 lg:col-span-2">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Últimas incidencias
            </h2>

            <ul class="divide-y divide-[#d5e8e0]">
                @foreach ($recent as $report)
                    @php
                        // El despacho solo aplica a radicados nuevos o retrabajos
                        // (invariante de WorkOrderDispatcher; la UI lo refleja).
                        $assignable = in_array($report->status, [
                            \App\Enums\ReportStatus::Reported,
                            \App\Enums\ReportStatus::Rework,
                        ], true);
                    @endphp
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('reports.show', $report->ticket_code) }}"
                               class="block truncate text-sm font-medium text-[#062e24] hover:text-[#087b5f]">
                                {{ $report->title }}
                            </a>
                            <p class="mt-0.5 font-mono text-[11px] text-[#5d7d70]">
                                {{ $report->ticket_code }} · {{ $report->category->name ?? '—' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-status-badge :status="$report->status" />
                            @if ($assignable)
                                <button type="button" data-assign-open
                                        data-report-id="{{ $report->id }}"
                                        data-action="{{ route('admin.assign', $report) }}"
                                        data-label="{{ $report->ticket_code }} — {{ $report->title }}"
                                        class="bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-4 py-2.5 rounded-xl transition text-xs">
                                    👷 Asignar
                                </button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="space-y-6">
            <div class="bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                    Calidad de la obra
                </h2>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-[#087b5f]">{{ $quality }}%</span>
                    <span class="pb-1 text-xs text-[#5d7d70]">dictámenes aprobados</span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#d5e8e0]">
                    <div class="h-full rounded-full bg-gradient-to-r from-[#087b5f] to-[#05664e]" style="width: {{ $quality }}%"></div>
                </div>
                <p class="mt-3 text-xs text-[#5d7d70]">
                    {{ $dictamenes['total'] }} inspecciones · {{ $dictamenes['rework'] }} reprocesos exigidos
                </p>
            </div>

            @if ($overdue > 0)
                <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 text-orange-700">
                    <p class="text-sm font-semibold">👷 {{ $overdue }} orden(es) asignada(s) sin iniciar</p>
                    <p class="mt-1 text-xs opacity-80">
                        Contratistas que aún no reportan inicio de obra. Revisa en la trazabilidad de cada radicado.
                    </p>
                </div>
            @endif
        </section>
    </div>

    {{-- Modal único de despacho: los botones [data-assign-open] le inyectan
         la acción del reporte antes de mostrarlo (sin clonar form × N). --}}
    <x-assign-modal :contractors="$contractors" />
</div>
@endsection
