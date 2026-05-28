@props ([
    'variant' => 'default',
    'title' => null,
    'message' => null,
    'icon' => null,
    'dismissible' => true,
])

@php
    $variants = [
        'default' => [
            'wrapper' => 'border-default-300 bg-card/95 text-default-700 dark:border-default-700 dark:bg-default-950/95 dark:text-default-100',
            'iconColor' => 'text-info',
            'icon' => 'tabler--info-circle',
        ],
        'success' => [
            'wrapper' => 'border-success/30 bg-success/10 text-success dark:border-success/40 dark:bg-success/15',
            'iconColor' => 'text-success',
            'icon' => 'tabler--circle-check',
        ],
        'danger' => [
            'wrapper' => 'border-danger/30 bg-danger/10 text-danger dark:border-danger/40 dark:bg-danger/15',
            'iconColor' => 'text-danger',
            'icon' => 'tabler--alert-octagon',
        ],
        'warning' => [
            'wrapper' => 'border-warning/30 bg-warning/10 text-warning dark:border-warning/40 dark:bg-warning/15',
            'iconColor' => 'text-warning',
            'icon' => 'tabler--alert-triangle',
        ],
        'info' => [
            'wrapper' => 'border-info/30 bg-info/10 text-info dark:border-info/40 dark:bg-info/15',
            'iconColor' => 'text-info',
            'icon' => 'tabler--info-circle',
        ],
    ];

    $variant = array_key_exists($variant, $variants) ? $variant : 'default';
    $body = trim((string) $slot) !== '' ? $slot : $message;
    $resolvedIcon = $icon ?: $variants[$variant]['icon'];
@endphp

<div
    {{
$attributes->class([
    'pointer-events-auto w-full max-w-sm overflow-hidden rounded-xl border shadow-lg backdrop-blur',
    'transition duration-300 ease-out',
    $variants[$variant]['wrapper'],
])
}}
    data-toast-item
    role="status"
    aria-live="polite"
>
    <div class="flex items-start gap-3 p-4">
        <span
            class="dark:bg-default-900/70 mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white/70"
        >
            <i class="iconify {{ $resolvedIcon }} {{ $variants[$variant]['iconColor'] }} text-xl"></i>
        </span>

        <div class="min-w-0 flex-1">
            @if (filled($title))
                <p class="text-body-color dark:text-default-100 font-semibold">{{ $title }}</p>
            @endif

            @if (filled($body))
                <div class="text-default-500 dark:text-default-400 mt-1 text-sm leading-6">{{ $body }}</div>
            @endif
        </div>

        @if ($dismissible)
            <button
                type="button"
                class="text-default-400 hover:text-body-color dark:text-default-500 dark:hover:text-default-100 inline-flex size-8 shrink-0 items-center justify-center rounded-full transition hover:bg-black/5 dark:hover:bg-white/5"
                aria-label="Fechar toast"
                data-toast-close
            >
                <i class="iconify tabler--x text-lg"></i>
            </button>
        @endif
    </div>
</div>
