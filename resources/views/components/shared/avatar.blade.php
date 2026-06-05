@props ([
    'name' => '',
    'src' => null,
    'size' => 'size-8',
])

@php
    $iniciais = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
        ->filter()
        ->take(2)
        ->map(static fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
    $iniciais = $iniciais !== '' ? $iniciais : '?';

    $cores = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-secondary'];
    $cor = $cores[(int) (crc32((string) $name) % count($cores))];
@endphp

@if ($src)
    <img alt="{{ $name }}" src="{{ $src }}" {{ $attributes->class([$size, 'rounded-full object-cover']) }} />
@else
    <span
        aria-label="{{ $name }}"
        {{ $attributes->class([$size, $cor, 'inline-flex items-center justify-center rounded-full text-sm font-semibold text-white']) }}
    >
        {{ $iniciais }}
    </span>
@endif
