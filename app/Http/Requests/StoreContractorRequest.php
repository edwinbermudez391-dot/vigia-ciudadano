<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de cuadrilla/contratista desde el panel de Alcaldía.
 *
 * POST /admin/contratistas
 *
 * El rol (contractor) y la activación (is_active) NO son entrada del
 * cliente: los fija el controlador. Aquí solo se valida la forma, y la
 * contraseña exige confirmación porque la cuadrilla inicia sesión con ella.
 */
final class StoreContractorRequest extends FormRequest
{
    use SanitizesInput;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->is_active === true && $user->isSuperAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeStrings($this->only([
            'name',
            'phone',
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:filter', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre de la cuadrilla',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'department_id' => 'dependencia asignada',
            'password' => 'contraseña inicial',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Indica el nombre de la empresa o cuadrilla.',
            'email.required' => 'El correo electrónico es obligatorio: con él inicia sesión la cuadrilla.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe un usuario registrado con ese correo.',
            'department_id.required' => 'Asigna la dependencia bajo la cual operará la cuadrilla.',
            'department_id.exists' => 'La dependencia seleccionada no existe.',
            'password.required' => 'Define la contraseña inicial con la que la cuadrilla accederá al sistema.',
            'password.min' => 'La contraseña inicial debe tener al menos :min caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
