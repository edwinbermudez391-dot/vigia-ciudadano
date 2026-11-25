@props(['contractors'])

@php
    // Si el POST volvió con errores de validación, el modal se reabre en
    // servidor (no depende de JS) con las acciones e valores conservados.
    $reopen = $errors->any() && old('report_id') !== null;
    $today = now()->toDateString();
@endphp

<div id="assign-modal" role="dialog" aria-modal="true" aria-labelledby="assign-title" aria-describedby="assign-label assign-help"
     class="fixed inset-0 z-50 grid place-items-center p-4 {{ $reopen ? '' : 'hidden' }}">
    {{-- Fondo: clic fuera cierra (data-assign_close) --}}
    <div class="absolute inset-0 bg-slate-950/45" data-assign_close></div>

    <form id="assign-form" method="POST" data-confirm="¿Confirmas la asignación de este reporte al contratista y la fecha seleccionados?"
          action="{{ $reopen ? route('admin.assign', ['report' => old('report_id')]) : '' }}"
          class="relative w-full max-w-2xl rounded-2xl border border-border bg-white p-6 sm:p-8 text-text-primary shadow-xl">
        @csrf
        <input type="hidden" name="report_id" value="{{ old('report_id') }}">

        <div class="dialog-heading">
            <div>
                <h2 id="assign-title" class="text-2xl font-semibold">Asignar contratista</h2>
                <p id="assign-label" class="mt-1 font-mono text-xs text-[#5d7d70]">{{ old('report_label') }}</p>
            </div>
            <button type="button" data-assign_close aria-label="Cerrar"
                    class="btn-secondary dialog-close">
                ✕
            </button>
        </div>

        <p id="assign-help" class="field-help mt-4">Selecciona la cuadrilla responsable y la fecha de atención. Revisa los datos antes de confirmar.</p>
        <div class="mt-6 space-y-6">
            <div>
                <label for="assign-contractor" class="field-label">Contratista responsable *</label>
                <select id="assign-contractor" name="contractor_id" required aria-invalid="{{ $errors->has('contractor_id') ? 'true' : 'false' }}"
                        @if ($errors->has('contractor_id')) aria-describedby="assign-contractor-error" @endif
                        class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    <option value="" disabled @selected(old('contractor_id') === null)>Selecciona una cuadrilla…</option>
                    @foreach ($contractors as $c)
                        <option value="{{ $c->id }}" @selected((int) old('contractor_id') === $c->id)>
                            {{ $c->name }} — {{ $c->department?->name ?? 'Sin dependencia' }}
                        </option>
                    @endforeach
                </select>
                <x-field-error field="contractor_id" id="assign-contractor-error" />
            </div>

            <div>
                <label for="assign-date" class="field-label">Fecha programada *</label>
                <input id="assign-date" name="scheduled_date" type="date" min="{{ $today }}" aria-invalid="{{ $errors->has('scheduled_date') ? 'true' : 'false' }}"
                       @if ($errors->has('scheduled_date')) aria-describedby="assign-date-error" @endif
                       value="{{ old('scheduled_date', $today) }}" required
                       class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                <x-field-error field="scheduled_date" id="assign-date-error" />
            </div>

            <div>
                <label for="assign-instructions" class="field-label">
                    Instrucciones u observaciones <span class="text-xs font-normal text-[#5d7d70]">(opcional)</span>
                </label>
                <textarea id="assign-instructions" name="instructions" rows="3" maxlength="500"
                          placeholder="Alcance de la intervención, accesos, riesgos, horarios…"
                          class="w-full resize-none rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 placeholder:text-slate-400 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">{{ old('instructions') }}</textarea>
                <x-field-error field="instructions" />
            </div>

            {{-- Solo para reabrir el modal en servidor tras validación fallida --}}
            <input type="hidden" name="report_label" value="{{ old('report_label') }}">

            <div class="form-actions">
                <button type="button" data-assign_close
                        class="btn-secondary">
                    Cancelar
                </button>
                <button type="submit"
                        class="btn-primary">
                    Confirmar asignación
                </button>
            </div>
        </div>
    </form>
</div>

@once
    @push('scripts')
    <script>
        // Modal de despacho: cada disparador [data-assign-open] fija la acción
        // del form (por reporte) antes de mostrarlo. Sin JS, el servidor ya
        // deja el modal abierto si la validación falló (ver $reopen arriba).
        (function () {
            const modal = document.getElementById('assign-modal');
            if (!modal) { return; }

            const form = document.getElementById('assign-form');
            const label = document.getElementById('assign-label');
            const reportField = form.querySelector('input[name="report_id"]');

            document.querySelectorAll('[data-assign-open]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;
                    reportField.value = btn.dataset.reportId ?? '';
                    label.textContent = btn.dataset.label ?? '';
                    modal.classList.remove('hidden');
                    form.contractor_id.focus();
                });
            });

            modal.querySelectorAll('[data-assign_close]').forEach((el) => {
                el.addEventListener('click', () => modal.classList.add('hidden'));
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') { modal.classList.add('hidden'); }
            });
        })();
    </script>
    @endpush
@endonce
