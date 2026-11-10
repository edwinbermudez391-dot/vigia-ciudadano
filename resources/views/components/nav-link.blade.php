@props(['href' => '#', 'active' => false])

<a href="{{ $href }}" @class([
    'whitespace-nowrap rounded-xl px-3 py-1.5 text-sm font-medium text-text-primary transition duration-200 hover:text-primary',
    'bg-primary/10 text-primary font-semibold' => $active,
])>{{ $slot }}</a>
