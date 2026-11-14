@extends('layouts.app')
@section('title', 'Tu reporte fue recibido — Vigía Ciudadano')
@section('content')
<div class="citizen-content citizen-receipt">
    <section class="citizen-card" aria-labelledby="receipt-title">
        <span class="success-icon"><x-icon name="check" /></span>
        <h1 id="receipt-title">¡Tu reporte fue recibido!</h1>
        <p>Guarda este número para consultar cómo va la solución.</p>
        <div class="citizen-ticket"><p>Número de reporte (radicado)</p><p id="ticket-code" class="citizen-number" tabindex="0">{{ $report->ticket_code }}</p></div>
        <div class="citizen-actions citizen-no-print">
            <button type="button" id="copy-ticket" class="btn-secondary" data-copy-ticket hidden><x-icon name="copy" />Copiar número</button>
            <a href="{{ route('reports.show', $report->ticket_code) }}" class="btn-primary"><x-icon name="search" />Consultar mi reporte</a>
            <button type="button" class="btn-secondary" data-print-receipt hidden><x-icon name="printer" />Imprimir comprobante</button>
        </div>
        <p id="copy-feedback" class="citizen-help" role="status"></p>
        <noscript><p>Puedes seleccionar el número y copiarlo. Para imprimir, usa la opción «Imprimir» de tu navegador.</p></noscript>
    </section>
    <section class="citizen-card" aria-labelledby="receipt-summary-title">
        <h2 id="receipt-summary-title">Tu comprobante</h2>
        <dl class="citizen-summary">
            <div><dt>Problema</dt><dd>{{ $report->title }}</dd></div>
            <div><dt>Tipo de problema</dt><dd><x-citizen-category-label :name="$report->category->name" /></dd></div>
            <div><dt>Fecha del reporte</dt><dd>{{ $report->created_at->format('d/m/Y H:i') }}</dd></div>
            <div><dt>Ubicación</dt><dd>{{ $report->address_reference ?: 'Lugar señalado al enviar el reporte' }}</dd></div>
            <div><dt>Coordenadas</dt><dd>{{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }}</dd></div>
        </dl>
        <p>El equipo responsable revisará el problema. Puedes volver a consultar con tu número, sin crear una cuenta.</p>
    </section>
    <div class="citizen-actions citizen-no-print"><a href="{{ route('home') }}" class="btn-secondary">Volver al inicio</a><a href="{{ route('reports.create') }}" class="btn-secondary">Reportar otro problema</a></div>
</div>
@endsection
