@props ([
    'label',
    'value' => 0,
    'icon' => 'tabler--chart-bar',
    // default | primary | success | info | warning | danger
    'variant' => 'default',
    // Contagem animada até o valor (rAF; desliga sozinha com prefers-reduced-motion).
    'countUp' => true,
    // Atraso da entrada em ms, para stagger dentro de um grid de tiles.
    'delay' => 0,
])

@php
    $valor = (int) $value;

    $estilos = match ($variant) {
        'primary' => ['soft' => 'bg-primary/12 text-primary', 'texto' => 'text-primary'],
        'success' => ['soft' => 'bg-success/12 text-success', 'texto' => 'text-success'],
        'info' => ['soft' => 'bg-info/12 text-info', 'texto' => 'text-info'],
        'warning' => ['soft' => 'bg-warning/12 text-warning', 'texto' => 'text-warning'],
        'danger' => ['soft' => 'bg-danger/12 text-danger', 'texto' => 'text-danger'],
        default => ['soft' => 'bg-light text-default-500', 'texto' => 'text-body-color'],
    };
@endphp

<div
    {{ $attributes->class(['border-default-200 bg-light/30 animate-fade-up motion-reduce:animate-none flex items-center gap-3 rounded-xl border p-4']) }}
    @if ((int) $delay > 0) style="animation-delay: {{ (int) $delay }}ms" @endif
>
    <span class="{{ $estilos['soft'] }} flex size-10 shrink-0 items-center justify-center rounded-full">
        <span class="iconify {{ $icon }} size-5" aria-hidden="true"></span>
    </span>
    <div class="min-w-0">
        <p class="text-2xs text-default-400 tracking-wide uppercase">{{ $label }}</p>
        <p
            class="{{ $estilos['texto'] }} text-2xl leading-tight font-semibold tabular-nums"
            @if ($countUp)
                x-data="{ v: {{ $valor }} }"
                x-init="
                    const alvo = {{ $valor }};
                    if (alvo > 0 && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        v = 0;
                        const t0 = performance.now();
                        const passo = (t) => {
                            v = Math.round(alvo * Math.min(1, (t - t0) / 500));
                            if (v < alvo) requestAnimationFrame(passo);
                        };
                        requestAnimationFrame(passo);
                    }
                "
                x-text="v.toLocaleString('pt-BR')"
            @endif
        >
            {{ number_format($valor, 0, ',', '.') }}
        </p>
    </div>
</div>
