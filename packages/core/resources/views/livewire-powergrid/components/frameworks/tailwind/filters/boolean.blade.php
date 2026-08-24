{{--
    Override do filtro BOOLEAN do PowerGrid (vendor v6.10.3).
    Renderiza o x-shared.combobox (single, pesquisável) sobre o <select> nativo
    oculto, que mantém o binding do pacote (wire:model + wire:input.live ->
    filterBoolean). emptyValue="all" => "Todos" é o estado sem filtro.
--}}

@props([
    'column' => null,
    'class' => '',
    'inline' => null,
    'filter' => null,
])

@php
    $fieldClassName = data_get($filter, 'className');
    $field = data_get($filter, 'field');
    $title = data_get($column, 'title');

    $trueLabel = data_get($filter, 'trueLabel');
    $falseLabel = data_get($filter, 'falseLabel');

    $defaultAttributes = $fieldClassName::getWireAttributes($field, $title);

    $params = array_merge([...data_get($filter, 'attributes'), ...$defaultAttributes], $filter);

    // Nome acessivel do gatilho (WCAG 4.1.2). O id do <label>/rotulo bate com o
    // id que o combobox geraria internamente ($comboboxId . '-label'), entao no
    // modo nao-inline o <label> visivel nomeia o gatilho via labelId, e no modo
    // inline (sem <label>) o combobox gera um rotulo sr-only com o mesmo id.
    $comboboxId = "filtro-boolean-{$tableName}-{$field}";
@endphp

@if ($params['component'])
    @unset($params['attributes'])

    <x-dynamic-component
        :component="$params['component']"
        :attributes="new \Illuminate\View\ComponentAttributeBag($params)"
    />
@else
    <div @class([theme_style($theme, 'filterBoolean.base'), 'space-y-1' => !$inline])>
        @unless ($inline)
            <label id="{{ $comboboxId }}-label" class="text-pg-primary-700 dark:text-pg-primary-300 block text-sm font-semibold">
                {{ $title }}
            </label>
        @endunless

        <x-shared.combobox
            mode="form"
            empty-value="all"
            placeholder="{{ trans('livewire-powergrid::datatable.boolean_filter.all') }}"
            label="{{ $title }}"
            :label-id="$inline ? null : $comboboxId . '-label'"
            id="{{ $comboboxId }}"
        >
            <select x-ref="native" class="sr-only" tabindex="-1" aria-hidden="true" {{ $defaultAttributes['selectAttributes'] }}>
                <option value="all">{{ trans('livewire-powergrid::datatable.boolean_filter.all') }}</option>
                <option value="true">{{ $trueLabel }}</option>
                <option value="false">{{ $falseLabel }}</option>
            </select>
        </x-shared.combobox>
    </div>
@endif
