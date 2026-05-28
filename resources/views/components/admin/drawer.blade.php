@props ([
    'id',
    'title' => null,
    'position' => 'end',
    'size' => 'md',
    'bodyScroll' => false,
    'backdrop' => true,
])

@php
    $position = in_array($position, ['start', 'end', 'top', 'bottom'], true) ? $position : 'end';
    $size = in_array($size, ['sm', 'md', 'lg', 'xl', 'full'], true) ? $size : 'md';

    $widthSizes = [
        'sm' => 'max-w-xs',
        'md' => 'max-w-sm',
        'lg' => 'max-w-md',
        'xl' => 'max-w-lg',
        'full' => 'max-w-full',
    ];

    $heightSizes = [
        'sm' => 'max-h-60',
        'md' => 'max-h-80',
        'lg' => 'max-h-[32rem]',
        'xl' => 'max-h-[40rem]',
        'full' => 'max-h-screen',
    ];

    $positionClasses = match ($position) {
        'start' => 'start-0 top-0 h-full w-full -translate-x-full border-e',
        'top' => 'inset-x-0 top-0 size-full -translate-y-full border-b',
        'bottom' => 'inset-x-0 bottom-0 size-full translate-y-full border-t',
        default => 'end-0 top-0 h-full w-full translate-x-full border-s',
    };

    $sizeClass = in_array($position, ['top', 'bottom'], true)
        ? $heightSizes[$size]
        : $widthSizes[$size];

    $dialogAttributes = $attributes
        ->class(collect([
            'hs-overlay',
            'hs-overlay-open:translate-x-0',
            'hs-overlay-open:translate-y-0',
            'bg-card border-default-300 fixed z-80 hidden transform transition-all duration-300',
            $positionClasses,
            $sizeClass,
            $bodyScroll ? '[--body-scroll:true]' : null,
            ! $backdrop ? '[--overlay-backdrop:false]' : null,
        ])->filter()->all())
        ->merge([
            'id' => $id,
            'role' => 'dialog',
            'tabindex' => '-1',
            'aria-modal' => $backdrop ? 'true' : 'false',
            'aria-labelledby' => $title ? "{$id}-label" : null,
            'aria-label' => $title ? null : 'Painel lateral',
        ]);
@endphp

<div {{ $dialogAttributes }}>
    <div class="flex h-full flex-col">
        @if ($title)
            <div class="border-default-300 flex items-center justify-between gap-3 border-b p-5">
                <div class="min-w-0">
                    <h3 id="{{ $id }}-label" class="text-body-color truncate text-lg font-semibold">{{ $title }}</h3>
                </div>

                <button
                    type="button"
                    class="hover:bg-light inline-flex size-8 items-center justify-center rounded-full opacity-70 transition hover:opacity-100"
                    aria-label="Fechar"
                    data-hs-overlay="#{{ $id }}"
                >
                    <i class="iconify tabler--x text-xl"></i>
                </button>
            </div>
        @endif

        <div class="grow overflow-y-auto p-5">{{ $slot }}</div>

        @isset ($footer)
            <div class="border-default-300 shrink-0 border-t p-5">{{ $footer }}</div>
        @endisset
    </div>
</div>
