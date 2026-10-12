<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\CheckNearbyReportsRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\NearbyReportResource;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportIntake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * API REST de reportes ciudadanos (recurso /api/v1/reports).
 *
 * El controlador orquesta; la lógica de dominio vive en Services
 * (ReportIntake —que a su vez compone TicketCodeGenerator e ImageUploader—)
 * y la validación en Form Requests. Cada método hace early-return del caso
 * de error con respuesta JSON estructurada y código HTTP coherente con REST.
 */
final class CitizenReportController extends Controller
{
    /**
     * Límites duros de la consulta geoespacial (cota de recursos).
     */
    private const MAX_NEARBY_RESULTS = 50;

    /**
     * Fórmula Haversine con radio terrestre 6.371 km, evaluada en SQL para
     * no arrastrar filas al PHP. Los ? son binding params (ORM parametrizado,
     * cero concatenación de valores del usuario en el SQL).
     */
    private const HAVERSINE_SQL = '(6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

    public function __construct(
        private readonly ReportIntake $intake,
    ) {}

    /**
     * GET /api/v1/reports/nearby — incidencias activas dentro del radio.
     */
    public function checkNearby(CheckNearbyReportsRequest $request): JsonResponse
    {
        $latitude = (float) $request->validated('latitude');
        $longitude = (float) $request->validated('longitude');
        $categoryId = (int) $request->validated('category_id');
        $radius = (float) $request->validated('radius_meters');

        $reports = Report::query()
            // Filtro por categoría y estado en WHERE (usa el índice compuesto
            // (status, category_id)... pero el optimizer elige lat/long con el
            // índice (latitude, longitude); ambos están cubiertos).
            ->where('category_id', $categoryId)
            ->where('status', '!=', ReportStatus::Closed->value)
            ->select([
                'id',
                'ticket_code',
                'title',
                'status',
                'latitude',
                'longitude',
                'address_reference',
                'damage_photo_url',
                'created_at',
            ])
            ->selectRaw(self::HAVERSINE_SQL.' AS distance_meters', [$latitude, $longitude, $latitude])
            // El resultado de una función no puede filtrarse en WHERE; HAVING
            // sobre el alias es el lugar correcto (MySQL/MariaDB).
            ->havingRaw('distance_meters <= ?', [$radius])
            ->orderBy('distance_meters')
            ->limit(self::MAX_NEARBY_RESULTS)
            ->get();

        return response()->json([
            // Sobre individual: ResourceCollection::resolve() no garantiza el
            // envoltorio 'data'; mapear cada resource a su toArray es determinista.
            'data' => $reports
                ->map(fn (Report $report): array => (new NearbyReportResource($report))->toArray($request))
                ->all(),
            'meta' => [
                'category_id' => $categoryId,
                'radius_meters' => $radius,
                'count' => $reports->count(),
            ],
        ], 200);
    }

    /**
     * POST /api/v1/reports — alta de reporte con evidencia fotográfica.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        /** @var UploadedFile $photo */
        $photo = $request->validated('damage_photo');

        // Retry del correlativo, transacción y compensación de la foto viven
        // en ReportIntake (compartidos con el portal web).
        $report = $this->intake->create($request->validated(), $photo, $request->user());

        // Hidrata category para responder 201 con el detalle completo.
        $report->load('category');

        // Respuesta 201 a traves del pipeline estandar del resource
        // (toResponse garantiza el envoltorio 'data' identico al de show()).
        $response = (new ReportResource($report))->toResponse($request);
        $response->setStatusCode(201);

        return $response;
    }

    /**
     * GET /api/v1/reports/{ticket_code} — detalle con relaciones eager-loaded.
     */
    public function show(string $ticket_code): ReportResource|JsonResponse
    {
        $report = Report::query()
            ->with(['category', 'subscribers', 'workOrder.qualityInspections'])
            ->where('ticket_code', $ticket_code)
            ->first();

        if ($report === null) {
            return response()->json([
                'message' => 'No existe un reporte con el código de ticket consultado.',
            ], 404);
        }

        return new ReportResource($report);
    }

    /**
     * GET /api/v1/reports/map-data — dataset público para el mapa interactivo.
     *
     * Endpoint abierto (sin auth) que devuelve los datos mínimos necesarios
     * para renderizar marcadores en el mapa: id, ticket_code, coordenadas,
     * estado, categoría y foto. Solo reportes activos (no cerrados) para
     * mantener la vista limpia; los cerrados se consultan por ticket.
     */
    public function mapData(): \Illuminate\Http\JsonResponse
    {
        $reports = \App\Models\Report::query()
            ->where('status', '!=', \App\Enums\ReportStatus::Closed->value)
            ->with('category')
            ->select([
                'id',
                'ticket_code',
                'title',
                'status',
                'latitude',
                'longitude',
                'damage_photo_url',
                'address_reference',
                'category_id',
                'created_at',
            ])
            ->get();

        $photo = fn (?string $path): ?string => $path === null
            ? null
            : \Illuminate\Support\Facades\Storage::disk('public')->url($path);

        $statusColors = [
            'reported' => '#eab308',       // Amarillo — Pendiente
            'in_progress' => '#3b82f6',     // Azul — En progreso
            'under_inspection' => '#8b5cf6', // Violeta — Bajo inspección
            'rework' => '#ef4444',          // Rojo — Retrabajo
            'closed' => '#22c55e',          // Verde — Cerrado
        ];

        $data = $reports->map(fn (\App\Models\Report $report) => [
            'id' => $report->id,
            'ticket_code' => $report->ticket_code,
            'title' => $report->title,
            'latitude' => (float) $report->latitude,
            'longitude' => (float) $report->longitude,
            'status' => $report->status->value,
            'status_label' => match ($report->status->value) {
                'reported' => 'Reportado',
                'in_progress' => 'En progreso',
                'under_inspection' => 'Bajo inspección',
                'rework' => 'Retrabajo',
                'closed' => 'Cerrado',
                default => $report->status->value,
            },
            'category_name' => $report->category?->name ?? 'Sin categoría',
            'photo_url' => $photo($report->damage_photo_url),
            'address_reference' => $report->address_reference,
            'marker_color' => $statusColors[$report->status->value] ?? '#94a3b8',
            'created_at' => $report->created_at->format('d/m/Y'),
            'detail_url' => route('reports.show', ['ticket_code' => $report->ticket_code]),
        ]);

        return response()->json([
            'data' => $data->values()->all(),
            'meta' => [
                'total' => $data->count(),
                'center' => ['lat' => 3.0095, 'lng' => -76.4849, 'zoom' => 14],
            ],
        ]);
    }

    /**
     * POST /api/v1/reports/{id}/subscribe — seguimiento de novedades.
     */
    public function subscribe(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Debes autenticarte para suscribirte a un reporte.',
            ], 401);
        }

        $report = Report::find($id);

        if ($report === null) {
            return response()->json([
                'message' => 'El reporte al que intentas suscribirte no existe.',
            ], 404);
        }

        // syncWithoutDetaching es idempotente: reintentos del cliente no
        // duplican el registro (y el UNIQUE (report_id, user_id) es la red final).
        $report->subscribers()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => 'Te has suscrito correctamente al reporte.',
            'data' => [
                'report_id' => $report->id,
                'subscribers_count' => $report->subscribers()->count(),
            ],
        ]);
    }
}
