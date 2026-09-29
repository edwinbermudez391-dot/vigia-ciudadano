<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Alta de reportes con evidencia fotográfica: generación de ticket,
 * almacenamiento defensivo de la foto y persistencia atómica.
 *
 * Sede única de esta lógica para que el portal web (SSR) y la API no
 * bifurquen reglas (retry del correlativo, compensación del archivo si
 * la BD falla, carpeta y nombre en disco derivados del ticket).
 *
 * Soporta flujo público: el autor puede ser null (ciudadano sin cuenta).
 * En ese caso, se registran los datos de contacto opcionales (citizen_name,
 * citizen_phone, citizen_email) si fueron proporcionados.
 */
final class ReportIntake
{
    private const PHOTO_FOLDER = 'reports/damages';

    public function __construct(
        private readonly TicketCodeGenerator $tickets,
        private readonly ImageUploader $uploader,
    ) {}

    /**
     * @param  array{category_id: int|string, title: string, description: string, latitude: string|float, longitude: string|float, address_reference?: ?string, citizen_name?: ?string, citizen_phone?: ?string, citizen_email?: ?string}  $data
     *
     * @throws UniqueConstraintViolationException tras agotar los reintentos
     */
    public function create(array $data, UploadedFile $photo, ?User $author): Report
    {
        // Retry acotado: bajo concurrencia extrema dos tickets pueden chocar
        // contra el UNIQUE de ticket_code; reintenta con el siguiente correlativo.
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->persist($data, $photo, $author);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Foto + inserción en una transacción con compensación del archivo:
     * el filesystem no participa del rollback.
     */
    private function persist(array $data, UploadedFile $photo, ?User $author): Report
    {
        return DB::transaction(function () use ($data, $photo, $author): Report {
            $ticketCode = $this->tickets->next();

            // Nombre en disco derivado del ticket (único garantizado),
            // nunca del nombre enviado por el cliente.
            $photoPath = $this->uploader->upload($photo, self::PHOTO_FOLDER, $ticketCode);

            try {
                return Report::create([
                    'ticket_code' => $ticketCode,
                    'user_id' => $author?->id,
                    'citizen_name' => $data['citizen_name'] ?? null,
                    'citizen_phone' => $data['citizen_phone'] ?? null,
                    'citizen_email' => $data['citizen_email'] ?? null,
                    'category_id' => (int) $data['category_id'],
                    'title' => (string) $data['title'],
                    'description' => (string) $data['description'],
                    'damage_photo_url' => $photoPath,
                    'latitude' => (float) $data['latitude'],
                    'longitude' => (float) $data['longitude'],
                    'address_reference' => (string) ($data['address_reference'] ?? ''),
                    'status' => ReportStatus::Reported->value,
                ]);
            } catch (Throwable $e) {
                $this->uploader->delete($photoPath);

                throw $e;
            }
        });
    }
}
