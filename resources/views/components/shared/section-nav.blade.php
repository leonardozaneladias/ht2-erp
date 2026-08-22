@props ([
    /**
     * Seções, na ordem de exibição:
     *   ['id' => ['rotulo' => 'Identificação', 'icone' => 'tabler--user', 'grupo' => 'Cadastro', 'erro' => false, 'badge' => null]]
     * `grupo`, `erro` e `badge` são opcionais.
     */
    'sections' => [],
    // Id da seção ativa.
    'active' => null,
    // Método Livewire chamado com o id da seção: wire:click="irPara('documentos')".
    'action' => 'irPara',
    'ariaLabel' => 'Seções',
])

{{--
    Navegação lateral de seções.

    Substitui bem uma barra de abas quando há muitas seções: a barra horizontal quebra em
    duas linhas e vira um "muro" que ninguém escaneia. Tem agrupamento e indicador de erro
    por seção — este último para formulários, onde a validação pode falhar numa seção que
    não está à vista.

    Renderiza só a <nav>. Quem consome monta o grid e o painel:

        <div class="grid items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
            <x-shared.card class="lg:sticky lg:top-20">
                <x-shared.section-nav :sections="..." :active="$secaoAtiva" action="irPara" />
            </x-shared.card>
            <div class="relative">…</div>
        </div>
--}}

@php
    // Agrupa preservando a ordem. Seções sem `grupo` caem num bloco anônimo, que é
    // renderizado sem cabeçalho — assim a Ficha adota o componente sem mudar de visual.
    //
    // preserveKeys é obrigatório: sem ele o groupBy REINDEXA os itens e o id da seção
    // (a chave) vira 0, 1, 2 — o wire:click passaria a chamar irPara('0').
    $grupos = collect($sections)->groupBy(
        fn (array $secao): string => $secao['grupo'] ?? '',
        preserveKeys: true,
    );
@endphp

<nav class="grid gap-0.5" aria-label="{{ $ariaLabel }}">
    @foreach ($grupos as $grupo => $itens)
        @if ($grupo !== '')
            <p
                class="text-default-400 px-3 pt-3 pb-1 text-xs font-semibold tracking-wide uppercase first:pt-0"
                id="grupo-{{ \Illuminate\Support\Str::slug($grupo) }}"
            >
                {{ $grupo }}
            </p>
        @endif
        @foreach ($itens as $id => $secao)
            <button
                type="button"
                {{-- Id previsível: âncora estável para os testes E2E e para aria-labelledby. --}}
                id="secao-{{ $id }}"
                wire:key="section-nav-{{ $id }}"
                wire:click="{{ $action }}('{{ $id }}')"
                @class ([
                    'flex items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm font-medium transition-colors',
                    'bg-primary/10 text-primary' => $id === $active,
                    'text-body-color hover:bg-light/70' => $id !== $active,
                ])
                @if ($id === $active) aria-current="page" @endif
            >
                @if (filled($secao['icone'] ?? null))
                    <i class="iconify {{ $secao['icone'] }} size-4 shrink-0" aria-hidden="true"></i>
                @endif

                <span class="truncate">{{ $secao['rotulo'] }}</span>

                @if ($secao['erro'] ?? false)
                    {{-- Mesmo ponto vermelho do x-shared.tab-trigger: o operador precisa saber
                         que a validação falhou numa seção que não está à vista. --}}
                    <span class="bg-danger ms-auto size-2 shrink-0 rounded-full" aria-hidden="true"></span>
                    <span class="sr-only">(há erros nesta seção)</span>
                @elseif (filled($secao['badge'] ?? null))
                    <span class="text-default-400 ms-auto shrink-0 text-xs">{{ $secao['badge'] }}</span>
                @endif
            </button>
        @endforeach
    @endforeach
</nav>
