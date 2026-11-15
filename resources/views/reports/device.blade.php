@extends('layouts.app')
@section('title', 'Mis reportes en este dispositivo — Vigía Ciudadano')
@section('content')
<div class="citizen-content citizen-receipt">
    <section class="citizen-card" aria-labelledby="device-reports-title">
        <h1 id="device-reports-title">Mis reportes en este dispositivo</h1>
        <p>Consulta los reportes que enviaste desde este navegador, sin iniciar sesión.</p>
        <p class="citizen-help">Si borras los datos del navegador o cambias de dirección web, la lista no aparecerá aquí. Guarda también tus números de reporte.</p>
        <div data-device-reports data-detail-url="{{ route('reports.show', '__CODE__') }}">
            <p role="status" data-device-list-status>Cargando tus reportes…</p>
            <ul class="citizen-device-list" data-device-list hidden></ul>
        </div>
        <noscript><p>Activa JavaScript para ver los reportes guardados en este navegador.</p></noscript>
        <div class="citizen-actions">
            <a href="{{ route('reports.create') }}" class="btn-primary">Reportar un problema</a>
            <a href="{{ route('home') }}" class="btn-secondary">Volver al inicio</a>
        </div>
    </section>
</div>
@endsection
