<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Consulta del mapa ciudadano: reportes activos dentro de un radio dado.
 *
 * GET /api/v1/reports/nearby
 *
 * El radio máximo de 100 m y la exigencia de categoría acotan el barrido
 * geográfico (previene enumeración masiva de la base de reportes).
 */
final class CheckNearbyReportsRequest extends FormRequest
{
    use SanitizesInput;

    /**
     * Radio por defecto cuando el cliente no lo envía.
     */
    public const DEFAULT_RADIUS_METERS = 20;

    /**
     * Solo usuarios autenticados y activos: el mapa detallado no es público
     * porque revela posiciones exactas de incidencias.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->is_active === true;
    }

    /**
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(
            'Tu sesión no está activa o no tienes permisos para consultar los reportes cercanos.'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeStrings($this->only([
            'latitude',
            'longitude',
            'category_id',
            'radius_meters',
        ])));

        if ($this->input('radius_meters') === null) {
            $this->merge(['radius_meters' => self::DEFAULT_RADIUS_METERS]);
        }
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'radius_meters' => ['nullable', 'numeric', 'min:5', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'category_id' => 'categoría',
            'radius_meters' => 'radio de búsqueda',
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
            'latitude.required' => 'La latitud es obligatoria para buscar reportes cercanos.',
            'latitude.numeric' => 'La latitud debe ser un número decimal (ej: 6.2476).',
            'latitude.between' => 'La latitud debe estar entre -90 y 90 grados.',

            'longitude.required' => 'La longitud es obligatoria para buscar reportes cercanos.',
            'longitude.numeric' => 'La longitud debe ser un número decimal (ej: -75.5812).',
            'longitude.between' => 'La longitud debe estar entre -180 y 180 grados.',

            'category_id.required' => 'Debes indicar la categoría que quieres consultar.',
            'category_id.integer' => 'La categoría debe identificarse con un número entero.',
            'category_id.exists' => 'La categoría seleccionada no existe en el catálogo.',

            'radius_meters.numeric' => 'El radio de búsqueda debe ser un número de metros.',
            'radius_meters.min' => 'El radio de búsqueda no puede ser menor a :min metros.',
            'radius_meters.max' => 'El radio de búsqueda no puede superar los :max metros por seguridad.',
        ];
    }
}
