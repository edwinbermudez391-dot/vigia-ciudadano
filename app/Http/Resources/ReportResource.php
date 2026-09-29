<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesStoredPhotoUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detalle completo de un reporte ciudadano (store y show).
 *
 * Usa whenLoaded() para no disparar lazy loading implícito: solo se
 * serializan las relaciones que el controlador cargó de forma eager.
 */
final class ReportResource extends JsonResource
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
            'description' => $this->description,
            'status' => $this->status->value,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address_reference' => $this->address_reference,
            'damage_photo_url' => $this->photoUrl($this->damage_photo_url),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'sla_hours' => $this->category->sla_hours,
                'department_id' => $this->category->department_id,
            ]),
            'user' => $this->whenLoaded('user', fn (): ?array => $this->user === null
                ? null
                : ['id' => $this->user->id, 'name' => $this->user->name]),
            'subscribers' => $this->whenLoaded(
                'subscribers',
                fn () => UserBriefResource::collection($this->subscribers),
            ),
            // Callback null-safe: un reporte recién creado aún no tiene orden y
            // `new WorkOrderResource(null)` con el recurso vacío revienta al
            // serializar status->value. Con esta forma responde work_order: null.
            'work_order' => $this->whenLoaded(
                'workOrder',
                fn (): ?WorkOrderResource => $this->workOrder === null
                    ? null
                    : new WorkOrderResource($this->workOrder),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
