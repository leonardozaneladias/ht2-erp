{{--
    Override do filtro multi-select do PowerGrid (vendor: power-components/
    livewire-powergrid v6.10.3 — components/inputs/select.blade.php).

    Troca a apresentacao do TomSelect pelo x-shared.combobox (gatilho compacto +
    busca + checkbox, 100% pt-BR). NAO mexe no pipeline de filtros: o combobox em
    modo pg-filter despacha `pg:multiSelect-{tableName}` (mesmo contrato que o
    storeMultiSelect do pacote), tratado por Filter::multiSelectChanged().

    Mantemos `wire:ignore` para o Livewire nao morfar o componente Alpine. O
    `<select>` nativo do vendor e dispensavel aqui (o combobox usa as opcoes via
    JSON e o estado vive no Alpine), por isso nao e renderizado.

    Ao subir o PowerGrid, reconferir esta view contra a do vendor. Rede de
    seguranca: tests/Browser/Admin/FiltroMultiSelectTest.php.

    `asyncData` (busca remota) NAO e suportado neste override (nenhum filtro do
    projeto usa). Se algum passar a usar, tratar aqui um segundo caminho.
--}}

@props([
    'inline' => true,
    'filter' => null,
    'tableName' => null,
    'multiple' => true,
    'initialValues' => [],
    'options' => [],
    'title' => '',
    'theme' => null,
])

@php
    $field = data_get($filter, 'field');
    $optionValue = data_get($filter, 'optionValue');
    $optionLabel = data_get($filter, 'optionLabel');

    $rawCollection = collect(data_get($filter, 'dataSource') ?? data_get($filter, 'computedDatasource'));

    $isGrouped =
        $rawCollection->first() &&
        is_array($rawCollection->first()) &&
        array_key_exists('options', $rawCollection->first());

    // Achata a fonte do PowerGrid no formato que o combobox espera:
    // [{ value, label, group? }] — preservando optgroup quando houver.
    $comboboxOptions = $isGrouped
        ? $rawCollection
            ->flatMap(
                fn ($group) => collect(data_get($group, 'options', []))->map(fn ($item) => [
                    'value' => data_get($item, $optionValue),
                    'label' => data_get($item, $optionLabel),
                    'group' => data_get($group, 'label'),
                ]),
            )
            ->values()
            ->all()
        : $rawCollection
            ->map(fn ($item) => [
                'value' => data_get($item, $optionValue),
                'label' => data_get($item, $optionLabel),
            ])
            ->values()
            ->all();
@endphp

@if (filled($filter))
    {{-- wire:ignore: o estado vive no Alpine; o PowerGrid e avisado via dispatch. --}}
    <div wire:ignore class="{{ theme_style($theme, 'filterMultiSelect.base') }}">
        @unless ($inline)
            <label class="text-pg-primary-700 dark:text-pg-primary-300 block text-sm font-semibold">
                {{ $title }}
            </label>
        @endunless

        <x-shared.combobox
            mode="pg-filter"
            :field="$field"
            :table-name="$tableName"
            :label="$title"
            :multiple="$multiple"
            :options="$comboboxOptions"
            :initial="$initialValues"
        />
    </div>
@endif
