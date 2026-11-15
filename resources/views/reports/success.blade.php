@extends('layouts.app')
@section('title', 'Tu reporte fue recibido — Vigía Ciudadano')
@section('content')
<div class="citizen-content citizen-receipt">
    @if (session('device_report_ticket') === $report->ticket_code)
        <span hidden data-device-receipt data-code="{{ $report->ticket_code }}" data-created-at="{{ $report->created_at->toIso8601String() }}"></span>
        <p class="citizen-help citizen-no-print" role="status" data-device-save-status></p>
    @endif
    <section class="citizen-card" aria-labelledby="receipt-title">
        <span class="success-icon"><x-icon name="check" /></span>
        <h1 id="receipt-title">¡Tu reporte fue recibido!</h1>
        <div class="citizen-actions citizen-no-print">
            <a href="{{ route('reports.show', $report->ticket_code) }}" class="btn-secondary"><x-icon name="search" />Consultar mi reporte</a>
            <a href="{{ route('home') }}" class="btn-primary">Volver al inicio</a>
            <a href="{{ route('reports.create') }}" class="btn-secondary">Reportar otro problema</a>
            <a href="{{ route('reports.device') }}" class="btn-secondary">Mis reportes en este dispositivo</a>
        </div>
        <p id="copy-feedback" class="citizen-help" role="status"></p>
    </section>
</div>
@endsection
