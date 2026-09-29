<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesStoredPhotoUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detalle de una inspección de calidad.
 */
final class QualityInspectionResource extends JsonResource
{
    use ResolvesStoredPhotoUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision' => $this->decision->value,
            'checklist_results' => $this->checklist_results,
            'rejection_reason' => $this->rejection_reason,
            'verification_photo_url' => $this->photoUrl($this->verification_photo_url),
            'inspector_latitude' => $this->inspector_latitude,
            'inspector_longitude' => $this->inspector_longitude,
            'inspected_at' => $this->inspected_at?->toIso8601String(),
            // Callback: si inspector no está eager-loaded se omite la clave.
            'inspector' => $this->whenLoaded(
                'inspector',
                fn (): UserBriefResource => new UserBriefResource($this->inspector),
            ),
        ];
    }
}
