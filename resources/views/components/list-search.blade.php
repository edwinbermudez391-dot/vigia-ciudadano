@props(['target', 'label' => 'Buscar en esta página', 'placeholder' => 'Escribe un nombre o número de radicado'])
<div class="list-search panel p-6" data-list-search="{{ $target }}">
    <label class="field-label" for="search-{{ $target }}">{{ $label }}</label>
    <div class="list-search-controls"><input id="search-{{ $target }}" class="field-control" type="search" placeholder="{{ $placeholder }}" aria-controls="{{ $target }}" aria-describedby="search-{{ $target }}-help"><button type="button" class="btn-secondary" data-search-reset>Limpiar</button></div>
    <p id="search-{{ $target }}-help" class="field-help">Filtra únicamente los elementos de esta página. <span role="status" data-search-count></span></p>
</div>
