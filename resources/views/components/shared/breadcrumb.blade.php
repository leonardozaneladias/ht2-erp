@props ([
    'items' => [],
    'divider' => 'tabler--chevron-right',
])

@php
    $items = collect(is_array($items) ? $items : [])
        ->filter(fn (array $item) => filled($item['label'] ?? null))
        ->values()
        ->all();

    $dividerIsIcon = str_contains((string) $divider, '--');
@endphp

@if ($items !== [])
    <nav aria-label="Breadcrumb" {{ $attributes->class(['py-2.5']) }}>
        <ol class="flex flex-wrap items-center gap-y-1 text-sm whitespace-nowrap">
            @foreach ($items as $index => $item)
                @php
                    $label = $item['label'];
                    $url = $item['url'] ?? $item['href'] ?? null;
                    $icon = $item['icon'] ?? null;
                    $isLast = ($item['current'] ?? false) || $index === array_key_last($items);
                @endphp
                <li
                    @class ([
                    'inline-flex max-w-full items-center gap-1.5',
                    'text-default-400 font-medium' => $isLast,
                ])
                    @if ($isLast) aria-current="page" @endif
                >
                    @if (! $isLast && filled($url))
                        <a
                            href="{{ $url }}"
                            class="text-default-600 hover:text-primary inline-flex min-w-0 items-center gap-1 font-medium transition"
                        >
                            @if ($icon)
                                <i class="iconify {{ $icon }} shrink-0 text-sm" aria-hidden="true"></i>
                            @endif

                            <span>{{ $label }}</span>
                        </a>
                    @else
                        <span class="inline-flex min-w-0 items-center gap-1 truncate">
                            @if ($icon)
                                <i class="iconify {{ $icon }} shrink-0 text-sm" aria-hidden="true"></i>
                            @endif

                            <span class="truncate">{{ $label }}</span>
                        </span>
                    @endif

                    @unless ($isLast)
                        @if ($dividerIsIcon)
                            <i
                                class="iconify {{ $divider }} text-default-400 text-base rtl:rotate-180"
                                aria-hidden="true"
                            ></i>
                        @else
                            <span class="text-default-400 px-1" aria-hidden="true">{{ $divider }}</span>
                        @endif
                    @endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif
