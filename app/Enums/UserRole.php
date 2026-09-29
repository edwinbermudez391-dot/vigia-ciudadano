<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles de usuario del sistema "Vigía Ciudadano".
 *
 * El sistema de autenticación es exclusivo para personal operativo e institucional.
 * Los ciudadanos reportan incidencias de forma pública y anónima sin necesidad de cuenta.
 */
enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Contractor = 'contractor';
    case QualityInspector = 'quality_inspector';

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
