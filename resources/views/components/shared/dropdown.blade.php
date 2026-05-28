@props ([
    'placement' => 'bottom-end',
    'trigger' => 'click',
    'autoClose' => 'true',
])

@php
    $trigger = in_array($trigger, ['click', 'hover'], true) ? $trigger : 'click';
    $autoClose = in_array((string) $autoClose, ['true', 'inside', 'false'], true) ? (string) $autoClose : 'true';
    $wrapperClasses = collect([
        'hs-dropdown relative inline-flex',
        "[--placement:{$placement}]",
        $trigger === 'hover' ? '[--trigger:hover]' : null,
        $autoClose !== 'true' ? "[--auto-close:{$autoClose}]" : null,
    ])->filter()->all();
@endphp

<div {{ $attributes->class($wrapperClasses) }}>
    @isset ($button)
        {{ $button }}
    @else
        <button
            type="button"
            class="hs-dropdown-toggle btn bg-light text-dark hover:text-primary"
            aria-expanded="false"
            aria-haspopup="menu"
        >
            Ações
            <i class="iconify tabler--chevron-down hs-dropdown-open:rotate-180 text-base transition-transform"></i>
        </button>
    @endisset

    <div class="hs-dropdown-menu" role="menu" aria-orientation="vertical">{{ $slot }}</div>
</div>
