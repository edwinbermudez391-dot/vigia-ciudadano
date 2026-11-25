<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Category;
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
    private const EARTH_RADIUS_METERS = 6371000;

    public const DUPLICATE_RADIUS_METERS = 20;

    public function __construct(
        private readonly TicketCodeGenerator $tickets,
        private readonly ImageUploader $uploader,
    ) {}

    /**
     * @param  array{category_id: int|string, title: string, description: string, latitude: string|float, longitude: string|float, address_reference?: ?string, citizen_name?: ?string, citizen_phone?: ?string, citizen_email?: ?string}  $data
     *
     * @throws UniqueConstraintViolationException tras agotar los reintentos
     */
    public function create(array $data, ?UploadedFile $photo, ?User $author, ?string $deviceTokenHash = null): Report
    {
        // Retry acotado: bajo concurrencia extrema dos tickets pueden chocar
        // contra el UNIQUE de ticket_code; reintenta con el siguiente correlativo.
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->persist($data, $photo, $author, $deviceTokenHash);
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
    private function persist(array $data, ?UploadedFile $photo, ?User $author, ?string $deviceTokenHash): Report
    {
        return DB::transaction(function () use ($data, $photo, $author, $deviceTokenHash): Report {
            $categoryId = (int) $data['category_id'];
            // Serialize submissions in this category before checking and writing.
            Category::query()->lockForUpdate()->findOrFail($categoryId, ['id']);

            $duplicate = $this->findDuplicate($categoryId, (float) $data['latitude'], (float) $data['longitude']);
            if ($duplicate !== null) {
                return $duplicate;
            }

            $ticketCode = $this->tickets->next();

            // Nombre en disco derivado del ticket (único garantizado),
            // nunca del nombre enviado por el cliente.
            $photoPath = $photo ? $this->uploader->upload($photo, self::PHOTO_FOLDER, $ticketCode) : null;

            try {
                return Report::create([
                    'ticket_code' => $ticketCode,
                    'device_token_hash' => $deviceTokenHash,
                    'user_id' => $author?->id,
                    'citizen_name' => $data['citizen_name'] ?? null,
                    'citizen_phone' => $data['citizen_phone'] ?? null,
                    'citizen_email' => $data['citizen_email'] ?? null,
                    'category_id' => $categoryId,
                    'title' => (string) $data['title'],
                    'description' => (string) $data['description'],
                    'damage_photo_url' => $photoPath,
                    'latitude' => (float) $data['latitude'],
                    'longitude' => (float) $data['longitude'],
                    'address_reference' => (string) ($data['address_reference'] ?? ''),
                    'status' => ReportStatus::Reported->value,
                ]);
            } catch (Throwable $e) {
                if ($photoPath !== null) $this->uploader->delete($photoPath);

                throw $e;
            }
        }, 3);
    }

    private function findDuplicate(int $categoryId, float $latitude, float $longitude): ?Report
    {
        $angularRadius = self::DUPLICATE_RADIUS_METERS / self::EARTH_RADIUS_METERS;
        $latitudeMargin = rad2deg($angularRadius);
        $maxHaversine = sin($angularRadius / 2) ** 2;
        $latitudeRadians = deg2rad($latitude);

        return Report::query()
            ->where('category_id', $categoryId)
            ->where('status', '!=', ReportStatus::Closed->value)
            ->whereBetween('latitude', [$latitude - $latitudeMargin, $latitude + $latitudeMargin])
            ->orderBy('id')
            ->cursor()
            ->first(static function (Report $report) use ($latitudeRadians, $longitude, $maxHaversine): bool {
                $reportLatitude = deg2rad($report->latitude);
                $latitudeHalfDelta = ($reportLatitude - $latitudeRadians) / 2;
                $longitudeHalfDelta = deg2rad($report->longitude - $longitude) / 2;
                $haversine = sin($latitudeHalfDelta) ** 2
                    + cos($latitudeRadians) * cos($reportLatitude) * sin($longitudeHalfDelta) ** 2;

                return $haversine <= $maxHaversine;
            });
    }
}
