@extends('layouts.app')
@section('title', 'Mis reportes en este dispositivo — Vigía Ciudadano')
@section('content')
<div class="citizen-content citizen-receipt">
    <section class="citizen-card" aria-labelledby="device-reports-title">
        <h1 id="device-reports-title">Mis reportes en este dispositivo</h1>
        <p>Consulta los reportes que enviaste desde este navegador, sin iniciar sesión.</p>
        <p class="citizen-help">La lista permanece al cerrar el navegador. Si usas otro navegador, navegas en privado o borras los datos del sitio, consulta tu reporte con su número.</p>
        <div data-device-reports data-detail-url="{{ route('reports.show', '__CODE__') }}">
            <p role="status" data-device-list-status @if ($reports->isNotEmpty()) hidden @endif>Todavía no hay reportes guardados en este navegador. Los próximos que envíes aparecerán aquí.</p>
            <ul class="citizen-device-list" data-device-list @if ($reports->isEmpty()) hidden @endif>
                @foreach ($reports as $report)
                    <li data-code="{{ $report->ticket_code }}" data-created-at="{{ $report->created_at->toIso8601String() }}">
                        <strong>{{ $report->ticket_code }}</strong>
                        <p class="citizen-help">Enviado el {{ $report->created_at->format('j/n/Y') }}</p>
                        <a href="{{ route('reports.show', $report->ticket_code) }}" class="btn-secondary" aria-label="Consultar reporte {{ $report->ticket_code }}">Consultar reporte</a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="citizen-actions">
            <a href="{{ route('reports.create') }}" class="btn-primary">Reportar un problema</a>
            <a href="{{ route('home') }}" class="btn-secondary">Volver al inicio</a>
        </div>
    </section>
    <x-report-lookup :action="route('reports.device')" />
</div>
@endsection
