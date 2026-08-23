@props ([
    'title' => null,
    'subtitle' => null,
    'bodyPadding' => true,
])

<div {{ $attributes->class(['card']) }}>
    @if ($title || $subtitle || isset($header) || isset($headerActions))
        <div class="card-header">
            @if (isset($header))
                {{ $header }}
            @elseif ($title || $subtitle)
                <div class="min-w-0">
                    @if ($title)
                        <h4 class="card-title">{{ $title }}</h4>
                    @endif

                    @if ($subtitle)
                        <p class="text-2xs text-default-400 mt-1">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            @isset ($headerActions)
                <div class="ms-auto flex flex-wrap items-center gap-2">{{ $headerActions }}</div>
            @endisset
        </div>
    @endif

    <div @class (['card-body', '!p-0' => ! $bodyPadding])> {{ $slot }}</div>

    @isset ($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
