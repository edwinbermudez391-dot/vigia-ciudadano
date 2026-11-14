@extends('layouts.app')
@section('title', 'Inspecciones pendientes — Vigía Ciudadano')
@section('content')
<div class="space-y-8">
    <x-page-heading title="Inspecciones pendientes" description="Revisa la reparación antes de aprobar el cierre.">
        <span class="status-badge rounded-full border border-violet-200 bg-violet-50 px-4 py-2 text-indigo-800">{{ $reports->total() }} en cola</span>
    </x-page-heading>
    @if ($reports->isNotEmpty())<x-list-search target="inspection-list" placeholder="Radicado, título o contratista" />@endif
    <div id="inspection-list" class="work-grid">
    @forelse ($reports as $report)
        @php
            $order = $report->workOrder;
            $storage = \Illuminate\Support\Facades\Storage::disk('public');
            $previous = $order->qualityInspections->sortByDesc('inspected_at')->first();
        @endphp
        <article class="work-card" data-search-item>
            <div class="work-card-head"><div><a class="report-meta font-semibold underline underline-offset-4" href="{{ route('reports.show', $report->ticket_code) }}">{{ $report->ticket_code }}</a><h2>{{ $report->title }}</h2><p class="report-meta">{{ $report->category->name }} · {{ $order->contractor->name ?? 'Sin contratista' }}</p></div><x-status-badge :status="$report->status" /></div>
            <div class="inspection-evidence mt-6">
                <x-photo-card label="Daño reportado" :url="$report->damage_photo_url ? $storage->url($report->damage_photo_url) : null">Evidencia ciudadana original</x-photo-card>
                <x-photo-card label="Solución entregada" :url="$order->solution_photo_url ? $storage->url($order->solution_photo_url) : null">Reparación registrada por el contratista</x-photo-card>
            </div>
            @if ($previous)<p class="notice border-amber-200 bg-amber-50 text-amber-900 mt-6">Esta obra ya tuvo {{ $order->qualityInspections->count() }} inspección(es). Revisa las correcciones antes de emitir una nueva decisión.</p>@endif
            <div class="work-card-footer mt-6"><p class="report-meta">Verifica los materiales, la seguridad y la evidencia en el lugar de la obra.</p><x-button :href="route('inspector.audit', $order->id)">Revisar obra<x-icon name="arrow" /></x-button></div>
        </article>
    @empty
        <div class="panel empty-state"><x-icon name="check" /><h2>No hay inspecciones pendientes</h2><p>Las obras terminadas aparecerán aquí cuando estén listas para revisar.</p></div>
    @endforelse
        <p hidden data-search-empty class="panel empty-state">No hay inspecciones que coincidan en esta página. Prueba otro término o limpia la búsqueda.</p>
    </div>
    {{ $reports->links('pagination.institutional') }}
</div>
@endsection
