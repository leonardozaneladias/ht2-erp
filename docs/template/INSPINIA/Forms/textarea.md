# Textarea

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/elements.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classe `.form-textarea` do Inspinia

---

## Descrição

Campo de texto multi-linha. Versão textarea do `<x-shared.input>` — mesma estrutura de label, hint, erro e integração Livewire.

---

## Código Original (Inspinia)

```html
<label class="form-label" for="obs">Observações</label> <textarea class="form-textarea" id="obs" rows="5"></textarea>
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.textarea>`
**Arquivo:** `resources/views/components/shared/textarea.blade.php`

### Props

| Prop       | Tipo      | Obrigatório | Default | Descrição        |
| ---------- | --------- | :---------: | ------- | ---------------- |
| `name`     | `string`  |     ✅      | —       | —                |
| `label`    | `?string` |     ❌      | `null`  | —                |
| `rows`     | `int`     |     ❌      | `4`     | Linhas de altura |
| `hint`     | `?string` |     ❌      | `null`  | —                |
| `required` | `bool`    |     ❌      | `false` | —                |

### Código

```blade
{{-- resources/views/components/shared/textarea.blade.php --}}
@props ([
    'name',
    'label' => null,
    'rows' => 4,
    'hint' => null,
    'required' => false,
])

@php $hasError = $errors->has($name); @endphp

<div class="mb-4">
    @if ($label)
        <label class="form-label" for="{{ $name }}">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->class([
                  'form-textarea',
                  'border-danger!' => $hasError,
              ]) }}
        @required ($required)
        >{{ $slot }}</textarea
    >

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs">{{ $errors->first($name) }}</small>
    @elseif ($hint)
        <small class="text-default-400 mt-1 block text-xs">{{ $hint }}</small>
    @endif
</div>
```

---

## Exemplos de Uso

```blade
<x-shared.textarea name="observacoes" label="Observações" rows="6" wire:model="pedido.observacoes" />
<x-shared.textarea name="descricao" label="Descrição" hint="Máx 500 caracteres" wire:model="produto.descricao" />
```

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Slot para valor inicial** — permite `<x-shared.textarea>{{ $old }}</x-shared.textarea>` sem Livewire
2. **Com Livewire:** usar `wire:model` no slot vazio
3. **Sem auto-resize** — para auto-resize, envolver com Alpine `x-data` + `$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'`

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/textarea.blade.php`
- **Preview visual:** `/admin/dev/components/textarea`

### API final

- props: `name`, `id`, `label`, `rows`, `value`, `hint`, `required`
- ajustes aplicados:
    - `value` foi incorporado como forma direta de preencher o campo sem depender só do slot
    - o slot continua funcionando para conteúdo inicial rico ou multilinha
    - o componente segue o mesmo contrato de acessibilidade e erro do `x-shared.input`

### Exemplo final

```blade
<x-shared.textarea
    name="descricao"
    label="Descrição"
    rows="5"
    hint="Resumo interno do produto."
    value="Pacote com cobertura fotográfica e filmagem."
/>
```
