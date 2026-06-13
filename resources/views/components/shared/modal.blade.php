@props ([
    'id',
    'title' => null,
    'size' => 'md',
    'scrollable' => true,
    'static' => false,
])

@php
    $sizeMap = [
        'sm' => 'sm:max-w-xs',
        'md' => 'sm:max-w-lg',
        'lg' => 'lg:max-w-3xl',
        'xl' => 'xl:max-w-5xl',
        'full' => 'w-[95vw] max-w-none',
    ];

    $resolvedSize = $sizeMap[$size] ?? $sizeMap['md'];

    $overlayClasses = collect([
        'hs-overlay hs-overlay-open:opacity-100 hs-overlay-open:duration-500 pointer-events-none fixed start-0 top-0 z-80 hidden size-full overflow-x-hidden overflow-y-auto opacity-0 transition-all',
        $static ? '[--overlay-backdrop:static]' : null,
    ])->filter()->implode(' ');

    $hasBodyContent = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();
@endphp

<div
    {{-- Atributos extras (ex.: wire:ignore.self em modais dentro de Livewire,
         para o morph não apagar as classes runtime do HSOverlay). --}}
    {{ $attributes->except('class') }}
    id="{{ $id }}"
    class="{{ $overlayClasses }}"
    role="dialog"
    tabindex="-1"
    aria-labelledby="{{ $title ? "{$id}-label" : $id }}"
>
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 m-3 flex min-h-[calc(100%-3.5rem)] scale-95 items-center opacity-0 transition-all duration-200 ease-out sm:mx-auto sm:w-full {{ $resolvedSize }}"
    >
        <div class="border-default-300 bg-card pointer-events-auto flex w-full flex-col rounded-xl border shadow-lg">
            @if ($title)
                <div class="border-default-300 flex items-center justify-between gap-3 border-b px-6 py-5">
                    <div class="min-w-0">
                        <h3 id="{{ $id }}-label" class="text-body-color truncate text-lg font-semibold">
                            {{ $title }}
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="text-default-400 hover:bg-light hover:text-body-color inline-flex size-9 items-center justify-center rounded-full transition"
                        aria-label="Fechar"
                        data-hs-overlay="#{{ $id }}"
                    >
                        <i class="iconify tabler--x text-xl"></i>
                    </button>
                </div>
            @endif

            <div @class (['px-6 py-5', 'max-h-[70vh] overflow-y-auto' => $scrollable])>
                @if ($hasBodyContent)
                    {{ $slot }}
                @endif
            </div>

            @isset ($footer)
                <div class="border-default-300 flex flex-wrap items-center justify-end gap-2 border-t px-6 py-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
