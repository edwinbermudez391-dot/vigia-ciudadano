<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Ciclo de vida del reporte ciudadano.
 *
 * Flujo normal: reported → in_progress → under_inspection → closed.
 * Ramo de rechazo: under_inspection → rework → in_progress.
 */
enum ReportStatus: string
{
    case Reported = 'reported';
    case InProgress = 'in_progress';
    case UnderInspection = 'under_inspection';
    case Rework = 'rework';
    case Closed = 'closed';

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
