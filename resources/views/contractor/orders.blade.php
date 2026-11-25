@extends('layouts.app')
@section('title', 'Mis órdenes de trabajo — Vigía Ciudadano')
@section('content')
<div class="space-y-8">
    <x-page-heading title="Mis órdenes de trabajo" description="Inicia trabajos y registra las reparaciones desde el detalle.">
        <span class="status-badge bg-blue-50 text-blue-900 rounded-full px-4 py-2">{{ $totalOrders }} {{ $totalOrders === 1 ? 'orden' : 'órdenes' }}</span>
    </x-page-heading>
    @if ($groups)
        <div class="space-y-4">
            <x-list-search target="work-list" placeholder="Título, radicado o categoría" />
            <x-status-switcher :groups="$groups" :status-enum="\App\Enums\WorkOrderStatus::class" :open-status="$openStatus" panel-prefix="ordenes" label="Estados de las órdenes" />
        </div>
    @endif

    <div id="work-list" class="space-y-4">
    @forelse ($groups as $state => $orders)
        @php($status = \App\Enums\WorkOrderStatus::from($state))
        <details id="ordenes-{{ $state }}" name="work-order-status-groups" class="group scroll-mt-6" data-search-group @if ($openStatus === $state) open @endif>
            <summary class="panel mb-4 flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden">
                <x-status-badge :status="$status" />
                <span class="flex items-center gap-3"><span class="report-meta">{{ $orders->total() }} {{ $orders->total() === 1 ? 'orden' : 'órdenes' }}</span><x-icon name="arrow" class="text-blue-900 group-open:rotate-90" /></span>
            </summary>
            <div class="space-y-4">
                @if ($status === \App\Enums\WorkOrderStatus::Assigned)
                    <p class="report-meta">Orden de llegada: primero las que recibiste antes.</p>
                @endif
                <div class="work-grid">
                    @foreach ($orders as $order)
                        <article class="work-card" data-search-item>
                            <div class="work-card-head">
                                <div><p class="report-meta font-semibold">{{ $order->report->ticket_code }}</p><h2>{{ $order->report->title }}</h2></div>
                                <x-status-badge :status="$order->status" />
                            </div>
                            <dl class="work-card-meta">
                                <div><dt class="inline font-semibold">Categoría: </dt><dd class="inline">{{ $order->report->category->name ?? 'Sin categoría' }}</dd></div>
                                @if ($status === \App\Enums\WorkOrderStatus::Assigned)
                                    <div><dt class="inline font-semibold">Recibida: </dt><dd class="inline">{{ ($order->assigned_at ?? $order->created_at)?->copy()->tz('America/Bogota')->format('d/m/Y H:i') ?? 'Sin fecha' }}</dd></div>
                                @endif
                                <div><dt class="inline font-semibold">Fecha programada: </dt><dd class="inline">{{ $order->scheduled_date->format('d/m/Y') }}</dd></div>
                            </dl>
                            <div class="work-card-footer"><p class="report-meta">{{ $order->report->address_reference ?: 'Ubicación disponible en el detalle.' }}</p><x-button :href="route('reports.show', $order->report->ticket_code)">Gestionar trabajo<x-icon name="arrow" /></x-button></div>
                        </article>
                    @endforeach
                    <p hidden data-search-empty class="panel empty-state">No hay órdenes que coincidan en esta página. Prueba otro término o limpia la búsqueda.</p>
                </div>
                {{ $orders->links('pagination.institutional') }}
            </div>
        </details>
    @empty
        <div class="panel empty-state"><x-icon name="work" /><h2>No tienes órdenes asignadas</h2><p>Cuando la alcaldía te asigne un trabajo, aparecerá aquí con su fecha programada.</p></div>
    @endforelse
    </div>
</div>
@endsection
