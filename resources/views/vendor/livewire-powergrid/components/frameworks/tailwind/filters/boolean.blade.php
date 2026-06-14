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
            <label class="text-pg-primary-700 dark:text-pg-primary-300 block text-sm font-semibold">
                {{ $title }}
            </label>
        @endunless

        <x-shared.combobox
            mode="form"
            empty-value="all"
            placeholder="{{ trans('livewire-powergrid::datatable.boolean_filter.all') }}"
            id="filtro-boolean-{{ $tableName }}-{{ $field }}"
        >
            <select x-ref="native" class="sr-only" tabindex="-1" aria-hidden="true" {{ $defaultAttributes['selectAttributes'] }}>
                <option value="all">{{ trans('livewire-powergrid::datatable.boolean_filter.all') }}</option>
                <option value="true">{{ $trueLabel }}</option>
                <option value="false">{{ $falseLabel }}</option>
            </select>
        </x-shared.combobox>
    </div>
@endif
