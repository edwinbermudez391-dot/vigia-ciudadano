<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\WorkOrder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cierre de orden de trabajo por parte del contratista asignado.
 *
 * POST /api/v1/work-orders/{workOrder}/complete  (multipart/form-data)
 *
 * Autorización en tres niveles: cuenta activa + rol contractor + propiedad
 * de la orden. Sin el identificador de la orden en la ruta no se puede
 * comprobar la propiedad, así que se deniega (default-deny).
 *
 * La foto se valida contra su contenido real detectado por `finfo`
 * (`image` + `mimes` no confían en el Content-Type del cliente).
 */
final class CompleteWorkOrderRequest extends FormRequest
{
    use SanitizesInput;

    private const MAX_IMAGE_PIXELS = 4000;

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || $user->is_active !== true || $user->role !== UserRole::Contractor) {
            return false;
        }

        return $this->ownsWorkOrder($user->id);
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(
            'Solo el contratista activo asignado a esta orden de trabajo puede marcarla como completada.'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeStrings($this->only([
            'work_description',
        ])));
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'work_description' => ['required', 'string', 'min:20', 'max:1500'],
            'solution_photo' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
                'dimensions:max_width='.self::MAX_IMAGE_PIXELS.',max_height='.self::MAX_IMAGE_PIXELS,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'work_description' => 'descripción de la obra realizada',
            'solution_photo' => 'foto de la solución',
        ];
    }

    /**
     * Mensajes de error claros y en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'work_description.required' => 'Debes describir el trabajo realizado al cerrar la orden.',
            'work_description.string' => 'La descripción de la obra debe ser texto.',
            'work_description.min' => 'La descripción de la obra debe tener al menos :min caracteres.',
            'work_description.max' => 'La descripción de la obra no puede superar los :max caracteres.',

            'solution_photo.required' => 'La foto de la solución es obligatoria: adjunta la evidencia en el archivo "solution_photo".',
            'solution_photo.image' => 'El archivo "solution_photo" no es una imagen válida según su contenido (solo se aceptan JPEG, PNG o WebP).',
            'solution_photo.mimes' => 'La foto de la solución debe ser una imagen de tipo: :values.',
            'solution_photo.max' => 'La foto de la solución no puede superar los 5 MB.',
            'solution_photo.dimensions' => 'La imagen no puede superar '.self::MAX_IMAGE_PIXELS.'×'.self::MAX_IMAGE_PIXELS.' píxeles.',
        ];
    }

    /**
     * Comprueba que la orden referenciada pertenece al usuario autenticado.
     */
    private function ownsWorkOrder(int $userId): bool
    {
        $parameter = $this->route('workOrder') ?? $this->route('work_order');

        if ($parameter instanceof WorkOrder) {
            // Route model binding: se resuelve sin query adicional.
            return $parameter->contractor_id === $userId;
        }

        if (is_numeric($parameter)) {
            return WorkOrder::query()
                ->whereKey((int) $parameter)
                ->where('contractor_id', $userId)
                ->exists();
        }

        // Default-deny: sin identificador de orden no hay cómo verificar propiedad.
        return false;
    }
}
