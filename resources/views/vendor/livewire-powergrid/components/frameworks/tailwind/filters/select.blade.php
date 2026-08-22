{{--
    Override do filtro SELECT do PowerGrid (vendor v6.10.3).
    Renderiza o x-shared.combobox (single, pesquisável) sobre o <select> nativo
    oculto, mantendo o binding do pacote (wire:model + wire:input.live ->
    filterSelect). A opção vazia ("Todos") é o estado sem filtro (placeholder).
--}}

@props([
    'class' => '',
    'column' => null,
    'inline' => null,
    'filter' => null,
    'options' => [],
])

@php
    $field = data_get($filter, 'field');
    $title = data_get($column, 'title');

    $defaultAttributes = \PowerComponents\LivewirePowerGrid\Components\Filters\FilterSelect::getWireAttributes(
        $field,
        $title,
    );

    $params = array_merge([...data_get($filter, 'attributes'), ...$defaultAttributes], $filter);

    $computedDatasource = data_get($filter, 'computedDatasource');
    $dataSource = filled($computedDatasource) ? $this->{$computedDatasource} : data_get($filter, 'dataSource');

    // Nome acessivel do gatilho (WCAG 4.1.2). O id do <label>/rotulo bate com o
    // id que o combobox geraria internamente ($comboboxId . '-label'), entao no
    // modo nao-inline o <label> visivel nomeia o gatilho via labelId, e no modo
    // inline (sem <label>) o combobox gera um rotulo sr-only com o mesmo id.
    $comboboxId = "filtro-select-{$tableName}-{$field}";
@endphp

@if ($params['component'])
    @unset($params['attributes'])

    <x-dynamic-component
        :component="$params['component']"
        :attributes="new \Illuminate\View\ComponentAttributeBag($params)"
    />
@else
    <div @class([theme_style($theme, 'filterSelect.base'), 'space-y-1' => !$inline])>
        @unless ($inline)
            <label id="{{ $comboboxId }}-label" class="text-pg-primary-700 dark:text-pg-primary-300 block text-sm font-semibold">
                {{ $title }}
            </label>
        @endunless

        <x-shared.combobox
            mode="form"
            placeholder="{{ trans('livewire-powergrid::datatable.select.all') }}"
            label="{{ $title }}"
            :label-id="$inline ? null : $comboboxId . '-label'"
            id="{{ $comboboxId }}"
        >
            <select x-ref="native" class="sr-only" tabindex="-1" aria-hidden="true" {{ $defaultAttributes['selectAttributes'] }}>
                @unless (data_get($params, 'params.disableOptionAll', false))
                    <option value="">{{ trans('livewire-powergrid::datatable.select.all') }}</option>
                @endunless

                @foreach ($dataSource as $key => $item)
                    <option wire:key="select-{{ $tableName }}-{{ $key }}" value="{{ $item[data_get($filter, 'optionValue')] }}">
                        {{ $item[data_get($filter, 'optionLabel')] }}
                    </option>
                @endforeach
            </select>
        </x-shared.combobox>
    </div>
@endif
