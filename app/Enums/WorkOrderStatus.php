<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estados de una orden de trabajo ejecutada por el contratista.
 */
enum WorkOrderStatus: string
{
    case Assigned = 'assigned';
    case InExecution = 'in_execution';
    case Completed = 'completed';

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
