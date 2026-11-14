@php
    // Presentation-only public timeline. Never render controller timeline details:
    // those contain citizen/staff names and internal inspection notes.
    $publicSteps = [['Recibido', $report->created_at, 'Guardamos tu reporte.']];
    if ($workOrder) {
        $publicSteps[] = ['En reparación', $workOrder->created_at, 'El trabajo fue asignado para su atención.'];
        if ($workOrder->completion_date) {
            $publicSteps[] = ['En revisión', $workOrder->completion_date, 'La reparación pasó a revisión de calidad.'];
        }
        foreach ($workOrder->qualityInspections->sortBy('inspected_at') as $inspection) {
            $approved = $inspection->decision === \App\Enums\InspectionDecision::Approved;
            $publicSteps[] = [$approved ? 'Revisión aprobada' : 'Correcciones solicitadas', $inspection->inspected_at,
                $approved ? 'La reparación superó esta revisión.' : 'El equipo debe corregir el trabajo.'];
        }
    } else {
        $publicSteps[] = ['Pendiente de asignación', null, 'El equipo responsable organizará la atención.'];
    }
@endphp
<div class="citizen-content">
    <header class="citizen-heading"><h1>Así va tu reporte</h1><p class="citizen-number">{{ $report->ticket_code }}</p></header>
    <div class="citizen-detail-layout">
        <section class="citizen-card" aria-labelledby="public-report-title">
            <x-public-status :status="$report->status" />
            <h2 id="public-report-title">{{ $report->title }}</h2>
            <p>{{ $report->description }}</p>
            <dl class="citizen-summary">
                <div><dt>Tipo de problema</dt><dd><x-citizen-category-label :name="$report->category->name" /></dd></div>
                <div><dt>Fecha del reporte</dt><dd><time datetime="{{ $report->created_at->toIso8601String() }}">{{ $report->created_at->format('d/m/Y H:i') }}</time></dd></div>
                <div><dt>Ubicación</dt><dd>{{ $report->address_reference ?: 'Lugar señalado al enviar el reporte' }}</dd></div>
                <div><dt>Coordenadas</dt><dd>{{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }}</dd></div>
            </dl>
            <a class="btn-secondary" href="https://www.openstreetmap.org/?mlat={{ $report->latitude }}&amp;mlon={{ $report->longitude }}#map=18/{{ $report->latitude }}/{{ $report->longitude }}" target="_blank" rel="noopener noreferrer"><x-icon name="map" />Ver el lugar en OpenStreetMap<span class="sr-only"> (se abre en otra pestaña)</span></a>
        </section>
        <section class="citizen-card" aria-labelledby="public-progress-title">
            <h2 id="public-progress-title">Seguimiento de la solución</h2>
            <ol class="citizen-timeline">
                @foreach ($publicSteps as [$title, $at, $detail])
                    <li><strong>{{ $title }}</strong>@if ($at)<time datetime="{{ $at->toIso8601String() }}">{{ $at->format('d/m/Y H:i') }}</time>@endif<p>{{ $detail }}</p></li>
                @endforeach
            </ol>
        </section>
    </div>
    <section class="citizen-card" aria-labelledby="public-evidence-title">
        <h2 id="public-evidence-title">Fotos del reporte</h2>
        <div class="evidence-grid">
            <x-photo-card label="Problema reportado" :url="$damageUrl" />
            <x-photo-card label="Reparación" :url="$solutionUrl" />
            <x-photo-card label="Revisión de la reparación" :url="$verificationUrl" />
        </div>
    </section>
    <div class="citizen-actions"><a class="btn-secondary" href="{{ route('home') }}#consultar">Consultar otro reporte</a><a class="btn-primary" href="{{ route('reports.create') }}">Reportar otro problema</a></div>
</div>
