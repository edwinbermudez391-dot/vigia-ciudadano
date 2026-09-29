<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Despacho Blade (Alcaldía): asigna/reasigna una incidencia a una cuadrilla.
 *
 * POST /admin/incidencias/{report}/asignar
 *
 * La elegibilidad de fondo (reporte en estado asignable, contratista activo)
 * es sede de WorkOrderDispatcher; aquí se valida la forma y se acota el
 * candidate a usuarios contractor activos para dar feedback temprano.
 */
final class AssignWorkOrderRequest extends FormRequest
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
            'instructions',
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contractor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Contractor->value)
                    ->where('is_active', true),
            ],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contractor_id' => 'cuadrilla',
            'scheduled_date' => 'fecha programada',
            'instructions' => 'instrucciones de la intervención',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contractor_id.required' => 'Selecciona la cuadrilla que ejecutará la obra.',
            'contractor_id.exists' => 'La cuadrilla seleccionada no existe o está inactiva.',
            'scheduled_date.required' => 'Indica la fecha programada de ejecución.',
            'scheduled_date.date' => 'La fecha programada no es una fecha válida.',
            'scheduled_date.after_or_equal' => 'La fecha programada no puede ser anterior a hoy.',
            'instructions.max' => 'Las instrucciones no pueden superar los :max caracteres.',
        ];
    }
}
