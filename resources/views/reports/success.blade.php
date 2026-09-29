@extends('layouts.app')

@section('title', 'Reporte radicado — Vigía Ciudadano')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    {{-- ================= TARJETA DE CONFIRMACIÓN ================= --}}
    <div class="bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-8 text-center">
        <div class="flex justify-center mb-4">
            <span class="grid size-20 place-items-center rounded-full bg-[#e8f8f0] text-4xl">✅</span>
        </div>

        <h1 class="text-2xl font-bold text-[#062e24]">¡Reporte radicado exitosamente!</h1>
        <p class="mt-2 text-sm text-[#1f5142]">
            Tu incidencia ha sido registrada en el sistema. Guarda este número de radicado para consultar el estado de la obra.
        </p>

        {{-- Ticket en grande --}}
        <div class="mt-6 rounded-2xl border-2 border-dashed border-[#087b5f] bg-[#f0faf5] p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-[#087b5f]">Número de radicado</p>
            <p id="ticket-code" class="mt-2 font-mono text-3xl font-bold text-[#062e24] select-all">
                {{ $report->ticket_code }}
            </p>
        </div>

        {{-- Botones de acción --}}
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <button type="button" id="copy-ticket"
                    class="bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-6 py-3 rounded-xl transition inline-flex items-center justify-center gap-2">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Copiar radicado
            </button>

            <a href="{{ route('reports.show', $report->ticket_code) }}"
               class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-6 py-3 rounded-xl shadow-sm transition active:scale-[0.98] inline-flex items-center justify-center gap-2">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Ver seguimiento
            </a>
        </div>
    </div>

    {{-- ================= RESUMEN DEL REPORTE ================= --}}
    <div class="bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-[#1f5142] mb-4">Resumen del reporte</h2>

        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-[#5d7d70]">Categoría</dt>
                <dd class="font-medium text-[#062e24] text-right">{{ $report->category->name }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-[#5d7d70]">Dependencia</dt>
                <dd class="font-medium text-[#062e24] text-right">{{ $report->category->department->name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-[#5d7d70]">Título</dt>
                <dd class="font-medium text-[#062e24] text-right">{{ $report->title }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-[#5d7d70]">Ubicación</dt>
                <dd class="font-mono text-xs text-[#062e24] text-right">
                    {{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }}
                </dd>
            </div>
            @if ($report->address_reference)
                <div class="flex justify-between gap-4">
                    <dt class="text-[#5d7d70]">Referencia</dt>
                    <dd class="font-medium text-[#062e24] text-right">{{ $report->address_reference }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <dt class="text-[#5d7d70]">Radicado</dt>
                <dd class="font-mono font-bold text-[#087b5f]">{{ $report->ticket_code }}</dd>
            </div>
        </dl>
    </div>

    {{-- ================= PRÓXIMOS PASOS ================= --}}
    <div class="rounded-2xl border border-[#d5e8e0] bg-[#f0faf5]/50 p-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-[#1f5142] mb-3">¿Qué sigue?</h2>
        <ol class="space-y-2 text-sm text-[#1f5142]">
            <li class="flex gap-3">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-[#e8f8f0] text-xs font-bold text-[#087b5f]">1</span>
                <span>La dependencia asignada revisará tu reporte y asignará una cuadrilla.</span>
            </li>
            <li class="flex gap-3">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-[#e8f8f0] text-xs font-bold text-[#087b5f]">2</span>
                <span>El contratista ejecutará la obra en la fecha programada.</span>
            </li>
            <li class="flex gap-3">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-[#e8f8f0] text-xs font-bold text-[#087b5f]">3</span>
                <span>Un inspector de calidad verificará el trabajo antes de cerrar la incidencia.</span>
            </li>
            <li class="flex gap-3">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-[#e8f8f0] text-xs font-bold text-[#087b5f]">4</span>
                <span>Podrás consultar el estado en cualquier momento con tu número de radicado.</span>
            </li>
        </ol>
    </div>

    {{-- ================= BOTÓN VOLVER AL INICIO ================= --}}
    <div class="text-center">
        <a href="{{ route('home') }}"
           class="text-sm font-medium text-[#087b5f] hover:text-[#05664e] underline">
            ← Volver al inicio
        </a>
    </div>
</div>

@push('scripts')
<script>
    // Copiar ticket al portapapeles
    document.getElementById('copy-ticket')?.addEventListener('click', function () {
        const ticketCode = document.getElementById('ticket-code').textContent.trim();

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(ticketCode).then(() => {
                showCopied(this);
            }).catch(() => {
                fallbackCopy(ticketCode, this);
            });
        } else {
            fallbackCopy(ticketCode, this);
        }
    });

    function fallbackCopy(text, button) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showCopied(button);
        } catch (err) {
            alert('No se pudo copiar. Por favor copia manualmente: ' + text);
        }
        document.body.removeChild(textarea);
    }

    function showCopied(button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> ¡Copiado!';
        button.disabled = true;
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }, 2000);
    }
</script>
@endpush
@endsection
