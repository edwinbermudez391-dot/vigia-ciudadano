@props(['href' => '#', 'active' => false])

<a href="{{ $href }}" @class([
    'whitespace-nowrap rounded-xl px-3 py-1.5 text-sm font-medium text-[#062e24] transition duration-200 hover:text-[#087b5f]',
    'bg-[#e8f8f0] text-[#087b5f] font-semibold' => $active,
])>{{ $slot }}</a>
