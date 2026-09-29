<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Violación del invariante de despacho de obras (no es un error de sistema):
 * la lanzará SIEMPRE una vía de entrada válida pero con estados imposibles
 * (carrera entre pantallas). Cada canal la traduce a su lenguaje de respuesta:
 * API -> 422 JSON; Blade -> flash 'error'.
 */
final class WorkAssignmentException extends DomainException
{
    public static function reportNotAssignable(string $currentStatus): self
    {
        return new self(
            "Solo es posible asignar reportes en estado 'reported' o 'rework' "
            ."(estado actual: {$currentStatus})."
        );
    }

    public static function contractorNotEligible(): self
    {
        return new self('El usuario indicado no existe o no es un contratista activo.');
    }
}
