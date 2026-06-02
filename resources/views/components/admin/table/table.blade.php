@props ([
    'density' => 'comfortable',
    'stickyHeader' => true,
    'loadingTarget' => null,
])

@php
    $densityClass = $density === 'compact' ? 'table-sm' : '';
    $loadingAttr = $loadingTarget ? "wire:target=\"{$loadingTarget}\"" : '';
@endphp

<div {{ $attributes->class(['relative']) }}>
    {{-- Overlay de carregamento --}}
    <div
        wire:loading.flex
        {!! $loadingAttr !!}
        class="bg-card/60 absolute inset-0 z-20 hidden items-center justify-center rounded-xl backdrop-blur-[1px]"
    >
        <span class="iconify tabler--loader-2 text-primary size-7 animate-spin"></span>
    </div>

    <div class="overflow-x-auto">
        <table @class (['table w-full', $densityClass, 'table-sticky' => $stickyHeader])>
            {{ $slot }}
        </table>
    </div>
</div>
