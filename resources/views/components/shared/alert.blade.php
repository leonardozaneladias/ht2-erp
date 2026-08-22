@props ([
    'variant' => 'info',
    'solid' => false,
    'icon' => null,
    'dismissible' => false,
    'title' => null,
])

@php
    $variants = [
        'primary' => [
            'soft' => 'bg-primary/15 text-primary',
            'solid' => 'bg-primary text-white',
            'icon' => 'tabler--info-circle',
        ],
        'secondary' => [
            'soft' => 'bg-secondary/15 text-secondary',
            'solid' => 'bg-secondary text-white',
            'icon' => 'tabler--info-circle',
        ],
        'success' => [
            'soft' => 'bg-success/15 text-success',
            'solid' => 'bg-success text-white',
            'icon' => 'tabler--circle-check',
        ],
        'danger' => [
            'soft' => 'bg-danger/15 text-danger',
            'solid' => 'bg-danger text-white',
            'icon' => 'tabler--alert-octagon',
        ],
        'warning' => [
            'soft' => 'bg-warning/15 text-warning',
            'solid' => 'bg-warning text-white',
            'icon' => 'tabler--alert-triangle',
        ],
        'info' => [
            'soft' => 'bg-info/15 text-info',
            'solid' => 'bg-info text-white',
            'icon' => 'tabler--info-circle',
        ],
        'light' => [
            'soft' => 'border border-default-300 bg-light/60 text-default-700',
            'solid' => 'bg-light text-dark',
            'icon' => 'tabler--info-circle',
        ],
        'dark' => [
            'soft' => 'bg-dark/15 text-dark',
            'solid' => 'bg-dark text-white',
            'icon' => 'tabler--info-circle',
        ],
    ];

    $variant = array_key_exists($variant, $variants) ? $variant : 'info';
    $tone = $solid ? 'solid' : 'soft';
    $resolvedIcon = $icon === false ? null : ($icon ?: $variants[$variant]['icon']);
    $wrapperId = $attributes->get('id') ?: 'alert-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));

    // role/live-region por severidade (WAI-ARIA APG; mesmo critério do x-shared.toast,
    // que já usa role="status"/aria-live="polite"): erros e avisos interrompem o leitor
    // de tela (assertive, via role="alert"); confirmações e mensagens informativas são
    // anunciadas sem interromper (polite, via role="status"). O dev pode sobrescrever
    // passando role="..." (o merge preserva atributos explícitos).
    $defaultRole = in_array($variant, ['danger', 'warning'], true) ? 'alert' : 'status';

    $wrapperAttributes = $attributes
        ->class([
            'hs-removing:translate-x-5 hs-removing:opacity-0',
            'rounded px-4 py-3 transition duration-300',
            'flex items-start gap-3',
            $variants[$variant][$tone],
            '[&_a]:font-semibold [&_a]:underline [&_a]:underline-offset-2',
            '[&_a:hover]:opacity-80',
        ])
        ->merge([
            'id' => $wrapperId,
            'role' => $defaultRole,
        ]);
@endphp

<div {{ $wrapperAttributes }}>
    @if ($resolvedIcon)
        <span class="mt-0.5 inline-flex shrink-0 items-center justify-center">
            <i class="iconify {{ $resolvedIcon }} text-xl" aria-hidden="true"></i>
        </span>
    @endif

    <div class="min-w-0 grow text-sm leading-5">
        @if ($title)
            <p class="mb-1 font-semibold">{{ $title }}</p>
        @endif

        <div class="space-y-2">{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="ms-auto inline-flex size-8 shrink-0 items-center justify-center rounded-full opacity-70 transition hover:opacity-100"
            aria-label="Fechar"
            data-hs-remove-element="#{{ $wrapperId }}"
        >
            <i class="iconify tabler--x text-lg" aria-hidden="true"></i>
        </button>
    @endif
</div>
