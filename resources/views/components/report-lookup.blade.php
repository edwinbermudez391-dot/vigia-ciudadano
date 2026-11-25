@props(['id' => 'consultar', 'action' => route('home')])
@php
    $enteredCode = is_string(request('radicado')) ? mb_strtoupper(trim(request('radicado'))) : '';
    $validCode = preg_match('/^REP-\d{4}-\d{4,5}$/', $enteredCode) === 1;
@endphp
<section id="{{ $id }}" class="citizen-card citizen-lookup" aria-labelledby="{{ $id }}-title">
    <h2 id="{{ $id }}-title" tabindex="-1">Consulta tu reporte</h2>
    <p id="{{ $id }}-help">Escribe el número que recibiste al reportar.</p>
    <form method="GET" action="{{ $action }}#{{ $id }}" data-report-lookup data-detail-url="{{ route('reports.show', '__CODE__') }}">
        <label for="{{ $id }}-code">Número de reporte (radicado)</label>
        <div class="citizen-lookup-controls">
            <input id="{{ $id }}-code" name="radicado" type="text" value="{{ $enteredCode }}" required maxlength="20"
                   autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="REP-2026-0148"
                   aria-describedby="{{ $id }}-help {{ $id }}-example {{ $id }}-error" aria-invalid="false">
            <button type="submit" class="btn-primary"><x-icon name="search" />Buscar reporte</button>
        </div>
        <p id="{{ $id }}-example" class="citizen-help">Ejemplo de formato: REP-2026-0148. Usa tu propio número.</p>
        <p id="{{ $id }}-error" class="citizen-error" role="alert" data-lookup-error hidden></p>
        <p class="citizen-help" role="status" data-lookup-status></p>
    </form>
    <noscript>
        @if ($validCode)
            <p><a class="btn-primary" href="{{ route('reports.show', $enteredCode) }}">Abrir mi reporte</a></p>
        @elseif ($enteredCode !== '')
            <p class="citizen-error">Revisa el número. Debe tener un formato como REP-2026-0148.</p>
        @endif
        <p class="citizen-help">Escribe el número y pulsa «Buscar reporte»; después, «Abrir mi reporte».</p>
    </noscript>
</section>
