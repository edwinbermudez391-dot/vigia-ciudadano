<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UserRole;

/**
 * Etiquetas legibles de los roles y pictogramas de categorías para la
 * interfaz (nav, firmas, selector de reporte).
 */
final class RoleUi
{
    public static function label(UserRole $role): string
    {
        return match ($role) {
            UserRole::SuperAdmin => 'Alcaldía',
            UserRole::Contractor => 'Contratista',
            UserRole::QualityInspector => 'Inspector de calidad',
        };
    }

    /**
     * Icono del catálogo: reconoce la categoría por palabras de su nombre,
     * sin acoplarse a IDs sembrados (que cambian entre entornos).
     */
    public static function categoryIcon(string $name): string
    {
        return match (true) {
            (bool) preg_match('/bach|v[ií]a|malla|paviment/i', $name) => '🕳️',
            (bool) preg_match('/luminar|alumbrad|ilumin/i', $name) => '💡',
            (bool) preg_match('/fuga|acueducto|agua/i', $name) => '💧',
            (bool) preg_match('/alcantarill|sumider|aguada|desag[uü]e/i', $name) => '🚰',
            default => '📌',
        };
    }
}
