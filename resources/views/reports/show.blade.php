@extends('layouts.app')

@php
    $photo = fn (?string $path): ?string => $path === null
        ? null
        : \Illuminate\Support\Facades\Storage::disk('public')->url($path);

    $damageUrl = $photo($report->damage_photo_url);
    $solutionUrl = $photo($workOrder?->solution_photo_url);
    $verificationUrl = $photo($latestInspection?->verification_photo_url);

    // Semáforo de SLA: plazo contractual vs reloj actual.
    $slaHours = $report->category->sla_hours ?? null;
    $slaDue = $slaHours ? $report->created_at->addHours($slaHours) : null;
    $slaOverdue = $slaDue && $report->status !== \App\Enums\ReportStatus::Closed && $slaDue->isPast();

    // Panel operativo: SOLO la cuadrilla dueña de la orden lo ve (IDOR de UI;
    // la protección real de datos vive en el controller y en el FormRequest).
    $crewOrder = $workOrder !== null
        && auth()->user()?->isContractor() === true
        && $workOrder->contractor_id === auth()->id();
@endphp

@section('title', $report->ticket_code.' — Vigía Ciudadano')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    {{-- ================= CABECERA ================= --}}
    <header class="bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-[#062e24] transition-all hover:shadow-md">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-lg bg-[#e8f8f0] px-3 py-1.5 font-mono text-sm font-semibold tracking-wider text-[#087b5f]">
                {{ $report->ticket_code }}
            </span>
            <x-status-badge :status="$report->status" />
            @if ($slaDue)
                <span @class([
                    'rounded-full border px-2.5 py-1 text-xs font-medium',
                    'border-rose-200 bg-rose-50 text-rose-600' => $slaOverdue,
                    'border-[#d5e8e0] text-[#5d7d70]' => !$slaOverdue,
                ])>
                    {{ $slaOverdue ? '⏰ SLA vencido ('.$slaHours.'h)' : 'SLA '.$slaHours.'h · vence '.$slaDue->format('d M H:i') }}
                </span>
            @endif

            {{-- Despacho exclusivo de Alcaldía, solo en estados asignables
                 (reported/rework); el resto del mundo ve trazabilidad pura. --}}
            @if ($assignable)
                <button type="button" data-assign-open
                        data-report-id="{{ $report->id }}"
                        data-action="{{ route('admin.assign', $report) }}"
                        data-label="{{ $report->ticket_code }} — {{ $report->title }}"
                        class="ml-auto bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-4 py-2.5 rounded-xl transition text-sm">
                    👷 Asignar Cuadrilla
                </button>
            @endif
        </div>

        <h1 class="mt-4 text-xl font-semibold leading-snug text-[#062e24]">{{ $report->title }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-[#1f5142]">{{ $report->description }}</p>

        <dl class="mt-4 grid gap-x-6 gap-y-2 text-xs sm:grid-cols-2">
            <div class="flex gap-2">
                <dt class="text-[#5d7d70]">Categoría</dt>
                <dd class="font-medium text-[#062e24]">{{ $report->category->name }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-[#5d7d70]">Dependencia</dt>
                <dd class="font-medium text-[#062e24]">{{ $report->category->department->name ?? '—' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-[#5d7d70]">Reportado</dt>
                <dd class="font-medium text-[#062e24]">{{ $report->created_at->format('d/m/Y H:i') }} · {{ $report->user->name ?? '—anónimo' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-[#5d7d70]">Referencia</dt>
                <dd class="font-medium text-[#062e24]">{{ $report->address_reference ?: '—' }}</dd>
            </div>
        </dl>

        {{-- Punto del daño en coordenadas (sin depender de Leaflet aquí) --}}
        <p class="mt-3 font-mono text-[11px] text-[#5d7d70]">
            📍 {{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }}
        </p>
    </header>

    {{-- ================= PANEL OPERATIVO (cuadrilla dueña) =================
         Solo visible para el contratista asignado (ver $crewOrder arriba).
         La protección real (IDOR, rol, máquina de estados) vive en
         ContractorController + CompleteWorkOrderRequest + WorkOrderExecution. --}}
    @if ($crewOrder && $workOrder->status === \App\Enums\WorkOrderStatus::Assigned)
        <section class="rounded-2xl border-2 border-sky-200 bg-sky-50 p-5 text-[#062e24] shadow-sm transition duration-200">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-sky-100 text-xl">🚧</span>
                    <div>
                        <p class="text-sm font-semibold text-[#062e24]">Orden despachada a tu cuadrilla</p>
                        <p class="text-xs text-[#5d7d70]">
                            Inicia la obra cuando estés en sitio: la trazabilidad registra el arranque antes de permitir cerrar.
                        </p>
                    </div>
                </div>

                {{-- contractor.startWork es PATCH (transición de estado RESTful);
                     el form envía POST con spoofing _method. --}}
                <form method="POST" action="{{ route('contractor.startWork', $workOrder) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98] whitespace-nowrap">
                        ▶️ Iniciar Trabajo en Sitio
                    </button>
                </form>
            </div>
        </section>
    @elseif ($crewOrder && $workOrder->status === \App\Enums\WorkOrderStatus::InExecution)
        <section class="rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-5 text-[#062e24] shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-emerald-700">
                ✅ Finalizar obra — evidencia obligatoria
            </h2>
            <p class="mt-1 text-xs text-[#5d7d70]">
                Al enviar, la orden pasa a <b>completed</b> y la incidencia a inspección de calidad; el panel quedará bloqueado hasta el dictamen.
            </p>

            <form method="POST" action="{{ route('contractor.completeWork', $workOrder) }}"
                  enctype="multipart/form-data" id="complete-work-form" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="solution_photo"
                           class="block cursor-pointer overflow-hidden rounded-2xl border border-dashed border-[#b9daca] bg-white transition duration-200 hover:border-[#087b5f]">
                        <input id="solution_photo" name="solution_photo" type="file"
                               accept="image/jpeg,image/png,image/webp" capture="environment"
                               class="hidden" data-photo-input required>
                        <div class="flex flex-col items-center gap-2 p-6 text-center" data-photo-placeholder>
                            <span class="grid size-14 place-items-center rounded-2xl bg-[#e8f8f0] text-2xl">📸</span>
                            <span class="text-sm font-medium text-[#062e24]">Foto de la solución ejecutada</span>
                            <span class="text-xs text-[#5d7d70]">
                                Captura en sitio · JPEG, PNG o WebP · máx. 5 MB
                            </span>
                        </div>
                        <img data-photo-preview class="hidden aspect-video w-full object-cover" alt="Vista previa de la solución">
                    </label>
                    @error('solution_photo')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="work_description" class="mb-1 block text-sm font-medium text-[#062e24]">
                        Descripción técnica del trabajo <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="work_description" name="work_description" rows="4"
                              required minlength="20" maxlength="1500"
                              placeholder="Materiales, procedimiento y alcance de la reparación (mín. 20 caracteres)."
                              class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">{{ old('work_description') }}</textarea>
                    @error('work_description')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98] py-4 text-base sm:w-auto">
                    ✅ Enviar Obra Finalizada a Inspección
                </button>
            </form>
        </section>
    @endif

    {{-- ================= COMPARATIVA FOTOGRÁFICA (3 columnas) ================= --}}
    <section>
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
            Evidencia del ciclo completo
        </h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-photo-card label="1 · Daño" :url="$damageUrl">
                Reportado por el ciudadano<br>
                {{ $report->created_at->format('d/m/Y') }}
            </x-photo-card>

            <x-photo-card label="2 · Solución" :url="$solutionUrl">
                @if ($workOrder && $workOrder->completion_date)
                    Ejecutada por {{ $workOrder->contractor->name }}<br>
                    {{ $workOrder->completion_date->format('d/m/Y H:i') }}
                @else
                    Obra aún no ejecutada
                @endif
            </x-photo-card>

            <x-photo-card label="3 · Verificación" :url="$verificationUrl">
                @if ($latestInspection)
                    {{ $latestInspection->inspector->name ?? 'Inspector' }} ·
                    {{ $latestInspection->inspected_at->format('d/m/Y H:i') }}
                    <span @class([
                        'mt-1.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold',
                        'bg-emerald-100 text-emerald-700' => $latestInspection->decision === \App\Enums\InspectionDecision::Approved,
                        'bg-rose-100 text-rose-600' => $latestInspection->decision === \App\Enums\InspectionDecision::Rework,
                    ])>
                        {{ $latestInspection->decision === \App\Enums\InspectionDecision::Approved ? 'APROBADA' : 'REPROCESO' }}
                    </span>
                @else
                    Pendiente de dictamen
                @endif
            </x-photo-card>
        </div>

        @if ($workOrder && $workOrder->work_description)
            <div class="mt-4 bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-sm text-[#062e24]">
                <p class="text-xs font-bold uppercase tracking-wider text-[#1f5142]">Trabajo realizado</p>
                <p class="mt-1.5 leading-relaxed text-[#1f5142]">{{ $workOrder->work_description }}</p>
            </div>
        @endif
    </section>

    {{-- ================= LÍNEA DE TIEMPO ================= --}}
    <section class="bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-[#062e24] transition-all hover:shadow-md">
        <h2 class="mb-5 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
            Línea de tiempo
        </h2>

        <ol class="relative space-y-6 border-l border-[#d5e8e0] pl-6">
            @foreach ($steps as $step)
                <li class="relative">
                    {{-- nodo --}}
                    <span @class([
                        'absolute -left-[31px] top-0.5 grid size-5 place-items-center rounded-full border-2 text-[10px] font-bold',
                        'border-emerald-500 bg-emerald-500 text-white' => $step['done'],
                        'border-[#b9daca] bg-white text-[#5d7d70]' => !$step['done'],
                    ])>{{ $step['done'] ? '✓' : '·' }}</span>

                    <p @class([
                        'text-sm font-semibold',
                        'text-[#062e24]' => $step['done'],
                        'text-[#5d7d70]' => !$step['done'],
                    ])>{{ $step['title'] }}</p>

                    @if ($step['at'])
                        <p class="mt-0.5 text-xs text-[#5d7d70]">
                            {{ $step['at']->format('d/m/Y H:i') }}
                        </p>
                    @endif

                    <p class="mt-1 max-w-prose text-xs leading-relaxed text-[#1f5142]">
                        {{ $step['detail'] }}
                    </p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ================= VEDURÍA (seguimiento) ================= --}}
    <section class="flex flex-wrap items-center justify-between gap-3 bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-[#062e24]">
        <div class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-[#e8f8f0] text-lg">👁️</span>
            <div>
                <p class="text-sm font-medium text-[#062e24]">{{ $report->subscribers->count() }} vecino(s) vigilando esta obra</p>
                <p class="text-xs text-[#5d7d70]">
                    {{ $subscribed ? 'Estás suscrito: recibirás cada cambio de estado.' : 'Súmate para hacer veeduría ciudadana.' }}
                </p>
            </div>
        </div>

        @if (!$subscribed)
            <button type="button" id="follow-btn" data-report-id="{{ $report->id }}"
                    class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98]">
                Seguir incidencia
            </button>
        @else
            <span class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">
                Siguiendo ✓
            </span>
        @endif
    </section>

    {{-- Modal de despacho reutilizado: visible solo si $assignable trajo
         cuadrillas (ReportController::show); inofensivo para el resto. --}}
    @if ($assignable)
        <x-assign-modal :contractors="$contractors" />
    @endif
</div>

@endsection

@push('scripts')
<script>
    {{-- Vista previa de la foto de solución (patrón del acta del inspector) --}}
    (function () {
        const form = document.getElementById('complete-work-form');
        if (!form) { return; }

        const input = form.querySelector('[data-photo-input]');
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) { return; }
            const img = form.querySelector('[data-photo-preview]');
            img.src = URL.createObjectURL(file);
            img.classList.remove('hidden');
            form.querySelector('[data-photo-placeholder]').classList.add('hidden');
        });
    })();

    (function () {
        const btn = document.getElementById('follow-btn');
        if (!btn) { return; }

        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        btn.addEventListener('click', async () => {
            btn.disabled = true;
            btn.textContent = '…';

            try {
                const res = await fetch('/api/v1/reports/' + btn.dataset.reportId + '/subscribe', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                if (res.ok) {
                    btn.outerHTML = '<span class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">Siguiendo ✓</span>';
                } else {
                    btn.textContent = 'Error, reintenta';
                    btn.disabled = false;
                }
            } catch (e) {
                btn.textContent = 'Sin conexión';
                btn.disabled = false;
            }
        });
    })();
</script>
@endpush
