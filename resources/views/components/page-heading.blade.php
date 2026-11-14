@props(['title', 'description' => null])
<header {{ $attributes->class(['page-heading']) }}>
    <div>
        <h1>{{ $title }}</h1>
        @if ($description)<p class="page-description">{{ $description }}</p>@endif
    </div>
    @if ($slot->isNotEmpty())<div class="page-actions">{{ $slot }}</div>@endif
</header>
