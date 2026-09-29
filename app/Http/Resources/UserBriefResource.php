<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación pública mínima de un usuario.
 *
 * Privacidad deliberada: NO se expone email ni phone. En show(), la lista de
 * subscribers es visible para cualquiera que consulte el ticket; revelar su
 * contacto sería una filtración de PII.
 */
final class UserBriefResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
