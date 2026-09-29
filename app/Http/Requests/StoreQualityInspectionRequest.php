<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Rules\ChecklistAnswerIsScalar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Registro de inspección de calidad sobre una orden de trabajo.
 *
 * POST /api/v1/work-orders/{workOrder}/inspections
 *
 * Restricción adicional de payloads: cada respuesta del checklist debe ser un
 * escalar pequeño (bool/numérico/string <=255). Se rechazan arrays u objetos
 * anidados para que nadie use este campo como contenedor de estructuras
 * arbitrarias (JSON smuggling / DoS por profundidad).
 */
final class StoreQualityInspectionRequest extends FormRequest
{
    use SanitizesInput;

    private const MAX_IMAGE_PIXELS = 4000;

    private const MAX_CHECKLIST_ITEMS = 50;

    /**
     * Solo inspectores de calidad con cuenta activa.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->is_active === true
            && $user->role === UserRole::QualityInspector;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(
            'Solo los inspectores de calidad con cuenta activa pueden registrar inspecciones.'
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeStrings($this->only([
            'decision',
            'checklist_results',
            'rejection_reason',
            'inspector_latitude',
            'inspector_longitude',
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
            'decision' => ['required', 'in:approved,rework'],
            'checklist_results' => [
                'required',
                'array',
                'min:1',
                'max:'.self::MAX_CHECKLIST_ITEMS,
            ],
            'checklist_results.*' => [new ChecklistAnswerIsScalar],
            'rejection_reason' => ['required_if:decision,rework', 'nullable', 'string', 'min:20', 'max:1000'],
            'inspector_latitude' => ['required', 'numeric', 'between:-90,90'],
            'inspector_longitude' => ['required', 'numeric', 'between:-180,180'],
            'verification_photo' => [
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
            'decision' => 'decisión',
            'checklist_results' => 'resultados del checklist',
            'checklist_results.*' => 'respuesta del checklist',
            'rejection_reason' => 'motivo del rechazo',
            'inspector_latitude' => 'latitud del inspector',
            'inspector_longitude' => 'longitud del inspector',
            'verification_photo' => 'foto de verificación',
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
            'decision.required' => 'Debes indicar la decisión de la inspección.',
            'decision.in' => 'La decisión debe ser "approved" (aprobada) o "rework" (rechazada).',

            'checklist_results.required' => 'Los resultados del checklist son obligatorios.',
            'checklist_results.array' => 'Los resultados del checklist deben enviarse como un objeto JSON de pares "ítem": respuesta.',
            'checklist_results.min' => 'El checklist debe contener al menos :min ítem evaluado.',
            'checklist_results.max' => 'El checklist no puede superar los :max ítems.',

            'rejection_reason.required_if' => 'Si la decisión es "rework", debes justificar el rechazo por escrito.',
            'rejection_reason.string' => 'El motivo del rechazo debe ser texto.',
            'rejection_reason.min' => 'La justificación del rechazo debe tener al menos :min caracteres.',
            'rejection_reason.max' => 'La justificación del rechazo no puede superar los :max caracteres.',

            'inspector_latitude.required' => 'La latitud del inspector es obligatoria: la inspección debe geolocalizarse.',
            'inspector_latitude.numeric' => 'La latitud del inspector debe ser un número decimal.',
            'inspector_latitude.between' => 'La latitud del inspector debe estar entre -90 y 90 grados.',

            'inspector_longitude.required' => 'La longitud del inspector es obligatoria: la inspección debe geolocalizarse.',
            'inspector_longitude.numeric' => 'La longitud del inspector debe ser un número decimal.',
            'inspector_longitude.between' => 'La longitud del inspector debe estar entre -180 y 180 grados.',

            'verification_photo.required' => 'La foto de verificación es obligatoria: adjunta la evidencia en el archivo "verification_photo".',
            'verification_photo.image' => 'El archivo "verification_photo" no es una imagen válida según su contenido (solo se aceptan JPEG, PNG o WebP).',
            'verification_photo.mimes' => 'La foto de verificación debe ser una imagen de tipo: :values.',
            'verification_photo.max' => 'La foto de verificación no puede superar los 5 MB.',
            'verification_photo.dimensions' => 'La imagen no puede superar '.self::MAX_IMAGE_PIXELS.'×'.self::MAX_IMAGE_PIXELS.' píxeles.',
        ];
    }
}
