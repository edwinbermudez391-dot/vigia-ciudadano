@props(['contractors'])

@php
    // Si el POST volvió con errores de validación, el modal se reabre en
    // servidor (no depende de JS) con las acciones e valores conservados.
    $reopen = $errors->any() && old('report_id') !== null;
    $today = now()->toDateString();
@endphp

<div id="assign-modal" role="dialog" aria-modal="true" aria-labelledby="assign-title"
     class="fixed inset-0 z-50 grid place-items-center p-4 {{ $reopen ? '' : 'hidden' }}">
    {{-- Fondo: clic fuera cierra (data-assign_close) --}}
    <div class="absolute inset-0 bg-[#062e24]/40 backdrop-blur-sm" data-assign_close></div>

    <form id="assign-form" method="POST"
          action="{{ $reopen ? route('admin.assign', ['report' => old('report_id')]) : '' }}"
          class="relative w-full max-w-md rounded-2xl border border-[#d5e8e0] bg-white p-6 text-[#062e24] shadow-2xl transition duration-200">
        @csrf
        <input type="hidden" name="report_id" value="{{ old('report_id') }}">

        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 id="assign-title" class="text-base font-semibold">👷 Asignar Cuadrilla</h3>
                <p id="assign-label" class="mt-1 font-mono text-xs text-[#5d7d70]">{{ old('report_label') }}</p>
            </div>
            <button type="button" data-assign_close aria-label="Cerrar"
                    class="grid size-8 shrink-0 place-items-center rounded-lg text-[#5d7d70] transition duration-200 hover:bg-[#f0faf5] hover:text-[#062e24]">
                ✕
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <div>
                <label for="assign-contractor" class="mb-1.5 block text-sm font-medium">Cuadrilla / contratista</label>
                <select id="assign-contractor" name="contractor_id" required
                        class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    <option value="" disabled @selected(old('contractor_id') === null)>Selecciona una cuadrilla…</option>
                    @foreach ($contractors as $c)
                        <option value="{{ $c->id }}" @selected((int) old('contractor_id') === $c->id)>
                            {{ $c->name }} — {{ $c->department?->name ?? 'Sin dependencia' }}
                        </option>
                    @endforeach
                </select>
                @error('contractor_id') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="assign-date" class="mb-1.5 block text-sm font-medium">Fecha programada</label>
                <input id="assign-date" name="scheduled_date" type="date" min="{{ $today }}"
                       value="{{ old('scheduled_date', $today) }}" required
                       class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                @error('scheduled_date') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="assign-instructions" class="mb-1.5 block text-sm font-medium">
                    Instrucciones u observaciones <span class="text-xs font-normal text-[#5d7d70]">(opcional)</span>
                </label>
                <textarea id="assign-instructions" name="instructions" rows="3" maxlength="500"
                          placeholder="Alcance de la intervención, accesos, riesgos, horarios…"
                          class="w-full resize-none rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 placeholder:text-slate-400 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">{{ old('instructions') }}</textarea>
                @error('instructions') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- Solo para reabrir el modal en servidor tras validación fallida --}}
            <input type="hidden" name="report_label" value="{{ old('report_label') }}">

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" data-assign_close
                        class="bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-4 py-2.5 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98]">
                    Despachar obra
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
