<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles de usuario del sistema "Vigía Ciudadano".
 *
 * Los ciudadanos pueden reportar incidencias de forma pública y anónima (sin cuenta),
 * pero también pueden registrarse con rol `citizen` para vincular reportes a su cuenta,
 * suscribirse a novedades y consultar su trazabilidad desde el panel personal.
 */
enum UserRole: string
{
    case Citizen = 'citizen';
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
