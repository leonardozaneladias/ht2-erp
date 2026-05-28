# Select (Native)

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/elements.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classe `.form-input` do Inspinia

---

## Descrição

Select HTML nativo — **sem JavaScript**. Usar para listas curtas (< 10 opções), enums simples, UF, meses. Para listas longas ou com busca, usar `<x-shared.select-search>` (Choices.js).

---

## Código Original (Inspinia)

```html
<label class="form-label" for="uf">UF</label>
<select class="form-input" id="uf" name="uf">
    <option value="">Selecione</option>
    <option value="SP">São Paulo</option>
    <option value="RJ">Rio de Janeiro</option>
</select>
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.select>`
**Arquivo:** `resources/views/components/shared/select.blade.php`

### Props

| Prop          | Tipo      | Obrigatório | Default          | Descrição                                                |
| ------------- | --------- | :---------: | ---------------- | -------------------------------------------------------- |
| `name`        | `string`  |     ✅      | —                | —                                                        |
| `label`       | `?string` |     ❌      | `null`           | —                                                        |
| `options`     | `?array`  |     ❌      | `null`           | Array associativo `[value => label]`. Se null, usar slot |
| `placeholder` | `?string` |     ❌      | `'Selecione...'` | Primeira opção desabilitada                              |
| `hint`        | `?string` |     ❌      | `null`           | —                                                        |
| `required`    | `bool`    |     ❌      | `false`          | —                                                        |

### Código

```blade
{{-- resources/views/components/shared/select.blade.php --}}
@props ([
    'name',
    'label' => null,
    'options' => null,
    'placeholder' => 'Selecione...',
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

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->class([
                'form-input',
                'border-danger!' => $hasError,
            ]) }}
        @required ($required)
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if ($options)
            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}">{{ $optionLabel }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs">{{ $errors->first($name) }}</small>
    @elseif ($hint)
        <small class="text-default-400 mt-1 block text-xs">{{ $hint }}</small>
    @endif
</div>
```

---

## Exemplos de Uso

### Via options array

```blade
<x-shared.select
    name="uf"
    label="UF"
    :options="['SP' => 'São Paulo', 'RJ' => 'Rio de Janeiro', 'MG' => 'Minas Gerais']"
    wire:model="form.uf"
    required
/>

<x-shared.select
    name="mes_conclusao"
    label="Mês de Conclusão"
    :options="array_combine(range(1, 12), ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'])"
    wire:model="contrato.mes_conclusao"
/>
```

### Via slot (HTML customizado)

```blade
<x-shared.select name="status" label="Status" wire:model="filtros.status">
    <option value="ativo">Ativo</option>
    <option value="cancelado">Cancelado</option>
    <option value="concluido">Concluído</option>
</x-shared.select>
```

### Enum-driven

```blade
<x-shared.select
    name="modalidade"
    label="Modalidade"
    :options="collect(ModalidadePagamento::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray()"
    wire:model="pagamento.modalidade"
/>
```

---

## Mapeamento no PRD

| Tela              | Uso                                 |
| ----------------- | ----------------------------------- |
| 14.3 Instituições | UF                                  |
| 14.4 Contratos    | Mês/Ano conclusão, Status           |
| 14.7 Programações | Status                              |
| 14.8 Condições    | Modalidade, Modalidade Complementar |
| 14.12 Formandos   | Filtros simples                     |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2 (Onda 3)  |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Usar para < 10 opções** — acima disso, `<x-shared.select-search>` com Choices.js
2. **Native select é melhor em mobile** — teclado nativo do SO, não precisa de JS
3. **`array_combine(range(1, 12), [...])`** — helper padrão para listas enumeradas
4. **Enum helpers:** sempre mapear para array `value => label` antes de passar — o componente não sabe enum

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/select.blade.php`
- **Preview visual:** `/admin/dev/components/select`

### API final

- props: `name`, `id`, `label`, `options`, `value`, `placeholder`, `hint`, `required`
- ajustes aplicados:
    - `value` seleciona a opção atual em cenários sem Livewire
    - `options` aceita array simples `value => label` ou listas com `value`, `label` e `disabled`
    - placeholder e mensagens de erro seguem o mesmo contrato do `x-shared.input`

### Exemplo final

```blade
<x-shared.select
    name="modalidade"
    label="Modalidade"
    :options="[
        ['value' => 'boleto', 'label' => 'Boleto Bancário'],
        ['value' => 'pix', 'label' => 'PIX'],
    ]"
    value="pix"
/>
```
