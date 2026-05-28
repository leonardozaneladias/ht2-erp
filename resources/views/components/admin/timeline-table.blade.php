@props ([
    'programacoes' => [],
    'gapMessage' => null,
    'emptyMessage' => 'Nenhuma programação cadastrada.',
])

@php
    $now = \Illuminate\Support\Carbon::now();
    $actionSolidClasses = [
        'default' => 'border-default-300 bg-card text-default-700 hover:bg-light',
        'primary' => 'bg-primary text-white hover:bg-primary-hover',
        'secondary' => 'bg-secondary text-white hover:bg-secondary-hover',
        'success' => 'bg-success text-white hover:bg-success-hover',
        'danger' => 'bg-danger text-white hover:bg-danger-hover',
        'warning' => 'bg-warning text-white hover:bg-warning-hover',
        'info' => 'bg-info text-white hover:bg-info-hover',
        'light' => 'bg-light text-dark hover:bg-light-hover',
        'dark' => 'bg-dark text-white hover:bg-dark-hover',
    ];
    $actionOutlineClasses = [
        'default' => 'border-default-300 text-default-700 hover:bg-light',
        'primary' => 'border-primary text-primary hover:bg-primary hover:text-white',
        'secondary' => 'border-secondary text-secondary hover:bg-secondary hover:text-white',
        'success' => 'border-success text-success hover:bg-success hover:text-white',
        'danger' => 'border-danger text-danger hover:bg-danger hover:text-white',
        'warning' => 'border-warning text-warning hover:bg-warning hover:text-white',
        'info' => 'border-info text-info hover:bg-info hover:text-white',
        'light' => 'border-light text-dark hover:bg-light hover:text-dark',
        'dark' => 'border-dark text-dark hover:bg-dark hover:text-white',
    ];
    $actionGhostClasses = [
        'default' => 'text-default-700 hover:bg-light',
        'primary' => 'text-primary hover:bg-primary/15',
        'secondary' => 'text-secondary hover:bg-secondary/15',
        'success' => 'text-success hover:bg-success/15',
        'danger' => 'text-danger hover:bg-danger/15',
        'warning' => 'text-warning hover:bg-warning/15',
        'info' => 'text-info hover:bg-info/15',
        'light' => 'text-dark hover:bg-light',
        'dark' => 'text-dark hover:bg-dark/15',
    ];

    $rows = collect($programacoes)->map(function ($programacao) use ($now) {
        $inicio = data_get($programacao, 'inicio');
        $fim = data_get($programacao, 'fim');

        $inicio = $inicio ? \Illuminate\Support\Carbon::parse($inicio) : null;
        $fim = $fim ? \Illuminate\Support\Carbon::parse($fim) : null;

        $status = data_get($programacao, 'status');

        if (! is_string($status) || $status === '') {
            $status = match (true) {
                $inicio && $inicio->isFuture() => 'futura',
                $fim && $fim->isPast() => 'expirada',
                default => 'ativa',
            };
        }

        $statusMeta = [
            'ativa' => [
                'variant' => 'success',
                'card' => 'border-success/35 bg-success/8',
                'track' => 'bg-success',
                'label' => 'ATIVA',
            ],
            'futura' => [
                'variant' => 'info',
                'card' => 'border-info/35 bg-info/8',
                'track' => 'bg-info',
                'label' => 'FUTURA',
            ],
            'expirada' => [
                'variant' => 'default',
                'card' => 'border-default-300 bg-light/70 opacity-80',
                'track' => 'bg-default-300',
                'label' => 'EXPIRADA',
            ],
        ][$status] ?? [
            'variant' => 'default',
            'card' => 'border-default-300 bg-light/60',
            'track' => 'bg-default-300',
            'label' => strtoupper((string) $status),
        ];

        $valor = data_get($programacao, 'valor_formatado');

        if (! is_string($valor) || $valor === '') {
            $rawValue = data_get($programacao, 'valor');

            if (is_numeric($rawValue)) {
                $valor = 'R$ '.number_format((float) $rawValue, 2, ',', '.');
            } else {
                $valor = '—';
            }
        }

        $periodo = data_get($programacao, 'periodo');

        if (! is_string($periodo) || $periodo === '') {
            $periodo = $inicio && $fim
                ? $inicio->format('d/m/Y').' - '.$fim->format('d/m/Y')
                : 'Período não informado';
        }

        $duracao = data_get($programacao, 'duracao');

        if (! is_string($duracao) || $duracao === '') {
            $duracao = $inicio && $fim
                ? $inicio->diffInDays($fim).' dias'
                : null;
        }

        $actions = collect(data_get($programacao, 'actions', []))
            ->filter(fn ($action) => is_array($action))
            ->values();

        return [
            'status_meta' => $statusMeta,
            'periodo' => $periodo,
            'duracao' => $duracao,
            'valor' => $valor,
            'parcelas' => data_get($programacao, 'parcelas', '—'),
            'descricao' => data_get($programacao, 'descricao') ?: '—',
            'actions' => $actions,
        ];
    })->values();

    $hasRows = $rows->isNotEmpty();
@endphp

<div {{ $attributes->class(['space-y-3']) }}>
    <div
        class="text-default-400 hidden grid-cols-12 gap-4 px-4 text-[11px] font-semibold tracking-[0.18em] uppercase md:grid"
    >
        <div class="col-span-4">Período</div>
        <div class="col-span-2 text-end">Valor</div>
        <div class="col-span-1 text-center">Parcelas</div>
        <div class="col-span-3">Descrição</div>
        <div class="col-span-2 text-end">Ações</div>
    </div>

    @if ($hasRows)
        @foreach ($rows as $row)
            <div
                @class ([
                'rounded-2xl border p-4 shadow-sm transition',
                'space-y-4 md:space-y-0 md:grid md:grid-cols-12 md:items-center md:gap-4',
                $row['status_meta']['card'],
            ])
            >
                <div class="space-y-3 md:col-span-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-shared.badge :variant="$row['status_meta']['variant']" pill size="sm">
                            {{ $row['status_meta']['label'] }}
                        </x-shared.badge>
                        <span class="text-body-color text-sm font-semibold">{{ $row['periodo'] }}</span>
                    </div>

                    @if ($row['duracao'])
                        <p class="text-default-400 text-xs">{{ $row['duracao'] }}</p>
                    @endif

                    <div class="bg-default-200 h-1.5 w-full overflow-hidden rounded-full">
                        <div class="{{ $row['status_meta']['track'] }} h-full w-full rounded-full"></div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <p class="text-default-400 text-xs tracking-[0.14em] uppercase md:hidden">Valor</p>
                    <p class="text-body-color text-sm font-semibold md:text-end">{{ $row['valor'] }}</p>
                </div>

                <div class="md:col-span-1">
                    <p class="text-default-400 text-xs tracking-[0.14em] uppercase md:hidden">Parcelas</p>
                    <p class="text-body-color text-sm font-semibold md:text-center">{{ $row['parcelas'] }}</p>
                </div>

                <div class="md:col-span-3">
                    <p class="text-default-400 text-xs tracking-[0.14em] uppercase md:hidden">Descrição</p>
                    <p class="text-body-color text-sm">{{ $row['descricao'] }}</p>
                </div>

                <div class="md:col-span-2">
                    @if ($row['actions']->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-2 md:justify-end">
                            @foreach ($row['actions'] as $action)
                                @php
                                    $actionAttributes = new \Illuminate\View\ComponentAttributeBag($action['attributes'] ?? []);
                                    $actionVariant = $action['variant'] ?? 'default';
                                    $actionAppearance = $action['appearance'] ?? 'outline';
                                    $actionToneClasses = match ($actionAppearance) {
                                        'ghost' => $actionGhostClasses[$actionVariant] ?? $actionGhostClasses['default'],
                                        'solid' => $actionSolidClasses[$actionVariant] ?? $actionSolidClasses['default'],
                                        default => $actionOutlineClasses[$actionVariant] ?? $actionOutlineClasses['default'],
                                    };
                                    $actionClasses = [
                                        'btn btn-sm inline-flex items-center justify-center gap-x-2 transition-all',
                                        $actionToneClasses,
                                    ];
                                @endphp
                                @if (! empty($action['href']))
                                    <a href="{{ $action['href'] }}" {{ $actionAttributes->class($actionClasses) }}>
                                        @if (! empty($action['icon']))
                                            <i class="iconify {{ $action['icon'] }} text-base shrink-0"></i>
                                        @endif
                                        <span>{{ $action['label'] ?? 'Ação' }}</span>
                                    </a>
                                @else
                                    <button
                                        type="{{ $action['type'] ?? 'button' }}"
                                        {{ $actionAttributes->class($actionClasses) }}
                                    >
                                        @if (! empty($action['icon']))
                                            <i class="iconify {{ $action['icon'] }} text-base shrink-0"></i>
                                        @endif
                                        <span>{{ $action['label'] ?? 'Ação' }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div
            class="border-default-300 bg-card text-default-400 rounded-2xl border px-6 py-12 text-center text-sm shadow-sm"
        >
            {{ $emptyMessage }}
        </div>
    @endif

    @if ($gapMessage)
        <x-shared.alert variant="warning" title="Gap detectado"> {{ $gapMessage }} </x-shared.alert>
    @endif
</div>
