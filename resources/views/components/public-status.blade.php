@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : $status;
    [$label, $description, $tone] = match ($value) {
        'reported' => ['Recibido', 'Tu reporte está pendiente de asignación.', 'blue'],
        'in_progress' => ['En reparación', 'El trabajo está asignado o en marcha.', 'blue'],
        'under_inspection' => ['En revisión', 'Estamos verificando la reparación.', 'amber'],
        'rework' => ['En reparación', 'Se solicitaron correcciones al trabajo.', 'amber'],
        'closed' => ['Solucionado', 'La reparación fue revisada y aprobada.', 'green'],
        default => ['En seguimiento', 'Consulta aquí los avances de tu reporte.', 'blue'],
    };
@endphp
<div {{ $attributes->class(['citizen-status', 'citizen-status-'.$tone]) }}>
    <strong>{{ $label }}</strong><p>{{ $description }}</p>
</div>
