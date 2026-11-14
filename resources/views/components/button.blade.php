@props(['href' => null, 'variant' => 'primary', 'type' => 'button'])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['btn', 'btn-'.$variant]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['btn', 'btn-'.$variant]) }}>{{ $slot }}</button>
@endif
