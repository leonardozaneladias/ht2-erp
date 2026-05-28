# Money Input (BRL — Centavos)

**Categoria:** Form (masked)  
**Origem Inspinia:** `resources/views/form/other-plugin.blade.php`  
**Plugins JS:** Inputmask 5.0.9

---

## Descrição

Campo monetário oficial do projeto para valores em reais na UI e persistência em centavos no backend. A decisão do projeto permanece: dinheiro não deve trafegar nem persistir como float.

---

## API Final

**Nome:** `<x-shared.money-input>`  
**Arquivo:** `resources/views/components/shared/money-input.blade.php`

### Props

| Prop       | Tipo      | Default |
| ---------- | --------- | ------- |
| `name`     | `string`  | —       |
| `label`    | `?string` | `Valor` |
| `prefix`   | `string`  | `R$ `   |
| `hint`     | `?string` | `null`  |
| `required` | `bool`    | `false` |

---

## Código Final Blade

```blade
@props ([
    'name',
    'label' => 'Valor',
    'prefix' => 'R$ ',
    'hint' => null,
    'required' => false,
])

@php
    $inputmaskConfig = [
        'alias' => 'currency',
        'prefix' => $prefix,
        'groupSeparator' => '.',
        'radixPoint' => ',',
        'digits' => 2,
        'digitsOptional' => false,
        'autoGroup' => true,
        'rightAlign' => false,
        'removeMaskOnSubmit' => false,
        'unmaskAsNumber' => false,
        'clearMaskOnLostFocus' => false,
    ];
@endphp

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="text"
    :placeholder="$prefix.'0,00'"
    data-af-inputmask='@json($inputmaskConfig)'
    class="font-mono"
    {{ $attributes }}
/>
```

---

## Exemplo de Uso

```blade
<x-shared.money-input name="valor_parcela" label="Valor da parcela" wire:model.live="form.valor" required />
```

---

## Notas de Implementação

1. A máscara é inicializada em `resources/js/admin/forms.js`.
2. O backend deve converter o valor para `int` centavos via helper dedicado.
3. `rightAlign: false` foi mantido para ergonomia melhor em forms do admin.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2           |
| **Complexidade** | Média        |
| **Status**       | 🟢 Concluído |
