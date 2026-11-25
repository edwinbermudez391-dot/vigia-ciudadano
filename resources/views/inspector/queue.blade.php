@extends('layouts.app')
@section('title', 'Inspecciones — Vigía Ciudadano')
@section('content')
@php
    $crewQuery = request()->has('per_page') ? ['per_page' => $pending->perPage()] : [];
    $sections = [
        ['pendientes', 'Inspecciones pendientes', $pending],
        ['cerrados', 'Reportes cerrados', $closedReports],
    ];
@endphp
<div class="space-y-8">
    <x-page-heading title="Inspecciones" description="Revisa las obras pendientes. Los reportes cerrados aparecen al final." />

    @if ($contractors->isNotEmpty())
        <nav class="flex flex-wrap gap-2" aria-label="Filtrar inspecciones por cuadrilla">
            <x-button :href="route('inspector.queue', $crewQuery)" :variant="$crewId === null ? 'primary' : 'secondary'"
                      :aria-current="$crewId === null ? 'true' : null">Todas las cuadrillas</x-button>
            @foreach ($contractors as $contractor)
                <x-button :href="route('inspector.queue', $crewQuery + ['cuadrilla' => $contractor->id])"
                          :variant="$crewId === $contractor->id ? 'primary' : 'secondary'"
                          :aria-current="$crewId === $contractor->id ? 'true' : null">{{ $contractor->name }}</x-button>
            @endforeach
        </nav>
    @endif

    @foreach ($sections as $section)
        @php
            [$id, $label, $reports] = $section;
            $closed = $id === 'cerrados';
        @endphp
        <section id="{{ $id }}" class="space-y-4 scroll-mt-6" aria-labelledby="inspecciones-{{ $id }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="inspecciones-{{ $id }}">{{ $label }}</h2>
                <span class="report-meta tabular-nums">{{ $reports->total() }} {{ $reports->total() === 1 ? 'reporte' : 'reportes' }}</span>
            </div>
            <div class="work-grid">
            @forelse ($reports as $report)
                @php
                    $order = $report->workOrder;
                    $storage = \Illuminate\Support\Facades\Storage::disk('public');
                    $inspections = $order?->quality_inspections_count ?? 0;
                @endphp
                <article class="work-card">
                    <div class="work-card-head"><div><a class="report-meta font-semibold underline underline-offset-4" href="{{ route('reports.show', $report->ticket_code) }}">{{ $report->ticket_code }}</a><h2>{{ $report->title }}</h2><p class="report-meta">{{ $report->category?->name ?? 'Sin categoría' }} · {{ $order?->contractor?->name ?? 'Sin contratista' }}</p></div><x-status-badge :status="$report->status" /></div>
                    <div class="inspection-evidence mt-6">
                        <x-photo-card label="Daño reportado" :url="$report->damage_photo_url ? $storage->url($report->damage_photo_url) : null">Evidencia ciudadana original</x-photo-card>
                        <x-photo-card label="Solución entregada" :url="$order?->solution_photo_url ? $storage->url($order->solution_photo_url) : null">Reparación registrada por el contratista</x-photo-card>
                    </div>
                    @if (!$closed && $inspections > 0)<p class="notice border-amber-200 bg-amber-50 text-amber-900 mt-6">Esta obra ya tuvo {{ $inspections }} inspección(es). Revisa las correcciones antes de emitir una nueva decisión.</p>@endif
                    <div class="work-card-footer mt-6">
                        @if ($closed || $order === null)
                            <x-button :href="route('reports.show', $report->ticket_code)" variant="secondary">Ver detalle<x-icon name="arrow" /></x-button>
                        @else
                            <p class="report-meta">Verifica los materiales, la seguridad y la evidencia en el lugar de la obra.</p>
                            <x-button :href="route('inspector.audit', $order->id)">Revisar obra<x-icon name="arrow" /></x-button>
                        @endif
                    </div>
                </article>
            @empty
                <div class="panel empty-state"><x-icon name="check" /><h2>{{ $closed ? 'No hay reportes cerrados' : 'No hay inspecciones pendientes' }}</h2><p>{{ $crewId !== null ? 'Elige otra cuadrilla o pulsa «Todas las cuadrillas».' : ($closed ? 'Los reportes cerrados aparecerán aquí.' : 'Las obras terminadas aparecerán aquí cuando estén listas para revisar.') }}</p></div>
            @endforelse
            </div>
            {{ $reports->links('pagination.institutional') }}
        </section>
    @endforeach
</div>
@endsection
