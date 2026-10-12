<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva orden de trabajo asignada — {{ $ticketCode }}</title>
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
        .notification-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #1e40af;
            margin: 0 0 8px 0;
        }
        .notification-subtitle {
            text-align: center;
            font-size: 16px;
            color: #64748b;
            margin: 0 0 32px 0;
        }
        .ticket-code {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 32px 0;
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
            padding: 24px;
            margin: 24px 0;
        }
        .detail-row {
            margin: 12px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .detail-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .detail-value {
            display: block;
            font-size: 16px;
            color: #1e293b;
            font-weight: 500;
        }
        .highlight-box {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 24px 0;
            text-align: center;
        }
        .highlight-box p {
            margin: 0;
            color: #1e40af;
        }
        .highlight-box strong {
            display: block;
            font-size: 18px;
            margin-bottom: 4px;
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
            {{-- Título de la notificación --}}
            <p class="notification-title">Nueva Orden de Trabajo Asignada</p>
            <p class="notification-subtitle">Se te ha asignado una nueva incidencia para reparación</p>

            {{-- Ticket code --}}
            <p class="ticket-code">{{ $ticketCode }}</p>

            {{-- Foto del daño reportado --}}
            @if($photoUrl)
                <div class="photo-container">
                    <img src="{{ $photoUrl }}" alt="Evidencia fotográfica del reporte {{ $ticketCode }}" width="500">
                </div>
            @endif

            {{-- Detalles de la orden --}}
            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Título del reporte</span>
                    <span class="detail-value">{{ $report->title }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Categoría</span>
                    <span class="detail-value">{{ $categoryName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dirección / Referencia</span>
                    <span class="detail-value">{{ $address }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Coordenadas GPS</span>
                    <span class="detail-value">{{ $report->latitude }}, {{ $report->longitude }}</span>
                </div>
            </div>

            {{-- Fecha programada destacada --}}
            <div class="highlight-box">
                <strong>Fecha programada</strong>
                <p>{{ $scheduledDate }}</p>
            </div>

            {{-- Descripción del daño --}}
            @if($report->description)
                <div class="details-box">
                    <span class="detail-label">Descripción del daño reportado</span>
                    <span class="detail-value" style="margin-top: 8px;">{{ $report->description }}</span>
                </div>
            @endif

            {{-- Botón de acción --}}
            <div class="cta-container">
                <a href="{{ $url }}" class="cta-button">Ver detalles de la orden</a>
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
