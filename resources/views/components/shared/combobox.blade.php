@props ([
    // 'form'      -> controla um <select> nativo oculto (passado no slot).
    // 'pg-filter' -> despacha pg:multiSelect para o PowerGrid (sem slot).
    'mode' => 'form',
    'multiple' => false,
    'searchable' => true,
    'placeholder' => 'Selecione...',
    'searchPlaceholder' => 'Buscar...',
    // Opcoes prontas [{value,label,disabled,group}] (pg-filter). No modo form
    // ficam null e o factory le do <select> do slot.
    'options' => null,
    'initial' => [],
    // Exclusivos do modo pg-filter (montam o dispatch para o PowerGrid).
    'field' => null,
    'tableName' => null,
    'label' => null,
    'id' => null,
    'triggerClass' => '',
    // Estado de erro de validacao (pinta a borda do gatilho de vermelho).
    'invalid' => false,
    'required' => false,
    // Acessibilidade (modo form): id do <label> externo e ids de erro/hint, para
    // que o gatilho (controle real) seja nomeado/descrito pelo leitor de tela.
    'labelId' => null,
    'describedBy' => null,
    // clearable: mostra o botao limpar. emptyValue: valor sentinela "sem selecao"
    // no <select> do modo form (ex.: 'all'/'' dos filtros boolean/select).
    'clearable' => true,
    'emptyValue' => '',
])

@php
    $comboboxId = $id ?: ($field !== null ? "combobox-{$tableName}-{$field}" : 'combobox-' . \Illuminate\Support\Str::random(6));
    $panelId = "{$comboboxId}-panel";
    $triggerId = "{$comboboxId}-trigger";
    $valueId = "{$comboboxId}-value";

    // Nome acessivel do gatilho. Prioriza um <label> externo associado via labelId
    // (modo form, ex.: select-search). Na ausencia dele, se houver `label` (ex.:
    // filtro multi-select pg-filter, que muitas vezes nao tem <label> visivel
    // quando inline), gera um rotulo sr-only interno para nomear o gatilho ao
    // leitor de tela (WCAG 4.1.2). Em ambos os casos o id do valor entra no nome,
    // anunciando rotulo + selecao atual. Sem labelId nem label -> sem nome (legado).
    $internalLabelId = (! $labelId && filled($label)) ? "{$comboboxId}-label" : null;
    $labelledBy = match (true) {
        (bool) $labelId => "{$labelId} {$valueId}",
        (bool) $internalLabelId => "{$internalLabelId} {$valueId}",
        default => null,
    };

    $config = [
        'mode' => $mode,
        'multiple' => $multiple,
        'searchable' => $searchable,
        'placeholder' => $placeholder,
        'field' => $field,
        'tableName' => $tableName,
        'label' => $label ?? $field,
        'initial' => array_values($initial ?? []),
        'clearable' => $clearable,
        'emptyValue' => $emptyValue,
    ];

    if ($options !== null) {
        $config['options'] = array_values($options);
    }
@endphp

<div x-data="comboBox(@js($config))" @keydown.escape="fechar(true);" {{ $attributes->class('relative') }}>
    {{-- Modo form: o <select> nativo oculto (x-ref="native") chega pelo slot. --}}
    {{ $slot }}

    @if ($internalLabelId)
        {{-- Rotulo acessivel interno: nomeia o gatilho quando nao ha <label>
             externo associado via labelId (ex.: filtro multi-select inline). --}}
        <span id="{{ $internalLabelId }}" class="sr-only">{{ $label }}</span>
    @endif

    {{-- Gatilho compacto: mostra o resumo ("Todos" / label / "N selecionados"). --}}
    <button
        type="button"
        id="{{ $triggerId }}"
        x-ref="gatilho"
        @click="alternar();"
        @keydown.arrow-down.prevent="abrir();"
        @keydown.arrow-up.prevent="abrir();"
        aria-haspopup="listbox"
        :aria-expanded="open ? 'true' : 'false'"
        aria-controls="{{ $panelId }}"
        @if ($labelledBy) aria-labelledby="{{ $labelledBy }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($required) aria-required="true" @endif
        @if ($invalid) aria-invalid="true" @endif
        data-testid="combobox-trigger"
        @if ($field !== null) data-combobox-field="{{ $field }}" @endif
        @if ($tableName !== null) data-combobox-table="{{ $tableName }}" @endif
        @class ([
            'text-body-color bg-card flex w-full items-center justify-between gap-1.5 rounded border text-start transition-colors focus:outline-0',
            // Modo form: casa com a altura/padding/fonte dos demais inputs (form-input: h-9.25, px-3, text-sm).
            'h-9.25 px-3 text-sm' => $mode === 'form',
            // Modo pg-filter (filtros de tabela): compacto.
            'min-h-[1.875rem] px-2 text-[0.8125rem]' => $mode !== 'form',
            'border-default-300 hover:border-default-400 focus:border-default-500' => ! $invalid,
            'border-danger!' => $invalid,
            $triggerClass,
        ])
    >
        <span id="{{ $valueId }}" class="truncate" :class="vazio && 'text-default-400'" x-text="resumo"></span>

        <span class="flex shrink-0 items-center gap-0.5">
            <span
                x-show="clearable && !vazio"
                x-cloak
                @click.stop="limpar();"
                @keydown.enter.stop.prevent="limpar();"
                @keydown.space.stop.prevent="limpar();"
                role="button"
                tabindex="0"
                aria-label="Limpar seleção"
                class="text-default-400 hover:text-default-700 hover:bg-light flex h-4 w-4 items-center justify-center rounded-full transition-colors"
            >
                <i class="iconify tabler--x text-[0.8rem]" aria-hidden="true"></i>
            </span>
            <i class="iconify tabler--chevron-down text-default-400 text-sm" aria-hidden="true"></i>
        </span>
    </button>

    {{-- Painel flutuante (position:fixed calculado no JS para escapar do
         overflow-x-auto da tabela; z acima dos dropdowns de header). --}}
    <div
        x-show="open"
        x-cloak
        x-ref="painel"
        id="{{ $panelId }}"
        data-testid="combobox-panel"
        @if ($field !== null) data-combobox-field="{{ $field }}" @endif
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.outside="fechar();"
        :style="estiloPainel"
        class="bg-card border-default-300 fixed z-[1080] max-w-[min(22rem,90vw)] overflow-hidden rounded border shadow-lg motion-reduce:transition-none"
    >
        @if ($searchable)
            <div class="border-light relative border-b p-2">
                <i
                    class="iconify tabler--search text-default-400 pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2"
                    aria-hidden="true"
                ></i>
                <input
                    type="text"
                    x-ref="busca"
                    x-model="search"
                    @keydown="aoTeclar($event);"
                    data-testid="combobox-search"
                    placeholder="{{ $searchPlaceholder }}"
                    class="form-input form-input-sm w-full !ps-8"
                    autocomplete="off"
                />
            </div>
        @endif

        <ul role="listbox" :aria-multiselectable="multiple ? 'true' : 'false'" class="max-h-64 overflow-y-auto py-1">
            <template x-for="(op, i) in opcoesFiltradas" :key="op.value">
                <li>
                    {{-- Cabecalho de grupo (optgroup), so na 1a opcao do grupo. --}}
                    <div
                        x-show="primeiroDoGrupo(i)"
                        class="text-default-400 px-2.5 pt-2 pb-1 text-[0.7rem] font-semibold tracking-wide uppercase"
                        x-text="op.group"
                    ></div>

                    <div
                        role="option"
                        data-testid="combobox-option"
                        :data-value="op.value"
                        :aria-selected="estaSelecionado(op.value) ? 'true' : 'false'"
                        :aria-disabled="op.disabled ? 'true' : 'false'"
                        @click="selecionar(op.value);"
                        @mousemove="activeIndex = i;"
                        class="text-body-color flex cursor-pointer items-center gap-2 px-2.5 py-1.5 text-[0.8125rem]"
                        :class="{
                            'bg-light': i === activeIndex,
                            'cursor-not-allowed opacity-50': op.disabled,
                        }"
                    >
                        @if ($multiple)
                            {{-- rounded-[4px] fixo: o radius default do projeto (10px)
                                 deixaria o checkbox de 16px quase circular (parece radio). --}}
                            <span
                                class="flex size-4 shrink-0 items-center justify-center rounded-[4px] border transition-colors"
                                :class="estaSelecionado(op.value)
                                    ? 'bg-primary border-primary text-white'
                                    : 'border-default-400'"
                            >
                                <i
                                    x-show="estaSelecionado(op.value)"
                                    class="iconify tabler--check text-[0.7rem]"
                                    aria-hidden="true"
                                ></i>
                            </span>
                            <span class="truncate" x-text="op.label"></span>
                        @else
                            <span class="truncate" x-text="op.label"></span>
                            <i
                                x-show="estaSelecionado(op.value)"
                                class="iconify tabler--check text-primary ms-auto shrink-0 text-sm"
                                aria-hidden="true"
                            ></i>
                        @endif
                    </div>
                </li>
            </template>

            <li x-show="opcoesFiltradas.length === 0" class="text-default-400 px-2.5 py-3 text-center text-[0.8125rem]">
                Nenhum resultado encontrado
            </li>
        </ul>
    </div>
</div>
