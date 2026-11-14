@props(['name' => 'report'])
<svg {{ $attributes->class(['ui-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('camera') <path d="M4 7h4l2-3h4l2 3h4v13H4Z"/><circle cx="12" cy="13" r="4"/> @break
        @case('copy') <rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V3H3v13h5"/> @break
        @case('printer') <path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6Z"/> @break
        @case('road') <path d="m7 3-4 18M17 3l4 18M12 3v4m0 3v4m0 3v4"/> @break
        @case('light') <path d="M9 18h6m-6 3h6M8 13a6 6 0 1 1 8 0c-1 1-1 2-1 3H9c0-1 0-2-1-3Z"/> @break
        @case('water') <path d="M12 3s-7 8-7 12a7 7 0 0 0 14 0c0-4-7-12-7-12Z"/> @break
        @case('trash') <path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/> @break
        @case('tree') <path d="m12 3-6 7h3l-5 7h6v4h4v-4h6l-5-7h3Z"/> @break
        @case('home') <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/> @break
        @case('users') <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/> @break
        @case('chart') <path d="M4 3v18h17M9 16v-5m5 5V6m5 10V9"/> @break
        @case('map') <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Zm6-3v15m6-12v15"/> @break
        @case('work') <path d="M9 6V4h6v2M3 11l9 4 9-4M12 12v5"/><rect x="3" y="6" width="18" height="15" rx="2"/> @break
        @case('check') <path d="m8 12 3 3 5-6M9 3H5a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1h-4"/><rect x="9" y="2" width="6" height="4" rx="1"/> @break
        @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('collapse') <path d="M9 3v18m8-14-5 5 5 5"/><rect x="3" y="3" width="18" height="18" rx="2"/> @break
        @case('arrow') <path d="M5 12h14m-6-6 6 6-6 6"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('shield') <path d="m12 3 8 3v5c0 5-4 8-8 10-4-2-8-5-8-10V6Zm-4 9 3 3 5-6"/> @break
        @case('logout') <path d="M9 4H4v16h5m6-12 4 4-4 4m-8-4h12"/> @break
        @case('search') <circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/> @break
        @default <path d="M8 3H5v18h14V8l-5-5H8Zm6 0v6h5M8 13h7m-7 4h5"/>
    @endswitch
</svg>
