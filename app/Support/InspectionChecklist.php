<?php

namespace App\Support;

final class InspectionChecklist
{
    public static function forCategory(?string $name): array
    {
        if ($name === 'Baches') $name = 'Baches y Malla Vial';

        return (config('inspection.categories')[$name ?? ''] ?? config('inspection.fallback'))
            + config('inspection.common');
    }
}
