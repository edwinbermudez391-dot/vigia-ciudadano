<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesStoredPhotoUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Orden de trabajo asociada a un reporte, con su historial de inspecciones.
 */
final class WorkOrderResource extends JsonResource
{
    use ResolvesStoredPhotoUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completion_date' => $this->completion_date?->toIso8601String(),
            'work_description' => $this->work_description,
            'solution_photo_url' => $this->photoUrl($this->solution_photo_url),
            // Contexto mínimo de la incidencia: sin esto el contratista no puede
            // identificar qué obra atender al listar su agenda. whenLoaded evita
            // lazy loading implícito; se omite cuando el controlador no carga
            // la relación (p. ej. ReportResource ya envuelve el reporte completo).
            'report' => $this->whenLoaded('report', fn (): array => [
                'id' => $this->report->id,
                'ticket_code' => $this->report->ticket_code,
                'title' => $this->report->title,
                'status' => $this->report->status->value,
                'latitude' => $this->report->latitude,
                'longitude' => $this->report->longitude,
                'address_reference' => $this->report->address_reference,
                'damage_photo_url' => $this->photoUrl($this->report->damage_photo_url),
                'category' => $this->report->relationLoaded('category') && $this->report->category !== null
                    ? [
                        'id' => $this->report->category->id,
                        'name' => $this->report->category->name,
                        'sla_hours' => $this->report->category->sla_hours,
                    ]
                    : null,
            ]),
            // Forma con callback: si la relación no está eager-loaded la clave
            // se OMITE. Pasar whenLoaded() directo a ::collection() o al
            // constructor es una bomba de tiempo (recurso null => 500).
            'contractor' => $this->whenLoaded(
                'contractor',
                fn (): UserBriefResource => new UserBriefResource($this->contractor),
            ),
            'quality_inspections' => $this->whenLoaded(
                'qualityInspections',
                fn () => QualityInspectionResource::collection($this->qualityInspections),
            ),
        ];
    }
}
