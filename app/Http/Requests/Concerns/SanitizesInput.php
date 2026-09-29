<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * Normaliza y limpia las entradas escalares ANTES de validar (defense in depth).
 *
 * Regla de seguridad: ningún payload llega a reglas como `exists:` o a la capa
 * de servicio tal cual vino del cliente. Esto no sustituye el escapado en la
 * salida (Blade/resources), pero reduce la superficie de:
 *  - Inyección de bytes de control (NULL 0x00 trunca registros; 0x1B escape,
 *    etc.) usados para ocultar payloads o evadir filtros.
 *  - CRLF injection (headers/log forging) al estandarizar saltos de línea.
 *  - XSS almacenado al retirar etiquetas HTML/SVG de campos de texto plano.
 *  - Consola/JSON smuggling con whitespace periférico.
 */
trait SanitizesInput
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function sanitizeStrings(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                // Pools pequeños (checklist); recursión acotada por el
                // propio límite de items del payload.
                $input[$key] = $this->sanitizeStrings($value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            // 1. Estandariza saltos de línea (CRLF/CR -> LF).
            $normalized = str_replace(["\r\n", "\r"], "\n", $value);

            // 2. Elimina caracteres de control conservando LF y TAB.
            $stripped = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $normalized);

            // 3. Un fallo de preg_replace (/u) implica UTF-8 inválido:
            //    se degrada a cadena vacía para que `required`/`string`
            //    rechacen el payload malformado.
            $clean = is_string($stripped) ? $stripped : '';

            // 4. Retira etiquetas HTML/SVG de campos de texto plano.
            $input[$key] = trim(strip_tags($clean));
        }

        return $input;
    }
}
