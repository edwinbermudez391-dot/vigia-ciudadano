<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateContractorRequest extends FormRequest
{
    use SanitizesInput;

    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user?->isSuperAdmin() || ! $user->is_active) {
            return false;
        }

        $contractor = $this->route('contractor');
        abort_unless($contractor instanceof User && $contractor->isContractor() && $contractor->contractor_archived_at === null, 404);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeStrings($this->only(['name', 'phone'])));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $contractor = $this->route('contractor');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:filter', 'max:255', Rule::unique('users', 'email')->ignore($contractor)],
            'phone' => ['nullable', 'string', 'max:25'],
            'department_id' => [
                Rule::requiredIf($contractor->department_id !== null),
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $contractor->department_id)),
                ),
            ],
            'password' => [
                'nullable', 'required_with:password_confirmation', 'string', 'min:8', 'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && config('hashing.driver') === 'bcrypt' && strlen($value) > 72) {
                        $fail('La contraseña es demasiado larga. Usa hasta 72 bytes.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre de la cuadrilla',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'department_id' => 'dependencia asignada',
            'password' => 'nueva contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario registrado con ese correo.',
            'department_id.exists' => 'Selecciona una dependencia activa.',
            'password.min' => 'La nueva contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.required_with' => 'Escribe la nueva contraseña antes de confirmarla.',
        ];
    }
}
