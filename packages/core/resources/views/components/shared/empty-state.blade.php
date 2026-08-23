@props ([
    'icon' => 'tabler--inbox',
    'title',
    'description' => null,
    'size' => 'md',
    'titleLevel' => 'h3',
])

@php
    $sizes = [
        'sm' => [
            'wrapper' => 'py-8 px-4',
            'circle' => 'size-12',
            'icon' => 'text-2xl',
            'title' => 'text-base',
            'description' => 'text-sm',
        ],
        'md' => [
            'wrapper' => 'py-16 px-4',
            'circle' => 'size-20',
            'icon' => 'text-4xl',
            'title' => 'text-lg',
            'description' => 'text-sm',
        ],
        'lg' => [
            'wrapper' => 'py-24 px-4',
            'circle' => 'size-28',
            'icon' => 'text-5xl',
            'title' => 'text-xl',
            'description' => 'text-base',
        ],
    ];

    $size = array_key_exists($size, $sizes) ? $size : 'md';
    $config = $sizes[$size];
    $hasBodySlot = trim((string) $slot) !== '';

    // Nível do heading configurável: sob um page-header (<h4>) a listagem deve
    // descer para <h5>, e não regredir para <h3>. Whitelist h1..h6 protege a
    // interpolação dinâmica <{{ $titleLevel }}> de injeção de tag arbitrária.
    $titleLevel = in_array($titleLevel, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $titleLevel : 'h3';
    $titleClasses = \Illuminate\Support\Arr::toCssClasses([
        'mb-2 font-semibold text-body-color dark:text-default-100',
        $config['title'],
    ]);
@endphp

<div {{
$attributes->class([
    'flex flex-col items-center justify-center text-center',
    $config['wrapper'],
])
}}>
    <div
        @class ([
        'mb-4 inline-flex items-center justify-center rounded-full bg-default-100 text-default-400 dark:bg-default-900 dark:text-default-500',
        $config['circle'],
    ])
    >
        <i class="iconify {{ $icon }} {{ $config['icon'] }}" aria-hidden="true"></i>
    </div>

    <{{ $titleLevel }} class="{{ $titleClasses }}"> {{ $title }} </{{ $titleLevel }}>

    @if ($hasBodySlot)
        <div
            @class ([
            'mb-6 max-w-md text-default-400 dark:text-default-400',
            $config['description'],
        ])
        >
            {{ $slot }}
        </div>
    @elseif (filled($description))
        <p
            @class ([
            'mb-6 max-w-md text-default-400 dark:text-default-400',
            $config['description'],
        ])
        >
            {{ $description }}
        </p>
    @endif

    @isset ($action)
        <div class="flex flex-wrap justify-center gap-3">{{ $action }}</div>
    @endisset
</div>
