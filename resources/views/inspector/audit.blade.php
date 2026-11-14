@extends('layouts.app')

@php
    $storage = \Illuminate\Support\Facades\Storage::disk('public');
    $items = collect(old('checklist_results', []));
    $hasRework = $items->contains(fn ($v) => $v === 'no_cumple');
@endphp

@section('title', 'Auditoría ' . $report->ticket_code . ' — Vigía Ciudadano')

@section('content')
<div class="audit-page space-y-6">
    <x-page-heading title="Revisar obra" description="Compara la evidencia, completa la lista de verificación y registra tu decisión.">
        <x-button :href="route('inspector.queue')" variant="secondary">Volver a inspecciones</x-button>
    </x-page-heading>

    <div class="audit-workspace">
    <form method="POST" action="{{ route('inspector.audit.store', $order->id) }}"
          enctype="multipart/form-data" id="audit-form" class="audit-form">
        @csrf
    {{-- Cabeza con ticket + comparativa para cotejar en campo --}}
    <header class="form-section">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-col items-start gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <a href="{{ route('reports.show', $report->ticket_code) }}"
                   class="rounded-lg bg-[#e8f8f0] px-2.5 py-1.5 font-mono text-xs font-semibold text-[#087b5f] transition hover:bg-[#d5e8e0]">
                    {{ $report->ticket_code }}
                </a>
                <h2 class="text-base font-semibold text-[#062e24]">{{ $report->title }}</h2>
            </div>
            <x-status-badge :status="$report->status" />
        </div>
        <p class="mt-2 text-xs text-[#5d7d70]">
            {{ $report->category->name }} · ejecutó {{ $order->contractor->name ?? 'contratista' }}
            @if ($order->completion_date) · terminada el {{ $order->completion_date->format('d/m/Y H:i') }} @endif
        </p>

        <div class="inspection-evidence mt-6">
            <x-photo-card label="Daño reportado" :url="$report->damage_photo_url ? $storage->url($report->damage_photo_url) : null">Compara el estado inicial con la reparación.</x-photo-card>
            <x-photo-card label="Solución entregada" :url="$order->solution_photo_url ? $storage->url($order->solution_photo_url) : null">Evidencia registrada por el contratista.</x-photo-card>
        </div>

        @if ($order->work_description)
            <blockquote class="mt-3 rounded-xl bg-[#f0faf5] px-3 py-2 text-xs italic text-[#1f5142]">
                "{{ $order->work_description }}" — Acta del contratista
            </blockquote>
        @endif
    </header>

    {{-- =================== FORMULARIO DE AUDITORÍA =================== --}}

        {{-- Checklist táctil: botones grandes (dedo en campo, con guantes) --}}
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Lista de verificación técnica
            </h2>

            @foreach ($checklist as $itemKey => $itemLabel)
                <div class="checklist-item flex flex-col gap-2 bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-[#062e24] transition duration-200 sm:flex-row sm:items-center sm:justify-between"
                     data-key="{{ $itemKey }}" role="group" aria-labelledby="check-label-{{ $itemKey }}">

                    <p id="check-label-{{ $itemKey }}" class="text-base font-medium leading-relaxed">{{ $itemLabel }}</p>

                    <input type="hidden" name="checklist_results[{{ $itemKey }}]" value="{{ $items->get($itemKey) }}" data-checklist-value>

                    <div class="checklist-options">
                        <button type="button" data-answer="cumple" aria-pressed="{{ $items->get($itemKey) === 'cumple' ? 'true' : 'false' }}"
                                @class([
                                    'rounded-xl border px-4 py-4 text-sm font-bold uppercase tracking-wide transition duration-200 active:scale-[0.98]',
                                    'border-emerald-500 bg-emerald-500 text-white shadow-lg shadow-emerald-500/25' => $items->get($itemKey) === 'cumple',
                                    'border-[#b9daca] bg-[#fcfdfd] text-[#5d7d70] hover:border-emerald-300 hover:text-emerald-600' => $items->get($itemKey) !== 'cumple',
                                ])>
                            Cumple
                        </button>
                        <button type="button" data-answer="no_cumple" aria-pressed="{{ $items->get($itemKey) === 'no_cumple' ? 'true' : 'false' }}"
                                @class([
                                    'rounded-xl border px-4 py-4 text-sm font-bold uppercase tracking-wide transition duration-200 active:scale-[0.98]',
                                    'border-rose-500 bg-rose-500 text-white shadow-lg shadow-rose-500/25' => $items->get($itemKey) === 'no_cumple',
                                    'border-[#b9daca] bg-[#fcfdfd] text-[#5d7d70] hover:border-rose-300 hover:text-rose-600' => $items->get($itemKey) !== 'no_cumple',
                                ])>
                            No cumple
                        </button>
                    </div>
                </div>
            @endforeach
        </section>

        {{-- Bloque condicional de NO CUMPLE: justificación + foto de no conformidad.
             Arranca oculto salvo que el old() traiga no_cumple (revalidación). --}}
        <section id="rework-panel" @class([
                'space-y-4 rounded-2xl border-2 border-rose-200 bg-rose-50 p-4 text-[#062e24] transition duration-200',
                'hidden' => !$hasRework,
            ])>
            <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-rose-600">
                ⚠️ Hay ítems que NO CUMPLEN — evidencia obligatoria
            </h2>

            <div>
                <label for="rejection_reason" class="mb-1 block text-sm font-medium text-[#062e24]">
                    Justificación de la no conformidad <span class="text-rose-500">*</span>
                </label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" maxlength="1000"
                          @required($hasRework)
                          minlength="20"
                          placeholder="Describe qué fallo se observó en campo (mín. 20 caracteres)."
                          class="w-full rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30">{{ old('rejection_reason') }}</textarea>
            </div>

            <p class="text-xs text-rose-600/80">
                La <b>foto de verificación</b> que adjuntes abajo servirá como evidencia de la no conformidad en el acta de reproceso.
            </p>
        </section>

        {{-- Foto de verificación del inspector --}}
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Foto de verificación <span class="text-rose-500">*</span>
            </h2>

            <label for="verification_photo"
                   class="block cursor-pointer overflow-hidden rounded-2xl border border-dashed border-[#b9daca] bg-white text-[#062e24] transition duration-200 hover:border-[#087b5f]">
                <input id="verification_photo" name="verification_photo" type="file"
                       accept="image/*" capture="environment" class="hidden" data-photo-input required>
                <div class="flex flex-col items-center gap-2 p-6 text-center" data-photo-placeholder>
                    <span class="grid size-14 place-items-center rounded-2xl bg-[#e8f8f0] text-2xl">📸</span>
                    <span class="text-sm font-medium text-[#062e24]">Foto actual del estado de la obra</span>
                    <span class="text-xs text-[#5d7d70]">JPEG, PNG o WebP · máx. 5 MB</span>
                </div>
                <img data-photo-preview class="hidden aspect-video w-full object-cover" alt="Vista previa">
            </label>
        </section>

        {{-- Geolocalización del acta --}}
        <section class="audit-location bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-[#062e24]">
            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium text-[#062e24]">Posición GPS del inspector <span class="text-rose-500">*</span></p>
                    <p class="text-xs text-[#5d7d70]" data-gps-status>
                        Captura tu ubicación desde el lugar de la obra. Si el GPS no responde, escribe las coordenadas.
                    </p>
                </div>
                <button type="button" data-capture-gps
                        class="bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-4 py-2.5 rounded-xl transition text-xs">
                    📡 Capturar GPS
                </button>
            </div>
            <div class="mt-3 field-grid">
                <div>
                    <label for="inspector-latitude" class="mb-1 block text-[11px] text-[#5d7d70]">Latitud</label>
                    <input id="inspector-latitude" name="inspector_latitude" type="number" step="0.0000001" min="-90" max="90" required
                           value="{{ old('inspector_latitude') }}" data-lat
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 font-mono text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                </div>
                <div>
                    <label for="inspector-longitude" class="mb-1 block text-[11px] text-[#5d7d70]">Longitud</label>
                    <input id="inspector-longitude" name="inspector_longitude" type="number" step="0.0000001" min="-180" max="180" required
                           value="{{ old('inspector_longitude') }}" data-lng
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 font-mono text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                </div>
            </div>
        </section>

        {{-- Dictamen final --}}
        <section class="audit-decision bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)] p-6 text-[#062e24]">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-[#1f5142]">
                Emitir dictamen
            </h2>

            <input type="hidden" name="decision" value="{{ old('decision') }}" data-decision>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <button type="button" data-decide="approved" aria-describedby="approve-help" class="btn-primary w-full">Aprobar obra</button>
                    <p id="approve-help" class="field-help">Obra conforme: cierra el reporte.</p>
                </div>
                <div>
                    <button type="button" data-decide="rework" aria-describedby="rework-help" class="btn w-full bg-rose-700 text-white hover:bg-rose-800">Solicitar correcciones</button>
                    <p id="rework-help" class="field-help">Devuelve la orden al contratista.</p>
                </div>
            </div>

            <p id="form-error" role="alert" tabindex="-1" class="mt-3 hidden rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600"></p>

            @if ($errors->any())
                <ul class="mt-3 space-y-1 text-xs text-rose-600">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('audit-form');
        const decisionInput = form.querySelector('[data-decision]');
        const reworkPanel = document.getElementById('rework-panel');
        const rejectField = document.getElementById('rejection_reason');
        const errorBox = document.getElementById('form-error');

        // ---------- checklist ----------
        form.querySelectorAll('.checklist-item').forEach((item) => {
            const value = item.querySelector('[data-checklist-value]');

            item.querySelectorAll('[data-answer]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const answer = btn.dataset.answer;
                    value.value = answer;

                    item.querySelectorAll('[data-answer]').forEach((b) => {
                        const active = b === btn;
                        const good = b.dataset.answer === 'cumple';
                        b.className = buttonClass(answer === b.dataset.answer, active, good);
                        b.setAttribute('aria-pressed', String(active));
                    });

                    syncReworkPanel();
                    hideError();
                });
            });
        });

        function buttonClass(selected, active, good) {
            const base = 'rounded-xl border px-4 py-4 text-sm font-bold uppercase tracking-wide transition duration-200 active:scale-[0.98] ';
            if (!active) {
                const hover = good
                    ? 'hover:border-emerald-300 hover:text-emerald-600'
                    : 'hover:border-rose-300 hover:text-rose-600';
                return base + 'border-[#b9daca] bg-[#fcfdfd] text-[#5d7d70] ' + hover
                    + '';
            }
            return good
                ? base + 'border-emerald-500 bg-emerald-500 text-white shadow-lg shadow-emerald-500/25'
                : base + 'border-rose-500 bg-rose-500 text-white shadow-lg shadow-rose-500/25';
        }

        function anyRework() {
            return Array.from(form.querySelectorAll('[data-checklist-value]'))
                .some((i) => i.value === 'no_cumple');
        }

        // La justificación aparece SOLO con un NO CUMPLE y entonces es obligatoria
        // (regla espejada en StoreQualityInspectionRequest con required_if).
        function syncReworkPanel() {
            const show = anyRework();
            reworkPanel.classList.toggle('hidden', !show);
            rejectField.required = show;
        }

        // ---------- GPS ----------
        form.querySelector('[data-capture-gps]').addEventListener('click', () => {
            const status = form.querySelector('[data-gps-status]');
            if (!('geolocation' in navigator)) {
                status.textContent = 'Tu navegador no soporta geolocalización.';
                return;
            }
            status.textContent = 'Buscando señal GPS…';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    form.querySelector('[data-lat]').value = pos.coords.latitude.toFixed(7);
                    form.querySelector('[data-lng]').value = pos.coords.longitude.toFixed(7);
                    status.textContent = '✓ Posición capturada con precisión de ' + Math.round(pos.coords.accuracy) + ' m.';
                },
                (err) => { status.textContent = 'GPS no disponible: ' + err.message + '. Captúralo de nuevo o escribe las coordenadas.'; },
                { enableHighAccuracy: true, timeout: 12000 }
            );
        });

        // ---------- dictamen ----------
        form.querySelectorAll('[data-decide]').forEach((btn) => {
            btn.addEventListener('click', () => {
                decisionInput.value = btn.dataset.decide;

                if (!validate()) { return; }

                const labels = {
                    approved: 'Aprobar la obra y CERRAR la incidencia',
                    rework: 'Rechazar la obra y enviar a REPROCESO',
                };
                if (confirm('¿Confirmas el dictamen: ' + labels[btn.dataset.decide] + '?')) {
                    form.requestSubmit();
                }
            });
        });

        function validate() {
            const unanswered = Array.from(form.querySelectorAll('[data-checklist-value]'))
                .filter((i) => !i.value).length;

            if (unanswered > 0) {
                return showError('Falta responder ' + unanswered + ' ítem(s) del checklist.');
            }
            if (decisionInput.value === 'rework') {
                if (!anyRework()) {
                    return showError('Para reprocesar debes marcar al menos un ítem como NO CUMPLE.');
                }
                if (rejectField.value.trim().length < 20) {
                    return showError('La justificación de la no conformidad debe tener al menos 20 caracteres.');
                }
            }
            if (!form.verification_photo.files.length) {
                return showError('Adjunta la foto de verificación de la obra.');
            }
            const lat = form.querySelector('[data-lat]').value;
            const lng = form.querySelector('[data-lng]').value;
            if (!lat || !lng) {
                return showError('Captura tu posición GPS: el acta debe firmarse en el punto de la obra.');
            }
            hideError();
            return true;
        }

        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.classList.remove('hidden');
            errorBox.focus();
            return false;
        }
        function hideError() { errorBox.classList.add('hidden'); }

        // ---------- foto preview ----------
        const photoInput = form.querySelector('[data-photo-input]');
        photoInput.addEventListener('change', () => {
            const file = photoInput.files[0];
            if (!file) { return; }
            const img = form.querySelector('[data-photo-preview]');
            img.src = URL.createObjectURL(file);
            img.classList.remove('hidden');
            form.querySelector('[data-photo-placeholder]').classList.add('hidden');
        });

        syncReworkPanel();
    })();
</script>
@endpush
