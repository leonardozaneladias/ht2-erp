@props ([
    'id' => null,
    'columns' => [],
    'searchable' => true,
    'exportable' => false,
    'selectable' => false,
    'columnSearch' => false,
    'dateRange' => false,
    'responsive' => true,
    'perPage' => 25,
    'striped' => true,
    'compact' => false,
    'emptyMessage' => 'Nenhum registro encontrado.',
    'selectionLabel' => 'itens selecionados',
])

@php
    $tableId = $id ?: 'dt-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $normalizedColumns = collect($columns)->map(function ($column) {
        if (is_string($column)) {
            return [
                'label' => $column,
                'searchable' => true,
                'orderable' => true,
                'type' => null,
                'className' => null,
                'headerClass' => null,
            ];
        }

        return [
            'label' => $column['label'] ?? '',
            'searchable' => (bool) ($column['searchable'] ?? true),
            'orderable' => (bool) ($column['orderable'] ?? true),
            'type' => $column['type'] ?? null,
            'className' => $column['class'] ?? $column['className'] ?? null,
            'headerClass' => $column['headerClass'] ?? null,
        ];
    })->values();

    $actualColumns = $selectable
        ? collect([[
            'label' => 'Selecionar',
            'searchable' => false,
            'orderable' => false,
            'type' => 'selection',
            'className' => 'w-12',
            'headerClass' => 'w-12',
        ]])->merge($normalizedColumns)->values()
        : $normalizedColumns;

    $dateRangeColumn = $dateRange
        ? $actualColumns->search(fn ($column) => ($column['type'] ?? null) === 'date')
        : false;

    $config = [
        'id' => $tableId,
        'columns' => $actualColumns->map(fn ($column) => [
            'label' => $column['label'],
            'searchable' => $column['searchable'],
            'orderable' => $column['orderable'],
            'type' => $column['type'],
            'className' => $column['className'],
        ])->all(),
        'searchable' => (bool) $searchable,
        'exportable' => (bool) $exportable,
        'selectable' => (bool) $selectable,
        'columnSearch' => (bool) $columnSearch,
        'responsive' => (bool) $responsive,
        'perPage' => (int) $perPage,
        'dateRange' => (bool) $dateRange && $dateRangeColumn !== false,
        'dateRangeColumn' => $dateRangeColumn !== false ? (int) $dateRangeColumn : null,
    ];

    $hasRows = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();
    $flatpickrToolbarConfig = ['dateFormat' => 'd/m/Y'];
    $tableClasses = [
        'table w-full align-middle',
        'table-striped' => $striped,
        'text-sm' => $compact,
    ];
@endphp

<div {{ $attributes->class(['space-y-4']) }} data-af-datatable="{{ \Illuminate\Support\Js::encode($config) }}">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            @isset ($toolbar)
                {{ $toolbar }}
            @endisset

            @if ($config['dateRange'])
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        placeholder="De"
                        class="form-input min-w-32"
                        data-af-flatpickr="{{ \Illuminate\Support\Js::encode($flatpickrToolbarConfig) }}"
                        data-af-datatable-range-start
                    />
                    <input
                        type="text"
                        placeholder="Até"
                        class="form-input min-w-32"
                        data-af-flatpickr="{{ \Illuminate\Support\Js::encode($flatpickrToolbarConfig) }}"
                        data-af-datatable-range-end
                    />
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center xl:justify-end">
            @if ($searchable)
                <div class="input-icon-group min-w-0 sm:min-w-72">
                    <i class="iconify tabler--search input-icon"></i>
                    <input
                        type="search"
                        class="form-input"
                        placeholder="Buscar na tabela..."
                        data-af-datatable-search
                    />
                </div>
            @endif

            @if ($exportable)
                <div class="flex flex-wrap items-center gap-2" data-af-datatable-export-buttons></div>
            @endif
        </div>
    </div>

    @if ($selectable && isset($bulkActions))
        <div
            class="border-primary/25 bg-primary/8 hidden flex-col gap-3 rounded-xl border p-4 md:flex-row md:items-center md:justify-between"
            data-af-datatable-bulk
        >
            <p class="text-body-color text-sm font-medium">
                <span data-af-datatable-selected-count>0</span> {{ $selectionLabel }}
            </p>

            <div class="flex flex-wrap items-center gap-2">{{ $bulkActions }}</div>
        </div>
    @endif

    <div class="border-default-300 bg-card overflow-x-auto rounded-xl border shadow-sm">
        <table id="{{ $tableId }}" {{ $attributes->except('class')->class($tableClasses) }} data-af-datatable-table>
            <thead class="bg-light/40">
                <tr>
                    @foreach ($actualColumns as $index => $column)
                        <th @class ([$column['headerClass'], 'text-start'])>
                            @if ($column['type'] === 'selection')
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        class="form-checkbox form-checkbox-light size-4.5"
                                        aria-label="Selecionar linhas visíveis"
                                        data-af-datatable-select-all
                                    />
                                </label>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>

                @if ($columnSearch)
                    <tr class="bg-light/60">
                        @foreach ($actualColumns as $index => $column)
                            <th @class ([$column['headerClass'], 'text-start'])>
                                @if (($column['searchable'] ?? false) && $column['type'] !== 'selection')
                                    <input
                                        type="search"
                                        class="form-input"
                                        placeholder="Filtrar..."
                                        data-af-datatable-column-search="{{ $index }}"
                                    />
                                @endif
                            </th>
                        @endforeach
                    </tr>
                @endif
            </thead>

            <tbody>
                @if ($hasRows)
                    {{ $slot }}
                @else
                    <tr>
                        <td
                            colspan="{{ $actualColumns->count() }}"
                            class="text-default-400 px-4 py-10 text-center text-sm"
                        >
                            @isset ($empty)
                                {{ $empty }}
                            @else
                                {{ $emptyMessage }}
                            @endisset
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
