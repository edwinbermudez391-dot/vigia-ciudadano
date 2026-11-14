@props(['status'])

@php
    // Etiqueta + paleta únicas (App\Support\StatusUi): API, panel y trazabilidad
    // nunca muestran colores divergentes para el mismo estado.
    $ui = \App\Support\StatusUi::badge($status);
@endphp

<span {{ $attributes->merge(['class' => 'status-badge inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-base font-semibold '.$ui['classes']]) }}>
    {{ $ui['label'] }}
</span>
