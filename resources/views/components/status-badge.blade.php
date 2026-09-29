@props(['status'])

@php
    // Etiqueta + paleta únicas (App\Support\StatusUi): API, panel y trazabilidad
    // nunca muestran colores divergentes para el mismo estado.
    $ui = \App\Support\StatusUi::badge($status);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-medium '.$ui['classes']]) }}>
    {{ $ui['label'] }}
</span>
