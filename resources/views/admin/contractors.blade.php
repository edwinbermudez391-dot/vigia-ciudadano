@extends('layouts.app')

@section('title', 'Gestión de Cuadrillas — Vigía Ciudadano')

@php
    // Alta fallida → el modal de registro se reabre en servidor con las
    // acciones (mismo patrón que x-assign-modal; sin dependencia de JS).
    $reopenRegister = $errors->any() && old('email') !== null;
@endphp

@section('content')
<div class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[#062e24]">Gestión de Cuadrillas</h1>
            <p class="mt-1 text-sm text-[#1f5142]">
                Contratistas habilitados para recibir despachos de obra.
            </p>
        </div>

        <button type="button" id="open-register"
                class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98]">
            ＋ Registrar Nueva Cuadrilla
        </button>
    </header>

    <section class="overflow-hidden bg-white/95 rounded-2xl border border-[#d5e8e0] shadow-[0_8px_30px_rgba(6,46,36,0.04)]">
        @if ($contractors->isEmpty())
            <p class="p-8 text-center text-sm text-[#5d7d70]">
                Aún no hay cuadrillas registradas. Usa «Registrar Nueva Cuadrilla» para habilitar la primera.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f0faf5] text-[#1f5142] text-xs font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 font-bold">Cuadrilla</th>
                            <th class="px-5 py-3 font-bold">Dependencia</th>
                            <th class="px-5 py-3 font-bold">Contacto</th>
                            <th class="px-5 py-3 font-bold">Órdenes activas</th>
                            <th class="px-5 py-3 font-bold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#d5e8e0]">
                        @foreach ($contractors as $c)
                            <tr class="transition duration-200 hover:bg-[#f0faf5]/50">
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-[#062e24]">{{ $c->name }}</p>
                                    <p class="mt-0.5 font-mono text-[11px] text-[#5d7d70]">
                                        {{ $c->email }}
                                    </p>
                                </td>
                                <td class="px-5 py-3.5 text-[#062e24]">{{ $c->department?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-[#1f5142]">
                                    {{ $c->phone ?: 'Sin teléfono' }}
                                </td>
                                <td class="px-5 py-3.5 tabular-nums text-[#062e24]">
                                    {{ $c->active_orders_count }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span @class([
                                        'rounded-full border px-2.5 py-1 text-xs font-medium',
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $c->is_active,
                                        'border-rose-200 bg-rose-50 text-rose-600' => ! $c->is_active,
                                    ])>
                                        {{ $c->is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

{{-- ==================== MODAL: REGISTRAR CUADRILLA ==================== --}}
<div id="register-modal" role="dialog" aria-modal="true" aria-labelledby="register-title"
     class="fixed inset-0 z-50 grid place-items-center p-4 {{ $reopenRegister ? '' : 'hidden' }}">
    <div class="absolute inset-0 bg-[#062e24]/40 backdrop-blur-sm" data-register_close></div>

    <form method="POST" action="{{ route('admin.contractors.store') }}"
          class="relative w-full max-w-md rounded-2xl border border-[#d5e8e0] bg-white p-6 shadow-2xl transition duration-200">
        @csrf

        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 id="register-title" class="text-base font-semibold text-[#062e24]">🧰 Registrar Nueva Cuadrilla</h3>
                <p class="mt-1 text-xs text-[#5d7d70]">
                    Queda con rol contratista y activa para recibir despachos.
                </p>
            </div>
            <button type="button" data-register_close aria-label="Cerrar"
                    class="grid size-8 shrink-0 place-items-center rounded-lg text-[#5d7d70] transition duration-200 hover:bg-[#f0faf5] hover:text-[#062e24]">
                ✕
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <div>
                <label for="rc-name" class="mb-1.5 block text-sm font-medium text-[#062e24]">Nombre de la empresa o cuadrilla</label>
                <input id="rc-name" name="name" type="text" maxlength="120" required autofocus
                       value="{{ old('name') }}" placeholder="Constructora ejemplo S.A.S."
                       class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 placeholder:text-slate-400 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                @error('name') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="rc-email" class="mb-1.5 block text-sm font-medium text-[#062e24]">Correo electrónico</label>
                    <input id="rc-email" name="email" type="email" maxlength="255" required
                           value="{{ old('email') }}" placeholder="cuadrilla@empresa.com"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 placeholder:text-slate-400 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('email') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="rc-phone" class="mb-1.5 block text-sm font-medium text-[#062e24]">
                        Teléfono <span class="text-xs font-normal text-[#5d7d70]">(opcional)</span>
                    </label>
                    <input id="rc-phone" name="phone" type="tel" maxlength="25"
                           value="{{ old('phone') }}" placeholder="300 000 0000"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 placeholder:text-slate-400 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('phone') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="rc-department" class="mb-1.5 block text-sm font-medium text-[#062e24]">Dependencia asignada</label>
                <select id="rc-department" name="department_id" required
                        class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    <option value="" disabled @selected(old('department_id') === null)>Selecciona una dependencia…</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d->id }}" @selected((int) old('department_id') === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                @error('department_id') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="rc-password" class="mb-1.5 block text-sm font-medium text-[#062e24]">Contraseña inicial</label>
                    <input id="rc-password" name="password" type="password" minlength="8" required
                           autocomplete="new-password"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('password') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="rc-password2" class="mb-1.5 block text-sm font-medium text-[#062e24]">Repetir contraseña</label>
                    <input id="rc-password2" name="password_confirmation" type="password" minlength="8" required
                           autocomplete="new-password"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition duration-200 focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" data-register_close
                        class="bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-4 py-2.5 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98]">
                    Registrar cuadrilla
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Modal de alta: abrir con el botón del header; cerrar con backdrop,
    // ✕, Cancelar o Escape. Si el POST falló, el servidor ya lo deja abierto.
    (function () {
        const modal = document.getElementById('register-modal');
        if (!modal) { return; }

        document.getElementById('open-register')?.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.querySelector('input[name="name"]')?.focus();
        });

        modal.querySelectorAll('[data-register_close]').forEach((el) => {
            el.addEventListener('click', () => modal.classList.add('hidden'));
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { modal.classList.add('hidden'); }
        });
    })();
</script>
@endpush
