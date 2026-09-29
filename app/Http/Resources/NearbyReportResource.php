<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesStoredPhotoUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Incidencia cercana para pintarla en el mapa del ciudadano.
 *
 * payload ligero por diseño: sin descripción larga ni relaciones,
 * porque el endpoint puede devolver decenas de ítems por consulta.
 */
final class NearbyReportResource extends JsonResource
{
    use ResolvesStoredPhotoUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'title' => $this->title,
            'status' => $this->status->value,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address_reference' => $this->address_reference,
            'damage_photo_url' => $this->photoUrl($this->damage_photo_url),
            // Columna calculada por la query Haversine (selectRaw).
            'distance_m' => round((float) $this->distance_meters, 1),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
