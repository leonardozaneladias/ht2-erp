# Select Search (Choices.js)

**Categoria:** Form  
**Origem Inspinia:** `resources/views/form/select.blade.php`  
**Plugins JS:** Choices.js 11.1.0  
**Plugins CSS:** Choices.js CSS + classes base de form do projeto

---

## Descrição

Wrapper oficial de select com busca. O componente suporta seleção simples, múltipla, grupos (`optgroup`), placeholder, criação opcional de itens e sincronização com Livewire porque o `<select>` nativo continua sendo a fonte de verdade.

`x-shared.tags-input` foi consolidado como wrapper semântico sobre esta mesma base.

---

## API Final

**Nome:** `<x-shared.select-search>`  
**Arquivo:** `resources/views/components/shared/select-search.blade.php`

### Props

| Prop          | Tipo      | Default        | Descrição                 |
| ------------- | --------- | -------------- | ------------------------- | --------------------------- |
| `name`        | `string`  | —              | Nome do campo             |
| `id`          | `?string` | `null`         | Id customizado            |
| `label`       | `?string` | `null`         | Label visível             |
| `options`     | `array    | null`          | `null`                    | Opções simples ou agrupadas |
| `value`       | `mixed`   | `null`         | Valor inicial             |
| `placeholder` | `?string` | `Selecione...` | Placeholder               |
| `hint`        | `?string` | `null`         | Texto de ajuda            |
| `required`    | `bool`    | `false`        | Campo obrigatório         |
| `multiple`    | `bool`    | `false`        | Seleção múltipla          |
| `searchable`  | `bool`    | `true`         | Habilita busca            |
| `removeItem`  | `bool`    | `true`         | Exibe botão de remoção    |
| `allowCreate` | `bool`    | `false`        | Permite criar itens novos |
| `maxItems`    | `?int`    | `null`         | Limite de itens           |
| `shouldSort`  | `bool`    | `false`        | Ordenação do Choices      |

---

## Código Final Blade

```blade
@props ([
    'name',
    'id' => null,
    'label' => null,
    'options' => null,
    'value' => null,
    'placeholder' => 'Selecione...',
    'hint' => null,
    'required' => false,
    'multiple' => false,
    'searchable' => true,
    'removeItem' => true,
    'allowCreate' => false,
    'maxItems' => null,
    'shouldSort' => false,
])

@php
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $config = [
        'multiple' => $multiple,
        'searchable' => $searchable,
        'removeItem' => $removeItem,
        'allowCreate' => $allowCreate,
        'maxItems' => $maxItems,
        'shouldSort' => $shouldSort,
        'placeholder' => $placeholder,
    ];
@endphp

<div class="mb-4" wire:ignore>
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif

    <select
        id="{{ $fieldId }}"
        name="{{ $multiple ? str_replace('[]', '', $name).'[]' : str_replace('[]', '', $name) }}"
        data-af-choices='@json($config)'
        @if ($multiple) multiple @endif
        {{ $attributes->class(['form-input']) }}
    >
        {{ $slot ?? '' }}
    </select>
</div>
```

---

## Dependências JS

- `resources/js/admin/forms.js`
- seletor de boot: `[data-af-choices]`
- biblioteca: `Choices`

---

## Exemplos de Uso

```blade
<x-shared.select-search
    name="cliente_id"
    label="Cliente"
    :options="$clientes"
    wire:model.live="form.cliente_id"
    required
/>
```

```blade
<x-shared.select-search
    name="produto_id"
    label="Produto"
    :options="$produtosAgrupados"
    placeholder="Selecione um produto"
/>
```

```blade
<x-shared.select-search
    name="modalidades"
    label="Modalidades"
    :options="$modalidades"
    :value="['presencial', 'ead']"
    multiple
    remove-item
/>
```

---

## Notas de Implementação

1. O boot oficial não usa mais `data-choices` do template original; a API real do projeto é `data-af-choices`.
2. O componente aceita arrays simples e arrays agrupados, inclusive estruturas com chave `options`.
3. O wrapper inteiro não fica mais em `wire:ignore`; a integração oficial reconcilia o Choices após mutações do DOM, preservando erros, opções e estado vindos do Livewire.
4. Para UX de chips/tags, preferir `x-shared.tags-input`; para relacionamento/search, usar `x-shared.select-search`.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Média        |
| **Status**       | 🟢 Concluído |
