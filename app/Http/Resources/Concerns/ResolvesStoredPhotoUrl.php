<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Las BD guardan RUTAS RELATIVAS en el disco `public` (portables entre
 * entornos); el contrato HTTP expone URLs absolutas para el cliente.
 */
trait ResolvesStoredPhotoUrl
{
    protected function photoUrl(?string $storedPath): ?string
    {
        if ($storedPath === null || $storedPath === '') {
            return null;
        }

        // Compatibilidad con URLs absolutas heredadas (CDN, S3, etc.).
        if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
            return $storedPath;
        }

        return Storage::disk('public')->url($storedPath);
    }
}
