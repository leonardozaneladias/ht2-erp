@props ([
    'icon' => 'tabler--inbox',
    'title',
    'description' => null,
    'size' => 'md',
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
        <i class="iconify {{ $icon }} {{ $config['icon'] }}"></i>
    </div>

    <h3 @class ([
        'mb-2 font-semibold text-body-color dark:text-default-100',
        $config['title'],
    ])>
        {{ $title }}
    </h3>

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
