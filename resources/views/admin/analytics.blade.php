@extends('layouts.app')

@section('title', 'Analítica Ejecutiva — Vigía Ciudadano')

@section('content')
<div class="space-y-8 pt-4">
    {{-- Header --}}
    <x-page-heading title="Estadísticas del municipio" description="Resultados, tiempos de solución y plazos." />
    <section class="panel" aria-label="Filtros de estadísticas">
        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.analytics') }}" class="filter-form">
            {{-- Período --}}
            <div>
                <label for="period" class="block text-xs font-medium text-[#5d7d70] mb-1">Período</label>
                <select name="period" id="period"
                        class="rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Esta semana</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Este mes</option>
                    <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Este año</option>
                </select>
            </div>

            {{-- Departamento --}}
            <div>
                <label for="department_id" class="block text-xs font-medium text-[#5d7d70] mb-1">Departamento</label>
                <select name="department_id" id="department_id"
                        class="rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    <option value="">Todos los departamentos</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-button type="submit" class="self-end">Aplicar filtros</x-button>
        </form>
    </section>

    {{-- KPIs Principales --}}
    <section class="analytics-stats stats-grid" aria-label="Indicadores del período seleccionado">
        {{-- Total Reportes --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <x-icon name="report" />
                @if($kpis['variation'] > 0)
                    <span class="text-xs font-bold text-emerald-600">+{{ $kpis['variation'] }}%</span>
                @elseif($kpis['variation'] < 0)
                    <span class="text-xs font-bold text-rose-600">{{ $kpis['variation'] }}%</span>
                @else
                    <span class="text-xs font-bold text-slate-500">0%</span>
                @endif
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-[#062e24]">{{ number_format($kpis['total_reports']) }}</p>
            <p class="mt-1 text-xs font-medium text-[#5d7d70]">Reportes radicados</p>
            <p class="mt-0.5 text-[10px] text-[#5d7d70]">vs {{ number_format($kpis['previous_reports']) }} período anterior</p>
        </div>

        {{-- Tiempo Promedio Resolución --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <x-icon name="clock" />
                <span class="text-xs font-bold text-sky-600">horas</span>
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-[#062e24]">{{ $kpis['avg_resolution_hours'] }}</p>
            <p class="mt-1 text-xs font-medium text-[#5d7d70]">Tiempo promedio resolución</p>
            <p class="mt-0.5 text-[10px] text-[#5d7d70]">desde radicación hasta cierre</p>
        </div>

        {{-- Tasa de Aprobación --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <x-icon name="check" />
                <span class="text-xs font-bold text-emerald-600">inspecciones</span>
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-[#062e24]">{{ $kpis['approval_rate'] }}%</p>
            <p class="mt-1 text-xs font-medium text-[#5d7d70]">Tasa de aprobación</p>
            <p class="mt-0.5 text-[10px] text-[#5d7d70]">proporción de inspecciones aprobadas</p>
        </div>

        {{-- Cumplimiento SLA --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <x-icon name="shield" />
                @if($kpis['sla_compliance'] >= 80)
                    <span class="text-xs font-bold text-emerald-600">Óptimo</span>
                @elseif($kpis['sla_compliance'] >= 60)
                    <span class="text-xs font-bold text-amber-600">Regular</span>
                @else
                    <span class="text-xs font-bold text-rose-600">Crítico</span>
                @endif
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-[#062e24]">{{ $kpis['sla_compliance'] }}%</p>
            <p class="mt-1 text-xs font-medium text-[#5d7d70]">Atención dentro del plazo</p>
            <p class="mt-0.5 text-[10px] text-[#5d7d70]">reportes resueltos dentro del plazo</p>
        </div>
    </section>

    {{-- Gráficos --}}
    <section class="charts-grid">
        {{-- Distribución por Categoría --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Por categoría
            </h2>
            <div class="chart-frame">
                <canvas id="categoryChart" role="img" aria-label="Reportes por categoría. Los valores están disponibles en la tabla de datos siguiente."></canvas>
            </div>
        </div>

        {{-- Distribución por Departamento --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Por dependencia
            </h2>
            <div class="chart-frame">
                <canvas id="departmentChart" role="img" aria-label="Reportes por dependencia. Los valores están disponibles en la tabla de datos siguiente."></canvas>
            </div>
        </div>
    </section>

    {{-- Evolución Temporal --}}
    <section class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
            Recibidos y resueltos
        </h2>
        <div class="chart-frame">
            <canvas id="trendChart" role="img" aria-label="Evolución semanal de reportes recibidos y resueltos. Consulta también los datos en tabla."></canvas>
        </div>
    </section>

    <details class="panel p-6">
        <summary class="font-semibold">Consultar los datos de las gráficas en tablas</summary>
        <p class="report-meta mt-3">Disponibles incluso si las gráficas no cargan. La evolución muestra las últimas ocho semanas.</p>
        <div class="grid gap-6 mt-6 lg:grid-cols-2">
            @foreach (['by_category' => 'Reportes por categoría', 'by_department' => 'Reportes por dependencia'] as $key => $label)
                <div class="table-scroll"><table class="w-full"><caption>{{ $label }}</caption>
                    <thead><tr><th scope="col">Nombre</th><th scope="col">Reportes</th></tr></thead>
                    <tbody>@forelse ($charts[$key]['labels'] as $i => $name)<tr><th scope="row">{{ $name }}</th><td>{{ $charts[$key]['data'][$i] }}</td></tr>@empty<tr><td colspan="2">No hay reportes en el período seleccionado.</td></tr>@endforelse</tbody>
                </table></div>
            @endforeach
        </div>
        <div class="table-scroll mt-6"><table class="w-full"><caption>Evolución semanal</caption>
            <thead><tr><th scope="col">Semana</th><th scope="col">Recibidos</th><th scope="col">Resueltos</th></tr></thead>
            <tbody>@foreach ($charts['weekly_trend']['labels'] as $i => $week)<tr><th scope="row">{{ $week }}</th><td>{{ $charts['weekly_trend']['received'][$i] }}</td><td>{{ $charts['weekly_trend']['resolved'][$i] }}</td></tr>@endforeach</tbody>
        </table></div>
    </details>

    {{-- Rendimiento de Contratistas --}}
    <section class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
            Rendimiento por Contratista
        </h2>

        @if($contractorPerformance->isEmpty())
            <p class="py-8 text-center text-sm text-[#5d7d70]">No hay contratistas registrados en el período seleccionado.</p>
        @else
            <div class="table-scroll">
                <table class="reports-table w-full text-base" role="table">
                    <thead>
                        <tr class="border-b border-[#d5e8e0] text-left text-xs font-semibold uppercase tracking-wider text-[#5d7d70]">
                            <th scope="col" class="pb-3">Contratista</th>
                            <th scope="col" class="pb-3 text-center">Asignadas</th>
                            <th scope="col" class="pb-3 text-center">Completadas</th>
                            <th scope="col" class="pb-3 text-center">A tiempo</th>
                            <th scope="col" class="pb-3 text-center">Rechazadas (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#d5e8e0]">
                        @foreach($contractorPerformance as $contractor)
                            <tr class="hover:bg-[#f8fdfa]">
                                <td data-label="Contratista" class="py-3 font-medium text-[#062e24]">{{ $contractor['name'] }}</td>
                                <td data-label="Asignadas" class="py-3 tabular-nums">{{ $contractor['assigned'] }}</td>
                                <td data-label="Completadas" class="py-3 tabular-nums">{{ $contractor['completed'] }}</td>
                                <td data-label="A tiempo" class="py-3 tabular-nums">{{ $contractor['on_time'] }}</td>
                                <td data-label="Rechazadas (%)" class="py-3">
                                    @if($contractor['rejection_rate'] <= 10)
                                        <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">
                                            {{ $contractor['rejection_rate'] }}%
                                        </span>
                                    @elseif($contractor['rejection_rate'] <= 25)
                                        <span class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">
                                            {{ $contractor['rejection_rate'] }}%
                                        </span>
                                    @else
                                        <span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">
                                            {{ $contractor['rejection_rate'] }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    'use strict';

    if (typeof Chart === 'undefined') return; // Las tablas siguen disponibles sin CDN.
    // Colores del tema
    const colors = {
        primary: '#173d68',
        secondary: '#225e9c',
        accent: '#0ea5e9',
        warning: '#f59e0b',
        danger: '#ef4444',
        success: '#10b981',
        palette: ['#087b5f', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ef4444', '#10b981', '#ec4899', '#6366f1', '#14b8a6', '#f97316']
    };

    // Configuración global de Chart.js
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.size = 16;
    Chart.defaults.color = '#4c6075';
    Chart.defaults.animation = false;

    // Gráfico de Categorías (Dona)
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: @json($charts['by_category']['labels']),
                datasets: [{
                    data: @json($charts['by_category']['data']),
                    backgroundColor: colors.palette,
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#062e24',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { weight: 'bold' }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // Gráfico de Departamentos (Barras)
    const departmentCtx = document.getElementById('departmentChart');
    if (departmentCtx) {
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: @json($charts['by_department']['labels']),
                datasets: [{
                    label: 'Reportes',
                    data: @json($charts['by_department']['data']),
                    backgroundColor: colors.palette.slice(0, {{ count($charts['by_department']['labels']) }}),
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#062e24',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#e2e8f0' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Gráfico de Tendencia (Línea)
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($charts['weekly_trend']['labels']),
                datasets: [
                    {
                        label: 'Recibidos',
                        data: @json($charts['weekly_trend']['received']),
                        borderColor: colors.primary,
                        backgroundColor: colors.primary + '20',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Resueltos',
                        data: @json($charts['weekly_trend']['resolved']),
                        borderColor: colors.accent,
                        backgroundColor: colors.accent + '20',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: '#062e24',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#e2e8f0' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
})();
</script>
@endpush
