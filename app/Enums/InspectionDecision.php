<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Resultado de la inspección de calidad sobre una orden de trabajo.
 */
enum InspectionDecision: string
{
    case Approved = 'approved';
    case Rework = 'rework';

    /**
     * Valores crudos, útiles para validación (Rule::in / enum).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
