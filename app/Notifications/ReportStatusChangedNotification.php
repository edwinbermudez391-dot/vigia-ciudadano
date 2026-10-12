<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada a los suscriptores de un reporte (y al citizen_email
 * cuando aplica) cada vez que el estado del reporte cambia.
 *
 * Se despacha desde el ReportObserver al detectar wasChanged('status').
 * Incluye: ticket_code, estado anterior/nuevo con badge de color,
 * observaciones contextuales y enlace público de seguimiento.
 */
final class ReportStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Etiquetas legibles en español para cada estado del dominio.
     */
    private const STATUS_LABELS = [
        'reported' => 'Reportado',
        'in_progress' => 'En progreso',
        'under_inspection' => 'Bajo inspección',
        'rework' => 'Retrabajo',
        'closed' => 'Cerrado',
    ];

    /**
     * Colores hexadecimales del badge por estado (accesibles con contraste AA).
     */
    private const STATUS_COLORS = [
        'reported' => ['bg' => '#e2e8f0', 'text' => '#334155'],
        'in_progress' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        'under_inspection' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'rework' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'closed' => ['bg' => '#d1fae5', 'text' => '#065f46'],
    ];

    public function __construct(
        private readonly Report $report,
        private readonly ReportStatus $oldStatus,
        private readonly ReportStatus $newStatus,
        private readonly string $observations = '',
    ) {}

    /**
     * Los suscriptores (User) reciben la notificación por correo.
     * Los anonymous notifiables (citizen_email) también usan 'mail'.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticketCode = $this->report->ticket_code;
        $newStatus = $this->newStatus->value;
        $oldStatus = $this->oldStatus->value;

        $statusLabel = self::STATUS_LABELS[$newStatus] ?? $newStatus;
        $statusColor = self::STATUS_COLORS[$newStatus] ?? ['bg' => '#e2e8f0', 'text' => '#334155'];

        // URL pública de seguimiento del reporte
        $url = route('reports.show', ['ticket_code' => $ticketCode]);

        // URL absoluta de la foto del daño (si existe en storage público)
        $photoUrl = null;
        if ($this->report->damage_photo_url) {
            try {
                $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($this->report->damage_photo_url);
            } catch (\Throwable) {
                $photoUrl = null;
            }
        }

        return (new MailMessage)
            ->subject("Actualización del reporte {$ticketCode} — {$statusLabel}")
            ->view('emails.report-status', [
                'report' => $this->report,
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
                'statusLabel' => $statusLabel,
                'statusBgColor' => $statusColor['bg'],
                'statusTextColor' => $statusColor['text'],
                'observations' => $this->observations,
                'url' => $url,
                'photoUrl' => $photoUrl,
                'ticketCode' => $ticketCode,
            ]);
    }

    /**
     * Representación en array (útil para canal database si se agrega en el futuro).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'ticket_code' => $this->report->ticket_code,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'observations' => $this->observations,
        ];
    }
}
