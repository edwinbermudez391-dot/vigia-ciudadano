@props(['label', 'url' => null])

<article class="overflow-hidden rounded-2xl border border-[#d5e8e0] bg-white/95 text-[#062e24] shadow-[0_8px_30px_rgba(6,46,36,0.04)] transition-all duration-200 hover:scale-[1.01] hover:shadow-md">
    <div class="relative">
        @if ($url)
            {{-- onerror: si el archivo falta en disco (storage:link roto o foto
                 borrada) se muestra el estado "Pendiente" en vez del icono roto. --}}
            <img src="{{ $url }}" alt="{{ $label }}" loading="lazy"
                 onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.classList.replace('hidden','grid');"
                 class="aspect-video w-full object-cover">
            <div class="hidden aspect-video place-items-center border-b border-dashed border-[#d5e8e0] bg-[#f0faf5] text-[#5d7d70]">
                <div class="text-center">
                    <span class="block text-2xl">🖼️</span>
                    <span class="mt-1 block text-xs font-medium">Foto no disponible</span>
                </div>
            </div>
        @else
            {{-- Estados futuros del flujo: la foto aún no existe --}}
            <div class="grid aspect-video place-items-center border-b border-dashed border-[#d5e8e0] bg-[#f0faf5] text-[#5d7d70]">
                <div class="text-center">
                    <span class="block text-2xl">🕓</span>
                    <span class="mt-1 block text-xs font-medium">Pendiente</span>
                </div>
            </div>
        @endif

        <span {{ $attributes->merge(['class' => 'absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-[#062e24] shadow-sm backdrop-blur']) }}>
            {{ $label }}
        </span>
    </div>

    <div class="px-4 py-3 text-xs leading-relaxed text-[#1f5142]">
        {{ $slot }}
    </div>
</article>
