@props(['groups', 'statusEnum', 'openStatus', 'panelPrefix', 'label'])

@if ($groups)
    <div class="hidden" data-status-switcher role="group" aria-label="{{ $label }}">
        <div class="flex gap-2 overflow-x-auto p-1">
            @foreach ($groups as $state => $group)
                @php($badge = \App\Support\StatusUi::badge($statusEnum::from($state)))
                <button type="button" aria-controls="{{ $panelPrefix }}-{{ $state }}" aria-expanded="{{ $openStatus === $state ? 'true' : 'false' }}"
                        class="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg border px-3 py-2 text-sm font-semibold hover:brightness-95 focus-visible:ring-2 focus-visible:ring-blue-600 aria-expanded:ring-2 aria-expanded:ring-current {{ $badge['classes'] }}">
                    {{ $badge['label'] }}
                    <span class="rounded-full bg-white/80 px-1.5 text-center tabular-nums">{{ $group->total() }}</span>
                </button>
            @endforeach
        </div>
    </div>
@endif
