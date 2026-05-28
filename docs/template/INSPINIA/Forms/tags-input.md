# Tags Input

**Categoria:** Form  
**Origem Inspinia:** derivado de `select.blade.php` via Choices.js  
**Plugins JS:** Choices.js 11.1.0

---

## Descrição

`x-shared.tags-input` é o wrapper semântico oficial para listas múltiplas em formato chips/tags. Ele reutiliza integralmente o motor de `x-shared.select-search`, mas expõe uma API mais clara para casos como dias de vencimento, lembretes, marcadores e listas parametrizáveis do admin.

Não há dependência nova: a decisão oficial foi preservar Choices.js e não introduzir Tagify.

---

## API Final

**Nome:** `<x-shared.tags-input>`  
**Arquivo:** `resources/views/components/shared/tags-input.blade.php`

### Props

| Prop          | Tipo      | Default             | Descrição                 |
| ------------- | --------- | ------------------- | ------------------------- | ------------------ |
| `name`        | `string`  | —                   | Nome do campo             |
| `id`          | `?string` | `null`              | Id customizado            |
| `label`       | `?string` | `null`              | Label visível             |
| `options`     | `array    | null`               | `null`                    | Opções disponíveis |
| `value`       | `array`   | `[]`                | Valores iniciais          |
| `placeholder` | `string`  | `Adicionar item...` | Placeholder               |
| `hint`        | `?string` | `null`              | Texto de ajuda            |
| `required`    | `bool`    | `false`             | Campo obrigatório         |
| `searchable`  | `bool`    | `false`             | Busca opcional            |
| `allowCreate` | `bool`    | `false`             | Permite criar tags livres |
| `maxItems`    | `?int`    | `null`              | Limite de itens           |

---

## Código Final Blade

```blade
@props ([
    'name',
    'id' => null,
    'label' => null,
    'options' => null,
    'value' => [],
    'placeholder' => 'Adicionar item...',
    'hint' => null,
    'required' => false,
    'searchable' => false,
    'allowCreate' => false,
    'maxItems' => null,
])

<x-shared.select-search
    :name="$name"
    :id="$id"
    :label="$label"
    :options="$options"
    :value="$value"
    :placeholder="$placeholder"
    :hint="$hint"
    :required="$required"
    :searchable="$searchable"
    :allow-create="$allowCreate"
    :max-items="$maxItems"
    multiple
    remove-item
    {{ $attributes }}
>
    {{ $slot ?? '' }}
</x-shared.select-search>
```

---

## Exemplos de Uso

```blade
<x-shared.tags-input
    name="dias_vencimento"
    label="Dias de vencimento"
    :options="array_combine(range(1, 31), range(1, 31))"
    :value="[5, 10, 15]"
/>
```

```blade
<x-shared.tags-input name="marcadores" label="Marcadores" :value="['vip', 'renovacao']" allow-create searchable />
```

---

## Notas de Implementação

1. É um wrapper semântico; a implementação visual continua vindo de `x-shared.select-search`.
2. O modo padrão é `multiple` com `remove-item`.
3. Para listas longas com busca principal, usar `x-shared.select-search`; para chips/tags, usar `x-shared.tags-input`.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2           |
| **Complexidade** | Baixa        |
| **Status**       | 🟢 Concluído |
