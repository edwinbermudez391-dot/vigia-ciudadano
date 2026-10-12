<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización del reporte {{ $ticketCode }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background-color: #1e40af;
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 32px 24px;
        }
        .status-badge-container {
            text-align: center;
            margin-bottom: 24px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .ticket-code {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 24px 0;
            font-family: 'Courier New', Courier, monospace;
        }
        .photo-container {
            text-align: center;
            margin: 24px 0;
        }
        .photo-container img {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .details-box p {
            margin: 8px 0;
        }
        .details-box strong {
            color: #475569;
            display: inline-block;
            min-width: 120px;
        }
        .observations-box {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            border-radius: 4px;
            padding: 16px 20px;
            margin: 24px 0;
        }
        .observations-box p {
            margin: 0;
        }
        .observations-box strong {
            color: #9a3412;
            display: block;
            margin-bottom: 8px;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #1e40af;
            color: #ffffff;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }
        .cta-button:hover {
            background-color: #1e3a8a;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }
        .footer p {
            margin: 4px 0;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }
            .content {
                padding: 24px 16px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header institucional --}}
        <div class="header">
            <h1>Vigía Ciudadano</h1>
        </div>

        <div class="content">
            {{-- Badge de estado --}}
            <div class="status-badge-container">
                <span class="status-badge" style="background-color: {{ $statusBgColor }}; color: {{ $statusTextColor }};">
                    {{ $statusLabel }}
                </span>
            </div>

            {{-- Ticket code --}}
            <p class="ticket-code">{{ $ticketCode }}</p>

            {{-- Foto del daño (si existe) --}}
            @if($photoUrl)
                <div class="photo-container">
                    <img src="{{ $photoUrl }}" alt="Evidencia fotográfica del reporte {{ $ticketCode }}" width="500">
                </div>
            @endif

            {{-- Detalles del reporte --}}
            <div class="details-box">
                <p><strong>Título:</strong> {{ $report->title }}</p>
                <p><strong>Categoría:</strong> {{ $report->category?->name ?? 'Sin categoría' }}</p>
                <p><strong>Ubicación:</strong> {{ $report->address_reference ?: 'Sin referencia' }}</p>
            </div>

            {{-- Observaciones contextuales --}}
            @if($observations)
                <div class="observations-box">
                    <strong>Observaciones:</strong>
                    <p>{{ $observations }}</p>
                </div>
            @endif

            {{-- Botón de acción --}}
            <div class="cta-container">
                <a href="{{ $url }}" class="cta-button">Consultar avance del reporte</a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Este es un correo automático de Vigía Ciudadano.</p>
            <p>Por favor, no responda a este mensaje.</p>
        </div>
    </div>
</body>
</html>
