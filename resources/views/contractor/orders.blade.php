@extends('layouts.app')
@section('title', 'Mis órdenes de trabajo — Vigía Ciudadano')
@section('content')
<div class="space-y-8">
    <x-page-heading title="Mis órdenes de trabajo" description="Inicia trabajos y registra las reparaciones desde el detalle.">
        <span class="status-badge bg-blue-50 text-blue-900 rounded-full px-4 py-2">{{ $orders->total() }} orden(es)</span>
    </x-page-heading>
    @if ($orders->isNotEmpty())<x-list-search target="work-list" placeholder="Título, radicado o categoría" />@endif
    <div id="work-list" class="work-grid">
    @forelse ($orders as $order)
        <article class="work-card" data-search-item>
            <div class="work-card-head">
                <div><p class="report-meta font-semibold">{{ $order->report->ticket_code }}</p><h2>{{ $order->report->title }}</h2></div>
                <x-status-badge :status="$order->status" />
            </div>
            <dl class="work-card-meta"><div><dt class="inline font-semibold">Categoría: </dt><dd class="inline">{{ $order->report->category->name ?? 'Sin categoría' }}</dd></div><div><dt class="inline font-semibold">Fecha programada: </dt><dd class="inline">{{ $order->scheduled_date->format('d/m/Y') }}</dd></div></dl>
            <div class="work-card-footer"><p class="report-meta">{{ $order->report->address_reference ?: 'Ubicación disponible en el detalle.' }}</p><x-button :href="route('reports.show', $order->report->ticket_code)">Gestionar trabajo<x-icon name="arrow" /></x-button></div>
        </article>
    @empty
        <div class="panel empty-state"><x-icon name="work" /><h2>No tienes órdenes asignadas</h2><p>Cuando la alcaldía te asigne un trabajo, aparecerá aquí con su fecha programada.</p></div>
    @endforelse
        <p hidden data-search-empty class="panel empty-state">No hay órdenes que coincidan en esta página. Prueba otro término o limpia la búsqueda.</p>
    </div>
    {{ $orders->links('pagination.institutional') }}
</div>
@endsection
