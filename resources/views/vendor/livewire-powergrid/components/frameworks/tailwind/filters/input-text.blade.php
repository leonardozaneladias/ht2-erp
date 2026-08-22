{{--
    Override do filtro INPUT-TEXT do PowerGrid (vendor v6.10.3).
    O <select> de OPERADOR ("Contém", "Não contém", "É exatamente"…) passa a ser
    o x-shared.combobox (single, pesquisável). O campo de texto continua igual.
    Mantém todos os bindings do pacote (filterInputText / filterInputTextOptions).
--}}

@props([
    'enabledFilters' => [],
    'column' => null,
    'inline' => null,
    'filter' => null,
])

<div>
    @php
        $fieldClassName = data_get($filter, 'className');

        $field = strval(data_get($filter, 'field'));
        $title = strval(data_get($column, 'title'));
        $operators = (array) data_get($filter, 'operators', []);
        $placeholder = strval(data_get($filter, 'placeholder'));
        $componentAttributes = (array) data_get($filter, 'attributes', []);

        $inputTextOptions = $fieldClassName::getInputTextOperators();
        $inputTextOptions = count($operators) > 0 ? $operators : $inputTextOptions;
        $showSelectOptions = !(count($inputTextOptions) === 1 && in_array('contains', $inputTextOptions));

        $defaultPlaceholder = data_get($column, 'placeholder') ?: data_get($column, 'title');
        $overridePlaceholder = $placeholder ?: $defaultPlaceholder;

        unset($filter['placeholder']);

        $defaultAttributes = $fieldClassName::getWireAttributes($field, $title);

        $inputClasses = theme_style($theme, 'filterInputText.input');

        // Nomes acessiveis (WCAG 4.1.2). Sao DOIS controles focaveis: o <input> do
        // valor e o combobox de OPERADOR. No modo nao-inline o <label> visivel nomeia
        // o <input> (via for/id); no modo inline o <input> recebe aria-label. O
        // operador sempre tem rotulo proprio (distinto do valor) via `label` do
        // combobox, que gera um rotulo sr-only interno e o referencia no gatilho.
        $inputId = "filtro-input-text-{$tableName}-{$field}";
        $operatorLabel = trans('livewire-powergrid::datatable.labels.filter_operator', ['column' => $title]);

        $params = array_merge(
            [
                'showSelectOptions' => $showSelectOptions,
                'placeholder' => ($placeholder = $componentAttributes['placeholder'] ?? $overridePlaceholder),
                ...data_get($filter, 'attributes'),
                ...$defaultAttributes,
            ],
            $filter,
        );
    @endphp

    @if ($params['component'])
        @unset($params['operators'], $params['attributes'])

        <x-dynamic-component
            :component="$params['component']"
            :attributes="new \Illuminate\View\ComponentAttributeBag($params)"
        />
    @else
        <div @class([theme_style($theme, 'filterInputText.base'), 'space-y-1' => !$inline])>
            @unless ($inline)
                <label for="{{ $inputId }}" class="text-pg-primary-700 dark:text-pg-primary-300 block text-sm font-semibold">
                    {{ $title }}
                </label>
            @endunless

            <div @class([
                'w-full space-y-2 sm:flex sm:space-y-0' => !$inline && $showSelectOptions,
                'flex flex-col space-y-1.5' => $inline && $showSelectOptions,
            ])>
                @if ($showSelectOptions)
                    <div @class(['pl-0 w-full sm:pr-3 sm:w-1/2' => !$inline])>
                        <x-shared.combobox
                            mode="form"
                            :clearable="false"
                            label="{{ $operatorLabel }}"
                            id="filtro-operador-{{ $tableName }}-{{ $field }}"
                        >
                            <select
                                x-ref="native"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                                data-cy="input_text_options_{{ $tableName }}_{{ $field }}"
                                {{ $defaultAttributes['selectAttributes'] }}
                            >
                                @foreach ($inputTextOptions as $key => $value)
                                    <option
                                        wire:key="input-text-options-{{ $tableName }}-{{ $key . '-' . $value }}"
                                        value="{{ $value }}"
                                    >{{ trans('livewire-powergrid::datatable.input_text_options.' . $value) }}</option>
                                @endforeach
                            </select>
                        </x-shared.combobox>
                    </div>
                @endif
                <div @class(['pl-0 w-full sm:w-1/2' => !$inline && $showSelectOptions])>
                    <input
                        id="{{ $inputId }}"
                        @if ($inline) aria-label="{{ $title }}" @endif
                        data-cy="input_text_{{ $tableName }}_{{ $field }}"
                        wire:key="input-{{ $field }}"
                        data-id="{{ $field }}"
                        @if (isset($enabledFilters[$field]['disabled']) && boolval($enabledFilters[$field]['disabled']) === true) disabled
                        @else
                            {{ $defaultAttributes['inputAttributes'] }} @endif
                        type="text"
                        class="{{ $inputClasses }}"
                        placeholder="{{ $placeholder }}"
                    />
                </div>
            </div>
        </div>
    @endif
</div>
