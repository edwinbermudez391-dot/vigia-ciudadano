@props(['label', 'url' => null])
<figure {{ $attributes->class(['evidence-card']) }}>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $label }}" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
        <div hidden class="photo-empty">Foto no disponible</div>
    @else
        <div class="photo-empty"><span><x-icon name="clock" /><span class="block mt-2">Evidencia pendiente</span></span></div>
    @endif
    <figcaption class="evidence-caption"><strong>{{ $label }}</strong><div class="report-meta">{{ $slot }}</div></figcaption>
</figure>
