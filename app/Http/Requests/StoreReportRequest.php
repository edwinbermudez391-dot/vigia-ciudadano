<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de reporte ciudadano.
 *
 * POST /api/v1/reports  (multipart/form-data)
 * POST /reportes        (portal web SSR)
 *
 * Nota de seguridad: el `user_id` NO se acepta del payload; el controlador lo
 * toma de $request->user()?->id para impedir suplantación de identidad.
 * La foto se valida contra su contenido real (magic bytes vía `finfo`):
 * `image` + `mimes` usan `File::isImage()`/`guessExtension()`, que se basan
 * en el MIME detectado del binario, no en `Content-Type` ni en la extensión del
 * nombre, ambos fácilmente falsificables por el cliente. `dimensions` acota
 * bombas de descompresión (imágenes gigapixel).
 *
 * Flujo público: ciudadanos sin cuenta pueden radicar reportes. Los campos
 * citizen_name, citizen_phone y citizen_email son opcionales para permitir
 * seguimiento sin fricción.
 */
final class StoreReportRequest extends FormRequest
{
    use SanitizesInput;

    /**
     * Resolución máxima aceptada para mitigar decompression bombs.
     */
    private const MAX_IMAGE_PIXELS = 4000;

    /**
     * Permite acceso público: cualquier ciudadano (autenticado o invitado)
     * puede radicar reportes. Si está autenticado, se vincula a su cuenta;
     * si es invitado, se registran los datos de contacto opcionales.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeStrings($this->only([
            'category_id',
            'title',
            'description',
            'latitude',
            'longitude',
            'address_reference',
            'citizen_name',
            'citizen_phone',
            'citizen_email',
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:15', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address_reference' => ['nullable', 'string', 'max:255'],
            'damage_photo' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
                'dimensions:max_width='.self::MAX_IMAGE_PIXELS.',max_height='.self::MAX_IMAGE_PIXELS,
            ],
            'citizen_name' => ['nullable', 'string', 'max:120'],
            'citizen_phone' => ['nullable', 'string', 'max:25'],
            'citizen_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'title' => 'título del reporte',
            'description' => 'descripción',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'address_reference' => 'referencia de dirección',
            'damage_photo' => 'foto del daño',
            'citizen_name' => 'nombre',
            'citizen_phone' => 'teléfono',
            'citizen_email' => 'correo electrónico',
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
            'category_id.required' => 'Debes seleccionar la categoría del daño reportado.',
            'category_id.integer' => 'La categoría debe identificarse con un número entero.',
            'category_id.exists' => 'La categoría seleccionada no existe en el catálogo.',

            'title.required' => 'El título del reporte es obligatorio.',
            'title.string' => 'El título debe ser texto.',
            'title.min' => 'El título debe tener al menos :min caracteres.',
            'title.max' => 'El título no puede superar los :max caracteres.',

            'description.required' => 'Describe el problema encontrado: la descripción es obligatoria.',
            'description.string' => 'La descripción debe ser texto.',
            'description.min' => 'La descripción debe tener al menos :min caracteres para ser útil a la dependencia.',
            'description.max' => 'La descripción no puede superar los :max caracteres.',

            'latitude.required' => 'La latitud es obligatoria: el reporte debe tener una ubicación.',
            'latitude.numeric' => 'La latitud debe ser un número decimal (ej: 6.2476).',
            'latitude.between' => 'La latitud debe estar entre -90 y 90 grados.',

            'longitude.required' => 'La longitud es obligatoria: el reporte debe tener una ubicación.',
            'longitude.numeric' => 'La longitud debe ser un número decimal (ej: -75.5812).',
            'longitude.between' => 'La longitud debe estar entre -180 y 180 grados.',

            'address_reference.string' => 'La referencia de dirección debe ser texto.',
            'address_reference.max' => 'La referencia de dirección no puede superar los :max caracteres.',

            'damage_photo.required' => 'La foto del daño es obligatoria: adjunta la evidencia en el archivo "damage_photo".',
            'damage_photo.image' => 'El archivo "damage_photo" no es una imagen válida según su contenido (solo se aceptan JPEG, PNG o WebP).',
            'damage_photo.mimes' => 'La foto del daño debe ser una imagen de tipo: :values.',
            'damage_photo.max' => 'La foto del daño no puede superar los 5 MB.',
            'damage_photo.dimensions' => 'La imagen no puede superar '.self::MAX_IMAGE_PIXELS.'×'.self::MAX_IMAGE_PIXELS.' píxeles.',

            'citizen_name.string' => 'El nombre debe ser texto.',
            'citizen_name.max' => 'El nombre no puede superar los :max caracteres.',

            'citizen_phone.string' => 'El teléfono debe ser texto.',
            'citizen_phone.max' => 'El teléfono no puede superar los :max caracteres.',

            'citizen_email.email' => 'El correo electrónico debe ser una dirección válida.',
            'citizen_email.max' => 'El correo electrónico no puede superar los :max caracteres.',
        ];
    }
}
