@extends('layouts.app')

@php $storage = \Illuminate\Support\Facades\Storage::disk('public'); @endphp

@section('title', 'Mis órdenes de trabajo — Vigía Ciudadano')

@section('content')
<div class="space-y-6">
    <header>
        <h1 class="text-2xl font-semibold tracking-tight text-[#062e24]">Mis órdenes de trabajo</h1>
        <p class="mt-1 text-sm text-[#1f5142]">
            Tu agenda. Ejecuta y sube la evidencia desde la app de campo (API <code class="font-mono text-xs text-[#087b5f]">/work-orders</code>).
        </p>
    </header>

    @forelse ($orders as $order)
        <article @class([
            'rounded-2xl border p-4 shadow-[0_8px_30px_rgba(6,46,36,0.04)] text-[#062e24] transition duration-200 hover:scale-[1.005]',
            'border-sky-200 bg-sky-50' => $order->status === \App\Enums\WorkOrderStatus::InExecution,
            'bg-white/95 border-[#d5e8e0]' => $order->status !== \App\Enums\WorkOrderStatus::InExecution,
        ])>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($order->solution_photo_url)
                        <img src="{{ $storage->url($order->solution_photo_url) }}" alt="Solución"
                             class="size-14 shrink-0 rounded-xl border border-[#d5e8e0] object-cover">
                    @else
                        <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-[#f0faf5] text-xl">🧱</span>
                    @endif
                    <div class="min-w-0">
                        <a href="{{ route('reports.show', $order->report->ticket_code) }}"
                           class="text-sm font-semibold text-[#062e24] hover:text-[#087b5f]">
                            {{ $order->report->title }}
                        </a>
                        <p class="mt-0.5 text-xs text-[#5d7d70]">
                            {{ $order->report->category->name ?? '—' }} · programada
                            {{ $order->scheduled_date->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <x-status-badge :status="$order->status" />
            </div>
        </article>
    @empty
        <div class="bg-white/95 rounded-2xl border border-dashed border-[#d5e8e0] p-10 text-center text-sm text-[#5d7d70]">
            Aún no tienes órdenes asignadas.
        </div>
    @endforelse

    {{ $orders->links() }}
</div>
@endsection
