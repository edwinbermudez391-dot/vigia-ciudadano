@extends('layouts.app')
@section('title', 'Inicio — Vigía Ciudadano')
@section('content')
<div class="space-y-8">
    <x-page-heading title="Gestión ciudadana" description="Organiza la atención de los reportes.">
        <x-button :href="route('admin.analytics')" variant="secondary">Ver estadísticas</x-button>
    </x-page-heading>

    <section class="stats-grid" aria-label="Resumen de reportes de todo el municipio">
        <x-stat-card label="Total de reportes" :value="$stats['total']" />
        <x-stat-card label="Reportes pendientes" :value="$stats['pending']" description="Por asignar" tone="amber" />
        <x-stat-card label="Trabajos en proceso" :value="$stats['in_progress']" description="Asignados o en ejecución" />
        <x-stat-card label="Reportes solucionados" :value="$stats['closed']" description="Con calidad aprobada" tone="green" />
    </section>

    <section class="support-grid" aria-label="Seguimiento operativo">
        <article class="panel"><h2>Inspección de calidad</h2><p><strong>{{ $stats['under_inspection'] }}</strong> reportes esperan revisión.</p><p class="report-meta mt-2">{{ $dictamenes['total'] }} inspecciones registradas · {{ $dictamenes['rework'] }} con correcciones solicitadas.</p><p class="report-meta mt-2">@if ($dictamenes['total'] > 0) {{ round(100 * ($dictamenes['total'] - $dictamenes['rework']) / $dictamenes['total']) }} % de inspecciones aprobadas. @else Aún no hay inspecciones para calcular la aprobación. @endif</p></article>
        <article class="panel"><h2>Órdenes por iniciar</h2><p><strong>{{ $stats['orders_pending'] }}</strong> órdenes asignadas aún no han iniciado.</p><p class="report-meta mt-2">{{ $stats['closed_today'] }} reportes cerrados hoy · {{ $stats['contractors'] }} contratistas activos.</p></article>
    </section>
</div>
@endsection
