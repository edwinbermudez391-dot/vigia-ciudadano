@props(['href' => '#', 'active' => false, 'icon' => null])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   {{ $attributes->class(['nav-link', 'is-active' => $active]) }} @if ($icon) title="{{ trim(strip_tags((string) $slot)) }}" @endif>
    @if ($icon)<x-icon :name="$icon" />@endif
    <span class="nav-caption">{{ $slot }}</span>
</a>
