<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Violación de la máquina de estados de EJECUCIÓN de la orden de trabajo
 * (assigned -> in_execution -> completed). La comparten API y Blade; cada
 * canal la traduce: API -> 422 JSON; Blade -> flash 'error'.
 */
final class WorkExecutionException extends DomainException
{
    public static function alreadyCompleted(): self
    {
        return new self('La orden ya fue completada; está pendiente de inspección de calidad.');
    }

    public static function notExecutable(string $currentStatus): self
    {
        return new self(
            "Solo una orden en ejecución puede completarse (estado actual: {$currentStatus})."
        );
    }
}
