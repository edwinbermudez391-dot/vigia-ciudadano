<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;

/**
 * Etiquetas y clases Tailwind por estado, en un único punto para que la
 * API, las vistas Blade y los componentes compartidos nunca diverjan.
 */
final class StatusUi
{
    /**
     * @return array{label: string, classes: string}
     */
    public static function badge(ReportStatus|WorkOrderStatus $status): array
    {
        return match (true) {
            $status === ReportStatus::Reported => self::def('Reportado', 'amber'),
            $status === ReportStatus::InProgress => self::def('En ejecución', 'sky'),
            $status === ReportStatus::UnderInspection => self::def('En inspección', 'violet'),
            $status === ReportStatus::Rework => self::def('Retrabajo', 'orange'),
            $status === ReportStatus::Closed => self::def('Cerrada', 'emerald'),
            $status === WorkOrderStatus::Assigned => self::def('Asignada', 'slate'),
            $status === WorkOrderStatus::InExecution => self::def('En ejecución', 'sky'),
            $status === WorkOrderStatus::Completed => self::def('Terminada', 'violet'),
        };
    }

    /**
     * Insignias pastel sobre interfaz clara: fondo 50/100, borde 200 y
     * texto oscuro del mismo tono para mantener el contraste AA.
     *
     * @return array{label: string, classes: string}
     */
    private static function def(string $label, string $hue): array
    {
        $classes = match ($hue) {
            'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
            'sky' => 'bg-sky-50 text-sky-900 border-sky-200',
            'violet' => 'bg-violet-50 text-indigo-700 border-violet-200',
            'orange' => 'bg-orange-50 text-orange-800 border-orange-200',
            'emerald' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };

        return ['label' => $label, 'classes' => $classes];
    }
}
