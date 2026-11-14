@props(['label', 'value', 'description' => null, 'tone' => 'blue'])
<article class="stat-card stat-{{ $tone }}">
    <h2>{{ $label }}</h2>
    <p class="stat-value">{{ number_format($value) }}</p>
    @if ($description)<p class="stat-description">{{ $description }}</p>@endif
</article>
