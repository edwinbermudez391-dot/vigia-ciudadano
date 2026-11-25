@extends('layouts.app')
@section('title', 'Contratistas — Vigía Ciudadano')
@php $reopenRegister = $errors->any() && old('email') !== null; @endphp
@section('content')
<div class="space-y-8">
    <x-page-heading title="Contratistas" description="Cuadrillas, contacto y órdenes activas.">
        <x-button id="open-register"><x-icon name="plus" />Registrar contratista</x-button>
    </x-page-heading>
    @if ($contractors->isNotEmpty())
        <x-list-search target="contractor-list" label="Buscar contratista" placeholder="Nombre, correo o dependencia" />
    @endif
    <section class="panel" id="contractor-list" aria-label="Listado de contratistas">
        <div class="panel-header"><div><h2>Cuadrillas registradas</h2><p>Las cuadrillas activas pueden recibir nuevas asignaciones.</p></div><span class="report-meta">{{ $contractors->count() }} contratista(s)</span></div>
        @if ($contractors->isEmpty())
            <div class="empty-state"><x-icon name="users" /><h2>Aún no hay contratistas</h2><p>Selecciona «Registrar contratista» para habilitar la primera cuadrilla.</p></div>
        @else
            <div class="table-scroll"><table class="reports-table contractors-table" role="table">
                <caption class="sr-only">Contratistas, estado, dependencia, contacto, órdenes activas y acciones</caption>
                <thead><tr><th scope="col">Contratista y estado</th><th scope="col">Dependencia</th><th scope="col">Contacto</th><th scope="col">Órdenes activas</th><th scope="col">Acciones</th></tr></thead>
                <tbody>
                    @foreach ($contractors as $c)
                        <tr data-search-item>
                            <td data-label="Contratista y estado"><strong class="report-title">{{ $c->name }}</strong><span @class(['status-badge inline-flex rounded-full border px-3 py-1.5 text-base font-semibold', 'border-emerald-200 bg-emerald-50 text-emerald-800' => $c->is_active, 'border-rose-200 bg-rose-50 text-rose-800' => !$c->is_active])>{{ $c->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                            <td data-label="Dependencia">{{ $c->department?->name ?? 'Sin dependencia' }}</td>
                            <td data-label="Contacto"><span class="block break-words">{{ $c->email }}</span><span class="report-meta block mt-2">{{ $c->phone ?: 'Sin teléfono' }}</span></td>
                            <td data-label="Órdenes activas"><strong class="text-2xl tabular-nums">{{ $c->active_orders_count }}</strong></td>
                            <td data-label="Acciones">
                                @php
                                    $hasHistory = $c->work_orders_exists || $c->reports_exists || $c->quality_inspections_exists;
                                    $confirmation = "¿Eliminar la cuenta de {$c->name}? ";
                                    $confirmation .= $hasHistory
                                        ? 'Se revocará el acceso y Alcaldía conservará el historial.'
                                        : 'Esta acción es definitiva.';
                                    if ($c->active_orders_count > 0) {
                                        $confirmation .= " Tiene {$c->active_orders_count} órdenes activas que requerirán atención de Alcaldía.";
                                    }
                                @endphp
                                <div class="row-actions items-center">
                                    <x-button :href="route('admin.contractors.edit', $c)" variant="secondary" aria-label="Editar cuenta de {{ $c->name }}">Editar</x-button>
                                    <form method="POST" action="{{ route('admin.contractors.destroy', $c) }}" data-confirm="{{ $confirmation }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Eliminar cuenta de {{ $c->name }}"
                                                class="inline-flex min-h-11 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 font-semibold text-rose-800 hover:bg-rose-100 focus-visible:ring-2 focus-visible:ring-rose-700">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <p hidden data-search-empty class="empty-state">No hay contratistas que coincidan con la búsqueda. Prueba otro nombre o limpia el filtro.</p>
        @endif
    </section>
</div>
<div id="register-modal" role="dialog" aria-modal="true" aria-labelledby="register-title" aria-describedby="register-description" class="fixed inset-0 z-50 grid place-items-center p-4 {{ $reopenRegister ? '' : 'hidden' }}">
    <div class="absolute inset-0 bg-slate-950/45" data-register_close></div>
    <form method="POST" action="{{ route('admin.contractors.store') }}" data-confirm="¿Confirmas el registro? Este contratista quedará habilitado para recibir órdenes de trabajo." class="relative w-full max-w-2xl rounded-2xl border border-border bg-white p-6 sm:p-8 shadow-xl">
        @csrf
        <div class="dialog-heading"><div><h2 id="register-title">Registrar contratista</h2><p id="register-description" class="field-help">Completa los datos de la empresa o cuadrilla. Los campos con * son obligatorios.</p></div><button type="button" class="btn-secondary dialog-close" data-register_close aria-label="Cerrar registro">✕</button></div>
        <div class="space-y-6 mt-6">
            <x-form-field name="name" id="rc-name" label="Nombre de la empresa o cuadrilla" :required="true" maxlength="120" autocomplete="organization" placeholder="Constructora ejemplo S.A.S." />
            <div class="field-grid">
                <x-form-field name="email" id="rc-email" type="email" label="Correo electrónico" :required="true" maxlength="255" autocomplete="email" />
                <x-form-field name="phone" id="rc-phone" type="tel" label="Teléfono (opcional)" maxlength="25" autocomplete="tel" />
            </div>
            <div><label for="rc-department" class="field-label">Dependencia asignada *</label>
                <select id="rc-department" class="field-control" name="department_id" required aria-invalid="{{ $errors->has('department_id') ? 'true' : 'false' }}" @if ($errors->has('department_id')) aria-describedby="rc-department-error" @endif>
                    <option value="">Selecciona una dependencia</option>
                    @foreach ($departments as $d)<option value="{{ $d->id }}" @selected((int) old('department_id') === $d->id)>{{ $d->name }}</option>@endforeach
                </select><x-field-error field="department_id" id="rc-department-error" />
            </div>
            <fieldset><legend class="field-label mb-3">Acceso al sistema</legend><div class="field-grid">
                <x-form-field name="password" id="rc-password" type="password" label="Contraseña inicial" :required="true" minlength="8" autocomplete="new-password" help="Utiliza al menos 8 caracteres." />
                <x-form-field name="password_confirmation" id="rc-password2" type="password" label="Repetir contraseña" :required="true" minlength="8" autocomplete="new-password" />
            </div></fieldset>
        </div>
        <div class="form-actions"><x-button variant="secondary" data-register_close>Cancelar</x-button><x-button type="submit">Registrar contratista</x-button></div>
    </form>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const modal = document.getElementById('register-modal');
    document.getElementById('open-register').addEventListener('click', () => { modal.classList.remove('hidden'); modal.querySelector('#rc-name').focus(); });
    modal.querySelectorAll('[data-register_close]').forEach(button => button.addEventListener('click', () => modal.classList.add('hidden')));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') modal.classList.add('hidden'); });
})();
</script>
@endpush
