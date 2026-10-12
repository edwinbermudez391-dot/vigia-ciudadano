@extends('layouts.app')

@section('title', 'Analítica Ejecutiva — Vigía Ciudadano')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[#062e24]">Analítica Ejecutiva</h1>
            <p class="mt-1 text-sm text-[#1f5142]">
                Métricas de desempeño y cumplimiento operativo del municipio.
            </p>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.analytics') }}" class="flex flex-wrap gap-3">
            {{-- Período --}}
            <div>
                <label for="period" class="block text-xs font-medium text-[#5d7d70] mb-1">Período</label>
                <select name="period" id="period" onchange="this.form.submit()"
                        class="rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Últimos 7 días</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Este mes</option>
                    <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Este año</option>
                </select>
            </div>

            {{-- Departamento --}}
            <div>
                <label for="department_id" class="block text-xs font-medium text-[#5d7d70] mb-1">Departamento</label>
                <select name="department_id" id="department_id" onchange="this.form.submit()"
                        class="rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    <option value="">Todos los departamentos</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </header>

    {{-- KPIs Principales --}}
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Reportes --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-3xl">📊</span>
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
                <span class="text-3xl">⏱️</span>
                <span class="text-xs font-bold text-sky-600">horas</span>
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-[#062e24]">{{ $kpis['avg_resolution_hours'] }}</p>
            <p class="mt-1 text-xs font-medium text-[#5d7d70]">Tiempo promedio resolución</p>
            <p class="mt-0.5 text-[10px] text-[#5d7d70]">desde radicación hasta cierre</p>
        </div>

        {{-- Tasa de Aprobación --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-3xl">✅</span>
                <span class="text-xs font-bold text-emerald-600">primer intento</span>
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-[#062e24]">{{ $kpis['approval_rate'] }}%</p>
            <p class="mt-1 text-xs font-medium text-[#5d7d70]">Tasa de aprobación</p>
            <p class="mt-0.5 text-[10px] text-[#5d7d70]">inspecciones aprobadas sin retrabajo</p>
        </div>

        {{-- Cumplimiento SLA --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-3xl">🎯</span>
                @if($kpis['sla_compliance'] >= 80)
                    <span class="text-xs font-bold text-emerald-600">Óptimo</span>
                @elseif($kpis['sla_compliance'] >= 60)
                    <span class="text-xs font-bold text-amber-600">Regular</span>
                @else
                    <span class="text-xs font-bold text-rose-600">Crítico</span>
                @endif
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-[#062e24]">{{ $kpis['sla_compliance'] }}%</p>
            <p class="mt-1 text-xs font-medium text-[#5d7d70]">Cumplimiento de SLA</p>
            <p class="mt-0.5 text-[10px] text-[#5d7d70]">reportes resueltos dentro del plazo</p>
        </div>
    </section>

    {{-- Gráficos --}}
    <section class="grid gap-6 lg:grid-cols-2">
        {{-- Distribución por Categoría --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Distribución por Categoría
            </h2>
            <canvas id="categoryChart" height="200"></canvas>
        </div>

        {{-- Distribución por Departamento --}}
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Distribución por Departamento
            </h2>
            <canvas id="departmentChart" height="200"></canvas>
        </div>
    </section>

    {{-- Evolución Temporal --}}
    <section class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
            Evolución Semanal: Reportes Recibidos vs Resueltos
        </h2>
        <canvas id="trendChart" height="100"></canvas>
    </section>

    {{-- Rendimiento de Contratistas --}}
    <section class="rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
            Rendimiento por Contratista
        </h2>

        @if($contractorPerformance->isEmpty())
            <p class="py-8 text-center text-sm text-[#5d7d70]">No hay contratistas registrados en el período seleccionado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#d5e8e0] text-left text-xs font-semibold uppercase tracking-wider text-[#5d7d70]">
                            <th class="pb-3">Contratista</th>
                            <th class="pb-3 text-center">Asignadas</th>
                            <th class="pb-3 text-center">Completadas</th>
                            <th class="pb-3 text-center">A Tiempo</th>
                            <th class="pb-3 text-center">Tasa Rechazo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#d5e8e0]">
                        @foreach($contractorPerformance as $contractor)
                            <tr class="hover:bg-[#f8fdfa]">
                                <td class="py-3 font-medium text-[#062e24]">{{ $contractor['name'] }}</td>
                                <td class="py-3 text-center tabular-nums">{{ $contractor['assigned'] }}</td>
                                <td class="py-3 text-center tabular-nums">{{ $contractor['completed'] }}</td>
                                <td class="py-3 text-center tabular-nums">{{ $contractor['on_time'] }}</td>
                                <td class="py-3 text-center">
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

    // Colores del tema
    const colors = {
        primary: '#087b5f',
        secondary: '#05664e',
        accent: '#0ea5e9',
        warning: '#f59e0b',
        danger: '#ef4444',
        success: '#10b981',
        palette: ['#087b5f', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ef4444', '#10b981', '#ec4899', '#6366f1', '#14b8a6', '#f97316']
    };

    // Configuración global de Chart.js
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#5d7d70';

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
