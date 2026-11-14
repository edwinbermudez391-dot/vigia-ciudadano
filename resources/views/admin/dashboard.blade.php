@extends('layouts.app')
@section('title', 'Inicio — Vigía Ciudadano')
@section('content')
<div class="space-y-8">
    <x-page-heading title="Gestión ciudadana" description="Organiza la atención de los reportes.">
        <x-button :href="route('admin.analytics')" variant="secondary">Ver estadísticas</x-button>
        <x-button href="#reportes">Gestionar reportes</x-button>
    </x-page-heading>

    <section class="stats-grid" aria-label="Resumen de reportes de todo el municipio">
        <x-stat-card label="Total de reportes" :value="$stats['total']" />
        <x-stat-card label="Reportes pendientes" :value="$stats['pending']" description="Por asignar" tone="amber" />
        <x-stat-card label="Trabajos en proceso" :value="$stats['in_progress']" description="Asignados o en ejecución" />
        <x-stat-card label="Reportes solucionados" :value="$stats['closed']" description="Con calidad aprobada" tone="green" />
    </section>

    <section class="quick-actions" aria-label="Tareas frecuentes">
        <a href="{{ route('admin.dashboard', ['status' => 'reported']) }}#reportes"><span class="action-icon"><x-icon name="report" /></span><strong>Asignar pendientes</strong><x-icon name="arrow" /></a>
        <a href="{{ route('admin.contractors') }}"><span class="action-icon"><x-icon name="users" /></span><strong>Contratistas</strong><x-icon name="arrow" /></a>
    </section>
    <section class="panel" id="reportes" aria-labelledby="reports-title">
        <div class="panel-header">
            <div>
                <h2 id="reports-title">Reportes ciudadanos</h2>
                <p>Más recientes primero.</p>
            </div>
            <span class="report-meta">{{ $recent->total() }} resultado(s)</span>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}#reportes" class="filter-form" role="search">
            <div>
                <label for="report-search">Buscar reporte</label>
                <input id="report-search" name="q" type="search" value="{{ request('q') }}" maxlength="150" placeholder="Número de radicado o título">
            </div>
            <div>
                <label for="report-status">Estado del reporte</label>
                <select id="report-status" name="status">
                    <option value="">Todos los estados</option>
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ \App\Support\StatusUi::badge($status)['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <x-button type="submit">Buscar</x-button>
            <x-button :href="route('admin.dashboard').'#reportes'" variant="secondary">Limpiar filtros</x-button>
        </form>
        <div class="table-scroll">
            <table class="reports-table" role="table">
                <caption class="sr-only">Reportes ciudadanos ordenados por fecha de registro, del más reciente al más antiguo</caption>
                <thead><tr><th scope="col" style="width:40%">Reporte</th><th scope="col" style="width:20%">Estado</th><th scope="col" style="width:40%">Acciones</th></tr></thead>
                <tbody>
                    @forelse ($recent as $report)
                        <tr>
                            <td data-label="Reporte">
                                <span class="report-title">{{ $report->title }}</span>
                                <p class="report-meta">{{ $report->ticket_code }} · {{ $report->created_at->format('d/m/Y') }}</p>
                                <p class="report-meta">{{ $report->category->name ?? 'Sin categoría' }}</p>
                            </td>
                            <td data-label="Estado"><x-status-badge :status="$report->status" /></td>
                            <td data-label="Acciones">
                                <div class="row-actions">
                                    <x-button :href="route('reports.show', $report->ticket_code)" variant="secondary" aria-label="Ver detalles de {{ $report->ticket_code }}">Ver detalles</x-button>
                                    @if (in_array($report->status, [\App\Enums\ReportStatus::Reported, \App\Enums\ReportStatus::Rework], true))
                                        <x-button data-assign-open data-report-id="{{ $report->id }}"
                                                  data-action="{{ route('admin.assign', $report) }}"
                                                  data-label="{{ $report->ticket_code }} — {{ $report->title }}"
                                                  aria-label="Asignar {{ $report->ticket_code }}">Asignar</x-button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state"><strong>No hay reportes para mostrar.</strong><p>Prueba con otro título, radicado o estado.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($recent->hasPages())<div class="border-t border-border p-6">{{ $recent->fragment('reportes')->links('pagination.institutional') }}</div>@endif
    </section>

    <section class="support-grid" aria-label="Seguimiento operativo">
        <article class="panel"><h2>Inspección de calidad</h2><p><strong>{{ $stats['under_inspection'] }}</strong> reportes esperan revisión.</p><p class="report-meta mt-2">{{ $dictamenes['total'] }} inspecciones registradas · {{ $dictamenes['rework'] }} con correcciones solicitadas.</p><p class="report-meta mt-2">@if ($dictamenes['total'] > 0) {{ round(100 * ($dictamenes['total'] - $dictamenes['rework']) / $dictamenes['total']) }} % de inspecciones aprobadas. @else Aún no hay inspecciones para calcular la aprobación. @endif</p></article>
        <article class="panel"><h2>Correcciones pendientes</h2><p><strong>{{ $stats['rework'] }}</strong> reportes requieren nuevos trabajos.</p><a class="btn-secondary mt-4" href="{{ route('admin.dashboard', ['status' => 'rework']) }}#reportes">Revisar correcciones</a></article>
        <article class="panel"><h2>Órdenes por iniciar</h2><p><strong>{{ $stats['orders_pending'] }}</strong> órdenes asignadas aún no han iniciado.</p><p class="report-meta mt-2">{{ $stats['closed_today'] }} reportes cerrados hoy · {{ $stats['contractors'] }} contratistas activos.</p></article>
    </section>
    <x-assign-modal :contractors="$contractors" />
</div>
@endsection
