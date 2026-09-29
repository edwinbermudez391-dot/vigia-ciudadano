@extends('layouts.app')

@section('title', 'Cola de inspecciones — Vigía Ciudadano')

@section('content')
<div class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[#062e24]">Obras pendientes de dictamen</h1>
            <p class="mt-1 text-sm text-[#1f5142]">
                Compara la foto del daño contra la solución del contratista antes de firmar el acta.
            </p>
        </div>
        <span class="rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-indigo-700">
            {{ $reports->total() }} en cola
        </span>
    </header>

    @forelse ($reports as $report)
        @php
            $order = $report->workOrder;
            $storage = \Illuminate\Support\Facades\Storage::disk('public');
            $previous = $order->qualityInspections->sortByDesc('inspected_at')->first();
        @endphp

        <article class="bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-[#062e24] transition duration-200 hover:scale-[1.005]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('reports.show', $report->ticket_code) }}"
                       class="rounded-lg bg-[#e8f8f0] px-2.5 py-1.5 font-mono text-xs font-semibold text-[#087b5f] transition hover:bg-[#d5e8e0]">
                        {{ $report->ticket_code }}
                    </a>
                    <div>
                        <h2 class="text-sm font-semibold leading-tight text-[#062e24]">{{ $report->title }}</h2>
                        <p class="text-xs text-[#5d7d70]">
                            {{ $report->category->name }} · contratista {{ $order->contractor->name ?? '—' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-status-badge :status="$report->status" />
                    <a href="{{ route('inspector.audit', $order->id) }}"
                       class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98] text-sm">
                        Auditar obra →
                    </a>
                </div>
            </div>

            {{-- Mini comparativa lado a lado: daño vs solución --}}
            <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-2xl">
                <figure class="overflow-hidden rounded-xl border border-[#d5e8e0]">
                    <img src="{{ $storage->url($report->damage_photo_url) }}" alt="Daño reportado"
                     onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('div'),{textContent:'Foto no disponible',className:'grid aspect-video place-items-center bg-[#f0faf5] text-xs text-[#5d7d70]'}))"
                         loading="lazy" class="aspect-video w-full object-cover">
                    <figcaption class="bg-[#f0faf5] px-2 py-1 text-[11px] text-[#5d7d70]">
                        Daño reportado
                    </figcaption>
                </figure>
                <figure class="overflow-hidden rounded-xl border border-[#d5e8e0]">
                    @if ($order->solution_photo_url)
                        <img src="{{ $storage->url($order->solution_photo_url) }}" alt="Solución del contratista"
                             onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('div'),{textContent:'Foto no disponible',className:'grid aspect-video place-items-center bg-[#f0faf5] text-xs text-[#5d7d70]'}))"
                             loading="lazy" class="aspect-video w-full object-cover">
                    @else
                        <div class="grid aspect-video place-items-center bg-[#f0faf5] text-xs text-[#5d7d70]">Sin foto de solución</div>
                    @endif
                    <figcaption class="bg-[#f0faf5] px-2 py-1 text-[11px] text-[#5d7d70]">
                        Solución entregada
                    </figcaption>
                </figure>
            </div>

            @if ($previous)
                <p class="mt-3 flex items-center gap-2 text-xs text-amber-600">
                    ↩️ Segunda visita: la obra ya fue reprocesada {{ $order->qualityInspections->count() > 1 ? '('.$order->qualityInspections->count().' dictámenes previos)' : '' }}.
                </p>
            @endif
        </article>
    @empty
        <div class="bg-white/95 rounded-2xl border border-dashed border-[#d5e8e0] p-10 text-center text-sm text-[#5d7d70]">
            🎉 No hay obras esperando dictamen. El municipio está al día.
        </div>
    @endforelse

    {{ $reports->links() }}
</div>
@endsection
