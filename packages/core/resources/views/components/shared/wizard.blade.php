@props ([
    // Lista de passos: strings ('Enviar') ou mapas (['label' => 'Enviar', 'icon' => 'tabler--upload']).
    'steps' => [],
    // Passo atual, 1-indexed. Fora do intervalo é normalizado para dentro dele.
    'current' => 1,
    // Fluxo terminado: todos os passos aparecem como concluídos (nenhum "atual").
    'completed' => false,
])

@php
    $passos = collect($steps)
        ->values()
        ->map(fn ($passo) => is_array($passo) ? $passo : ['label' => (string) $passo])
        ->all();

    $totalPassos = count($passos);
    $atual = max(1, min(max($totalPassos, 1), (int) $current));
@endphp

@if ($totalPassos > 0)
    <div {{ $attributes->class(['w-full']) }}>
        {{-- Stepper horizontal (telas ≥ sm) --}}
        <nav aria-label="Progresso das etapas" class="hidden sm:block">
            <ol class="flex items-start">
                @foreach ($passos as $i => $passo)
                    @php
                        $numero = $i + 1;
                        $estado = match (true) {
                            (bool) $completed => 'concluido',
                            $numero < $atual => 'concluido',
                            $numero === $atual => 'atual',
                            default => 'futuro',
                        };
                    @endphp
                    <li
                        @class ([
                            'relative flex-1',
                            // Conector até o próximo passo; pinta de primary quando este passo já foi concluído.
                            // A transição anima a pintura na troca de etapa (morph só muda a classe).
                            "after:absolute after:top-4 after:start-1/2 after:h-0.5 after:w-full after:transition-colors after:duration-300 motion-reduce:after:transition-none after:content-['']" => ! $loop->last,
                            'after:bg-primary' => ! $loop->last && $estado === 'concluido',
                            'after:bg-default-200' => ! $loop->last && $estado !== 'concluido',
                        ])
                        @if ($estado === 'atual') aria-current="step" @endif
                    >
                        <div class="relative z-10 flex flex-col items-center">
                            <div
                                @class ([
                                    'flex size-8 items-center justify-center rounded-full text-sm font-semibold transition-colors duration-300',
                                    'bg-primary text-white' => $estado === 'concluido',
                                    'bg-primary ring-primary/20 text-white ring-4' => $estado === 'atual',
                                    'bg-default-200 text-default-600' => $estado === 'futuro',
                                ])
                            >
                                @if ($estado === 'concluido')
                                    {{-- O check é um nó novo no morph (substitui ícone/número) — o pop roda 1x. --}}
                                    <span
                                        class="iconify tabler--check animate-pop-in size-4 motion-reduce:animate-none"
                                        aria-hidden="true"
                                    ></span>
                                @elseif (filled($passo['icon'] ?? null))
                                    <span class="iconify {{ $passo['icon'] }} size-4" aria-hidden="true"></span>
                                @else
                                    {{ $numero }}
                                @endif
                            </div>
                            <span
                                @class ([
                                    'mt-2 px-1 text-center text-xs',
                                    'text-primary font-semibold' => $estado === 'atual',
                                    'text-body-color font-medium' => $estado === 'concluido',
                                    'text-default-400' => $estado === 'futuro',
                                ])
                            >
                                {{ $passo['label'] ?? '' }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ol>
        </nav>

        {{-- Telas pequenas: barra compacta no lugar do stepper --}}
        <div class="sm:hidden">
            <x-shared.progress-bar
                :value="$completed ? 100 : ($atual / $totalPassos) * 100"
                size="sm"
                :aria-label="$completed ? 'Todas as etapas concluídas' : 'Etapa ' . $atual . ' de ' . $totalPassos"
            />
            <p class="text-default-400 mt-1.5 text-center text-xs">
                @if ($completed)
                    <span class="text-body-color font-medium">Todas as etapas concluídas</span>
                @else
                    Etapa {{ $atual }} de {{ $totalPassos }} —
                    <span class="text-body-color font-medium">{{ $passos[$atual - 1]['label'] ?? '' }}</span>
                @endif
            </p>
        </div>

        @if ($slot->isNotEmpty())
            <div class="mt-6">{{ $slot }}</div>
        @endif
    </div>
@endif
