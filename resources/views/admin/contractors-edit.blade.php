@extends('layouts.app')
@section('title', 'Editar contratista — Vigía Ciudadano')

@section('content')
<div class="space-y-6">
    <x-page-heading title="Editar contratista" :description="$contractor->name">
        <x-button :href="route('admin.contractors')" variant="secondary">Volver a contratistas</x-button>
    </x-page-heading>

    <form method="POST" action="{{ route('admin.contractors.update', $contractor) }}" class="panel max-w-3xl space-y-6 p-4 sm:p-6">
        @csrf
        @method('PATCH')

        <x-form-field name="name" id="ec-name" label="Nombre de la empresa o cuadrilla" :value="$contractor->name" :required="true" maxlength="120" autocomplete="organization" />
        <div class="field-grid">
            <x-form-field name="email" id="ec-email" type="email" label="Correo electrónico" :value="$contractor->email" :required="true" maxlength="255" autocomplete="email" />
            <x-form-field name="phone" id="ec-phone" type="tel" label="Teléfono (opcional)" :value="$contractor->phone" maxlength="25" autocomplete="tel" />
        </div>
        <div>
            <label for="ec-department" class="field-label">Dependencia asignada</label>
            <select id="ec-department" class="field-control" name="department_id" @required($contractor->department_id !== null)
                    aria-invalid="{{ $errors->has('department_id') ? 'true' : 'false' }}"
                    @if ($errors->has('department_id')) aria-describedby="ec-department-error" @endif>
                <option value="">{{ $contractor->department_id === null ? 'Sin dependencia' : 'Selecciona una dependencia' }}</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((int) old('department_id', $contractor->department_id) === $department->id)>
                        {{ $department->name }}{{ $department->is_active ? '' : ' (inactiva)' }}
                    </option>
                @endforeach
            </select>
            <x-field-error field="department_id" id="ec-department-error" />
        </div>

        <fieldset class="space-y-3 border-t border-border pt-4">
            <legend class="field-label">Cambiar contraseña</legend>
            <p class="field-help">Deja estos dos campos vacíos si quieres conservar la contraseña actual.</p>
            <div class="field-grid">
                <x-form-field name="password" id="ec-password" type="password" label="Nueva contraseña" minlength="8" autocomplete="new-password" />
                <x-form-field name="password_confirmation" id="ec-password-confirmation" type="password" label="Confirmar nueva contraseña" minlength="8" autocomplete="new-password" />
            </div>
        </fieldset>

        <div class="form-actions">
            <x-button :href="route('admin.contractors')" variant="secondary">Cancelar</x-button>
            <x-button type="submit">Guardar cambios</x-button>
        </div>
    </form>
</div>
@endsection
