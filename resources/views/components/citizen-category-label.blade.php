@props(['name', 'withIcon' => false])
@php
    [$label, $icon] = match ($name) {
        'Baches y Malla Vial', 'Baches' => ['Hueco en la calle', 'road'],
        'Luminaria Apagada o en Falla' => ['Lámpara dañada', 'light'],
        'Fuga de Agua Potable' => ['Fuga de agua', 'water'],
        'Basuras y Escombros en Vía Pública' => ['Basuras o escombros', 'trash'],
        'Alcantarillado o Sumidero Tapado' => ['Alcantarilla tapada', 'water'],
        'Árbol en Riesgo de Caída' => ['Árbol en riesgo de caer', 'tree'],
        'Andenes Deteriorados' => ['Andén dañado', 'road'],
        'Sin Suministro de Agua' => ['Falta de agua', 'water'],
        'Semáforo en Falla' => ['Semáforo dañado', 'light'],
        'Cableado Expuesto' => ['Cables sueltos o expuestos', 'light'],
        'Congestionamiento Vial Anormal' => ['Dificultad para pasar por la vía', 'road'],
        'Contaminación Auditiva' => ['Ruido excesivo', 'report'],
        'Grietas en Fachadas y Muros' => ['Grietas en paredes o muros', 'report'],
        'Señalización Vial Deteriorada' => ['Señal de tránsito dañada', 'road'],
        default => [$name, 'report'],
    };
@endphp
@if ($withIcon)<x-icon :name="$icon" /><strong>{{ $label }}</strong>@else{{ $label }}@endif
