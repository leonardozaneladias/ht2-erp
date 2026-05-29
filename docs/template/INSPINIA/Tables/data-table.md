# Data Table (DataTable unificada)

**Categoria:** Table
**Origem Inspinia:** `resources/views/tables/datatables/*.blade.php` (15 variantes consolidadas)
**Plugins JS:** DataTables.net 2.3.3 + plugins (buttons, export, checkbox-select, fixed-columns, fixed-header, responsive, keytable)
**Plugins CSS:** DataTables CSS + classe `.table` do Inspinia

---

## Descrição

**Tabela única consolidada** com todas as features do DataTables.net ativáveis via props: busca, export (CSV/Excel/PDF/Print), seleção múltipla, column search, range search, responsividade, etc. Em vez de 15 componentes separados (como os 15 showcases do Inspinia), criamos **um** `<x-admin.data-table>` rico em props.

> **Alternativa Livewire:** para tabelas < 500 linhas, considerar tabela pura Livewire com Alpine (mais leve, sem jQuery). Porém DataTables ainda é padrão para CRUDs grandes.

---

## Código Original (Inspinia — 15 variantes)

O Inspinia tem 15 arquivos showcase. Todos seguem o padrão:

```html
<table class="table-bordered table" id="datatable-basic">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        {{-- rows --}}
    </tbody>
</table>
```

```js
$('#datatable-basic').DataTable({
    language: { url: '/datatables/pt-BR.json' },
    buttons: ['csv', 'excel', 'pdf', 'print'],
    dom: 'Bfrtip',
});
```

---

## Componente Blade Proposto

**Nome:** `<x-admin.data-table>`
**Arquivo:** `resources/views/components/admin/data-table.blade.php`
**Tipo:** Blade anônimo + helper JS (`resources/js/admin/data-table.js`)

### Props

| Prop           | Tipo     | Default | Descrição                                                          |
| -------------- | -------- | ------- | ------------------------------------------------------------------ |
| `id`           | `string` | auto    | ID único da tabela                                                 |
| `columns`      | `array`  | —       | `[['label' => 'Nome', 'searchable' => true, 'orderable' => true]]` |
| `searchable`   | `bool`   | `true`  | Busca global                                                       |
| `exportable`   | `bool`   | `false` | Botões CSV/Excel/PDF                                               |
| `selectable`   | `bool`   | `false` | Checkbox de seleção múltipla                                       |
| `columnSearch` | `bool`   | `false` | Busca por coluna no header                                         |
| `responsive`   | `bool`   | `true`  | Responsive mode                                                    |
| `perPage`      | `int`    | `25`    | Items por página                                                   |

### Código

```blade
{{-- resources/views/components/admin/data-table.blade.php --}}
@props ([
    'id' => 'dt-' . \Illuminate\Support\Str::random(6),
    'columns' => [],
    'searchable' => true,
    'exportable' => false,
    'selectable' => false,
    'columnSearch' => false,
    'responsive' => true,
    'perPage' => 25,
])

<div wire:ignore>
    <table
        id="{{ $id }}"
        class="table table-bordered w-full"
        data-datatable="true"
        data-searchable="{{ $searchable ? 'true' : 'false' }}"
        data-exportable="{{ $exportable ? 'true' : 'false' }}"
        data-selectable="{{ $selectable ? 'true' : 'false' }}"
        data-column-search="{{ $columnSearch ? 'true' : 'false' }}"
        data-responsive="{{ $responsive ? 'true' : 'false' }}"
        data-per-page="{{ $perPage }}"
    >
        <thead>
            <tr>
                @if ($selectable)
                    <th class="w-4"><input type="checkbox" class="form-checkbox" data-select-all /></th>
                @endif
                @foreach ($columns as $col)
                    <th
                        @if (($col['orderable'] ?? true) === false) data-orderable="false" @endif
                        @if (($col['searchable'] ?? true) === false) data-searchable="false" @endif
                    >
                        {{ $col['label'] }}
                    </th>
                @endforeach
            </tr>
            @if ($columnSearch)
                <tr class="column-search">
                    @if ($selectable)
                        <th></th>
                    @endif
                    @foreach ($columns as $col)
                        <th>
                            @if (($col['searchable'] ?? true) !== false)
                                <input type="text" class="form-input form-input-sm" placeholder="Filtrar..." />
                            @endif
                        </th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
```

---

## Exemplos de Uso

### Listagem com exportação e busca por coluna

```blade
<x-shared.card title="Todos os Pedidos" :body-padding="false">
    <x-admin.data-table
        :columns="[
            ['label' => 'Código', 'searchable' => true],
            ['label' => 'Cliente'],
            ['label' => 'Categoria'],
            ['label' => 'Data'],
            ['label' => 'Total', 'orderable' => false],
            ['label' => 'Itens', 'orderable' => false],
            ['label' => 'Status'],
            ['label' => 'Ações', 'orderable' => false, 'searchable' => false],
        ]"
        exportable
        column-search
    >
        @foreach ($pedidos as $pedido)
            <tr>
                <td>{{ $pedido->codigo }}</td>
                <td>{{ $pedido->cliente->nome }}</td>
                <td>{{ $pedido->categoria->nome }}</td>
                <td>{{ $pedido->created_at->format('d/m/Y') }}</td>
                <td>{{ MoneyHelper::format($pedido->total_centavos) }}</td>
                <td>{{ $pedido->itens_count }}</td>
                <td><x-shared.status-badge :enum="$pedido->status" /></td>
                <td>
                    <x-shared.dropdown placement="bottom-end">
                        <x-slot:button>
                            <button class="hs-dropdown-toggle btn btn-icon btn-sm">
                                <i class="iconify tabler--dots-vertical"></i>
                            </button>
                        </x-slot:button>
                        <x-shared.dropdown-item icon="tabler--edit" :href="route('admin.pedidos.edit', $pedido)">
                            Editar
                        </x-shared.dropdown-item>
                        <x-shared.dropdown-item icon="tabler--copy" wire:click="duplicar({{ $pedido->id }})">
                            Duplicar
                        </x-shared.dropdown-item>
                        <x-shared.dropdown-divider />
                        <x-shared.dropdown-item
                            icon="tabler--ban"
                            variant="danger"
                            wire:click="confirmarInativacao({{ $pedido->id }})"
                        >
                            Inativar
                        </x-shared.dropdown-item>
                    </x-shared.dropdown>
                </td>
            </tr>
        @endforeach
    </x-admin.data-table>
</x-shared.card>
```

### Listagem com seleção múltipla

```blade
<x-admin.data-table
    :columns="[
        ['label' => 'Cliente'],
        ['label' => 'Pedido'],
        ['label' => 'Item'],
        ['label' => 'Valor'],
        ['label' => 'Data'],
        ['label' => 'Status'],
        ['label' => 'Ações', 'orderable' => false],
    ]"
    selectable
    exportable
>
    {{-- rows --}}
</x-admin.data-table>
```

---

## Init global JavaScript

```js
// resources/js/admin/data-table-init.js
import $ from 'jquery';
import 'datatables.net-dt';
import 'datatables.net-buttons-dt';
import 'datatables.net-buttons/js/buttons.html5';
import 'datatables.net-buttons/js/buttons.print';
import 'datatables.net-responsive-dt';
import 'datatables.net-select-dt';

export function initDataTables() {
    $('table[data-datatable="true"]').each(function () {
        const $t = $(this);
        const config = {
            language: { url: '/js/datatables/pt-BR.json' },
            pageLength: parseInt($t.data('per-page') || 25),
            responsive: $t.data('responsive') === true || $t.data('responsive') === 'true',
            searching: $t.data('searchable') !== false,
        };

        if ($t.data('exportable')) {
            config.dom = 'Blfrtip';
            config.buttons = ['csv', 'excel', 'print'];
        }

        if ($t.data('selectable')) {
            config.select = { style: 'multi', selector: 'td:first-child' };
        }

        $t.DataTable(config);
    });
}

document.addEventListener('livewire:init', () => {
    initDataTables();
    Livewire.hook('morph.added', () => initDataTables());
});
```

---

## Classificação

| Critério         | Valor            |
| ---------------- | ---------------- |
| **Vai usar**     | 🟢 Sim (crítico) |
| **Complexidade** | Alta             |
| **Status**       | 🟢 Concluído     |

---

## Código Final Blade

**Arquivo:** `resources/views/components/admin/data-table.blade.php`
**Helper JS:** `resources/js/admin/data-table.js`
**Preview:** `resources/views/admin/dev/components/data-table.blade.php`

### API final consolidada

| Prop             | Tipo      | Default                         | Observação                                                                                  |
| ---------------- | --------- | ------------------------------- | ------------------------------------------------------------------------------------------- |
| `id`             | `?string` | auto                            | Gera `dt-*` quando omitido                                                                  |
| `columns`        | `array`   | `[]`                            | Array de colunas com `label`, `searchable`, `orderable`, `type`, `className`, `headerClass` |
| `searchable`     | `bool`    | `true`                          | Busca global externa                                                                        |
| `exportable`     | `bool`    | `false`                         | Botões CSV / Excel / PDF / Print                                                            |
| `selectable`     | `bool`    | `false`                         | Adiciona coluna sintética de seleção e toolbar de bulk actions                              |
| `columnSearch`   | `bool`    | `false`                         | Segunda linha de header com filtros por coluna                                              |
| `dateRange`      | `bool`    | `false`                         | Filtro por intervalo usando a primeira coluna marcada como `type => 'date'`                 |
| `responsive`     | `bool`    | `true`                          | Liga plugin responsive do DataTables                                                        |
| `perPage`        | `int`     | `25`                            | Page length inicial                                                                         |
| `striped`        | `bool`    | `true`                          | Alternância visual das linhas                                                               |
| `compact`        | `bool`    | `false`                         | Densidade menor                                                                             |
| `emptyMessage`   | `string`  | `"Nenhum registro encontrado."` | Fallback quando não há rows                                                                 |
| `selectionLabel` | `string`  | `"itens selecionados"`          | Texto da toolbar de seleção                                                                 |

### Slots finais

| Slot          | Uso                                  |
| ------------- | ------------------------------------ |
| default       | `<tr>` reais da tabela               |
| `toolbar`     | Ações/filtros extras acima da tabela |
| `bulkActions` | Ações em lote quando `selectable`    |
| `empty`       | Estado vazio customizado             |

### Código

```blade
<x-admin.data-table
    :columns="[
        ['label' => 'Código'],
        ['label' => 'Cliente'],
        ['label' => 'Categoria'],
        ['label' => 'Data', 'type' => 'date'],
        ['label' => 'Status', 'searchable' => false],
        ['label' => 'Ações', 'orderable' => false, 'searchable' => false],
    ]"
    exportable
    column-search
    date-range
>
    <x-slot:toolbar>
        <x-shared.badge variant="info" solid>4 pedidos</x-shared.badge>
    </x-slot:toolbar>

    @foreach ($pedidos as $pedido)
        <tr>
            <td>{{ $pedido->codigo }}</td>
            <td>{{ $pedido->cliente->nome }}</td>
            <td>{{ $pedido->categoria->nome }}</td>
            <td>{{ $pedido->created_at->format('d/m/Y') }}</td>
            <td><x-shared.status-badge :enum="$pedido->status" /></td>
            <td>
                <x-shared.dropdown placement="bottom-end">
                    <x-slot:button>
                        <button type="button" class="hs-dropdown-toggle btn btn-icon btn-sm bg-light text-dark">
                            <i class="iconify tabler--dots-vertical text-base"></i>
                        </button>
                    </x-slot:button>

                    <x-shared.dropdown-item icon="tabler--edit">Editar</x-shared.dropdown-item>
                    <x-shared.dropdown-item icon="tabler--copy">Duplicar</x-shared.dropdown-item>
                </x-shared.dropdown>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
```

---

## Notas de Adaptação

1. **Componente único** em vez de 15 arquivos: busca, exportação, seleção em massa e filtros vivem no mesmo contrato Blade
2. **Sem componente autônomo para actions/filtros/export**: `dropdown`, `drawer` e toolbar/slots cobrem o que o lote precisa
3. **Bridge JS própria** em `resources/js/admin/data-table.js`, com reinit por `MutationObserver` e `livewire:navigated`
4. **Localização pt-BR inline** no helper JS, sem depender de arquivo externo em `public/`
5. **`dateRange`** filtra pela primeira coluna marcada como `type => 'date'`, evitando configuração duplicada no preview e nas telas reais
6. **Seleção em lote** usa checkboxes reais no slot das linhas (`data-datatable-row-select`) e expõe `datatable-selection-change`
7. **Export PDF/Excel** depende de `pdfmake` + `jszip`, já integrados ao build
8. **Fora do escopo principal**: ajax, child-rows, fixed-columns, fixed-header e variantes muito específicas continuam fora da API principal
