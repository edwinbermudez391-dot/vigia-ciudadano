<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Notifications\ReportStatusChangedNotification;
use App\Notifications\WorkOrderAssignedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Observer del modelo Report: detecta cambios de estado y despacha
 * notificaciones por correo a los suscriptores y al contratista.
 *
 * Transiciones monitoreadas:
 *   - Cualquier cambio de status → ReportStatusChangedNotification a suscriptores
 *   - reported/rework → in_progress → WorkOrderAssignedNotification al contratista
 *
 * Las notificaciones implementan ShouldQueue: Laravel las encola automáticamente
 * y las despacha tras confirmar la transacción (after_commit). En local con
 * QUEUE_CONNECTION=sync se ejecutan de forma síncrona.
 */
final class ReportObserver
{
    /**
     * Se dispara después de que el modelo se actualiza en la BD.
     * Solo reacciona si el campo 'status' cambió (ignora otros updates).
     */
    public function updated(Report $report): void
    {
        // No notificar en la creación inicial (el status se establece por primera vez)
        if ($report->wasRecentlyCreated) {
            return;
        }

        if (! $report->wasChanged('status')) {
            return;
        }

        // getRawOriginal evita los casts: devuelve el valor crudo de la BD
        // (string), no la instancia del enum que getOriginal() sí hidrata.
        $oldStatus = ReportStatus::tryFrom((string) $report->getRawOriginal('status'));
        $newStatus = $report->status;

        if ($oldStatus === null || $oldStatus === $newStatus) {
            return;
        }

        // Cargar relaciones necesarias para las notificaciones
        $report->loadMissing(['subscribers', 'workOrder.contractor', 'workOrder.qualityInspections', 'category']);

        // Resolver observaciones contextuales según la transición
        $observations = $this->resolveObservations($report, $oldStatus, $newStatus);

        // 1. Notificar a todos los suscriptores del reporte
        foreach ($report->subscribers as $subscriber) {
            $subscriber->notify(
                new ReportStatusChangedNotification($report, $oldStatus, $newStatus, $observations)
            );
        }

        // 2. Notificar al citizen_email si es un reporte anónimo con contacto
        if ($report->citizen_email) {
            Notification::route('mail', $report->citizen_email)
                ->notify(new ReportStatusChangedNotification($report, $oldStatus, $newStatus, $observations));
        }

        // 3. Si el reporte pasó a in_progress (fue asignado), notificar al contratista
        if ($newStatus === ReportStatus::InProgress) {
            $this->notifyContractor($report);
        }
    }

    /**
     * Resuelve las observaciones contextuales según la transición de estado.
     * Extrae información relevante de las relaciones cargadas del reporte.
     */
    private function resolveObservations(Report $report, ReportStatus $oldStatus, ReportStatus $newStatus): string
    {
        return match (true) {
            $newStatus === ReportStatus::InProgress => $this->assignmentObservations($report),
            $newStatus === ReportStatus::UnderInspection => 'La obra ha sido completada por el contratista y ha pasado a inspección de calidad.',
            $newStatus === ReportStatus::Closed => 'La inspección de calidad aprobó la obra. El reporte ha sido cerrado satisfactoriamente.',
            $newStatus === ReportStatus::Rework => $this->reworkObservations($report),
            default => "El estado del reporte ha cambiado de {$oldStatus->value} a {$newStatus->value}.",
        };
    }

    /**
     * Observaciones para cuando el reporte es asignado a un contratista.
     */
    private function assignmentObservations(Report $report): string
    {
        $workOrder = $report->workOrder;

        if ($workOrder && $workOrder->contractor) {
            $contractorName = $workOrder->contractor->name;
            $scheduledDate = $workOrder->scheduled_date->format('d/m/Y');

            return "Tu reporte ha sido asignado a la cuadrilla \"{$contractorName}\". Fecha programada: {$scheduledDate}.";
        }

        return 'Tu reporte ha sido asignado a una cuadrilla de trabajo.';
    }

    /**
     * Observaciones para cuando el reporte pasa a retrabajo (inspección rechazada).
     */
    private function reworkObservations(Report $report): string
    {
        $lastInspection = $report->workOrder?->qualityInspections?->sortByDesc('inspected_at')->first();

        if ($lastInspection && $lastInspection->rejection_reason) {
            return "La inspección de calidad rechazó la obra. Motivo: {$lastInspection->rejection_reason}";
        }

        return 'La inspección de calidad rechazó la obra. El contratista deberá realizar ajustes.';
    }

    /**
     * Despacha WorkOrderAssignedNotification al contratista responsable.
     */
    private function notifyContractor(Report $report): void
    {
        $workOrder = $report->workOrder;

        if (! $workOrder || ! $workOrder->contractor) {
            return;
        }

        $workOrder->contractor->notify(new WorkOrderAssignedNotification($report, $workOrder));
    }
}
