@extends('layouts.app')
@section('title', 'Reportes — Vigía Ciudadano')
@section('content')
<div class="space-y-8">
    <x-page-heading title="Reportes ciudadanos" description="Consulta y gestiona los reportes agrupados por estado." />

    <section class="panel" id="reportes" aria-labelledby="reports-title">
        <div class="panel-header">
            <div>
                <h2 id="reports-title">Reportes por estado</h2>
                <p>Orden de llegada: los más antiguos primero en cada estado.</p>
            </div>
            <span class="report-meta">{{ $totalReports }} {{ $totalReports == 1 ? 'resultado' : 'resultados' }}</span>
        </div>
        <form method="GET" action="{{ route('admin.reports') }}" class="filter-form" role="search">
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
            <x-button :href="route('admin.reports')" variant="secondary">Limpiar filtros</x-button>
        </form>
        <div class="space-y-3 border-t border-border p-4 sm:p-6">
            <x-status-switcher :groups="$reportGroups" :status-enum="\App\Enums\ReportStatus::class" :open-status="$openStatus" panel-prefix="reportes" label="Estados de los reportes" />

            @forelse ($reportGroups as $state => $group)
                @php($status = \App\Enums\ReportStatus::from($state))
                <details id="reportes-{{ $state }}" name="report-status-groups"
                         class="group scroll-mt-6 overflow-hidden rounded-xl border border-border" @if ($openStatus === $state) open @endif>
                    <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 bg-slate-50 px-4 py-3 hover:bg-blue-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-600 [&::-webkit-details-marker]:hidden">
                        <x-status-badge :status="$status" id="titulo-{{ $state }}" />
                        <span class="flex shrink-0 items-center gap-3 sm:gap-4">
                            <span class="report-meta tabular-nums">{{ $group->total() }} {{ $group->total() == 1 ? 'reporte' : 'reportes' }}</span>
                            <x-icon name="arrow" class="text-blue-900 group-open:rotate-90" />
                        </span>
                    </summary>

                    <div class="table-scroll border-t border-border">
                        <table class="reports-table" role="table" aria-labelledby="titulo-{{ $state }}">
                            <thead><tr><th scope="col" class="w-[60%]">Reporte</th><th scope="col">Acciones</th></tr></thead>
                            <tbody>
                                @foreach ($group as $report)
                                    <tr>
                                        <td data-label="Reporte">
                                            <span class="report-title">{{ $report->title }}</span>
                                            <p class="report-meta">{{ $report->ticket_code }} · {{ $report->created_at->format('d/m/Y') }}</p>
                                            <p class="report-meta">{{ $report->category->name ?? 'Sin categoría' }}</p>
                                        </td>
                                        <td data-label="Acciones">
                                            <div class="row-actions">
                                                <x-button :href="route('reports.show', $report->ticket_code)" variant="secondary" aria-label="Ver detalles de {{ $report->ticket_code }}">Ver detalles</x-button>
                                                @if (in_array($status, [\App\Enums\ReportStatus::Reported, \App\Enums\ReportStatus::Rework], true))
                                                    <x-button data-assign-open data-report-id="{{ $report->id }}"
                                                              data-action="{{ route('admin.assign', $report) }}"
                                                              data-label="{{ $report->ticket_code }} — {{ $report->title }}"
                                                              aria-label="Asignar {{ $report->ticket_code }}">Asignar</x-button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($group->hasPages())
                        <div class="border-t border-border px-4 py-3">{{ $group->links('pagination.institutional') }}</div>
                    @endif
                </details>
            @empty
                <div class="empty-state"><strong>No hay reportes para mostrar.</strong><p>Prueba con otro título, radicado o estado.</p></div>
            @endforelse
        </div>
    </section>

    <x-assign-modal :contractors="$contractors" />
</div>
@endsection
