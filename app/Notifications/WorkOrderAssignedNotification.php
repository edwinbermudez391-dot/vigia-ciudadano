<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Report;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada al contratista cuando se le asigna una nueva orden
 * de trabajo. Incluye: ticket_code, categoría, dirección, fecha programada
 * y enlace para consultar los detalles.
 *
 * Se despacha desde el ReportObserver cuando el reporte transiciona a
 * in_progress (WorkOrderDispatcher crea la orden antes de cambiar el estado).
 */
final class WorkOrderAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Report $report,
        private readonly WorkOrder $workOrder,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticketCode = $this->report->ticket_code;
        $scheduledDate = $this->workOrder->scheduled_date->format('d/m/Y');
        $categoryName = $this->report->category?->name ?? 'Sin categoría';
        $address = $this->report->address_reference ?: 'Sin referencia de dirección';
        $url = route('reports.show', ['ticket_code' => $ticketCode]);

        // URL absoluta de la foto del daño
        $photoUrl = null;
        if ($this->report->damage_photo_url) {
            try {
                $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($this->report->damage_photo_url);
            } catch (\Throwable) {
                $photoUrl = null;
            }
        }

        return (new MailMessage)
            ->subject("Nueva orden de trabajo asignada — {$ticketCode}")
            ->view('emails.work-order-assigned', [
                'report' => $this->report,
                'workOrder' => $this->workOrder,
                'ticketCode' => $ticketCode,
                'scheduledDate' => $scheduledDate,
                'categoryName' => $categoryName,
                'address' => $address,
                'photoUrl' => $photoUrl,
                'url' => $url,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'report_id' => $this->report->id,
            'ticket_code' => $this->report->ticket_code,
            'scheduled_date' => $this->workOrder->scheduled_date->toDateString(),
            'category' => $this->report->category?->name,
        ];
    }
}
