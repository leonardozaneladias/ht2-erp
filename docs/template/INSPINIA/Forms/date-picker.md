# Date Picker (Flatpickr)

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/pickers.blade.php`
**Plugins JS:** Flatpickr 4.6.13
**Plugins CSS:** Flatpickr CSS

---

## Descrição

Seletor de data usando Flatpickr. Aplicado via `data-provider="flatpickr"`. Suporta formato customizado, min/max date, default date, enable-time, disabled dates, modo range.

---

## Código Original (Inspinia)

```html
<!-- Básico -->
<input
    class="form-input"
    data-provider="flatpickr"
    data-date-format="d/m/Y"
    type="text"
    placeholder="Selecione uma data"
/>

<!-- Com hora -->
<input class="form-input" data-provider="flatpickr" data-date-format="d/m/Y H:i" data-enable-time type="text" />

<!-- Min/Max -->
<input
    class="form-input"
    data-provider="flatpickr"
    data-date-format="d/m/Y"
    data-mindate="today"
    data-maxdate="2026-12-31"
    type="text"
/>

<!-- Default -->
<input
    class="form-input"
    data-provider="flatpickr"
    data-date-format="d/m/Y"
    data-default-date="2026-04-11"
    type="text"
/>
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.date-picker>`
**Arquivo:** `resources/views/components/shared/date-picker.blade.php`

### Props

| Prop         | Tipo      | Obrigatório | Default   | Descrição                |
| ------------ | --------- | :---------: | --------- | ------------------------ |
| `name`       | `string`  |     ✅      | —         | —                        |
| `label`      | `?string` |     ❌      | `null`    | —                        |
| `format`     | `string`  |     ❌      | `'d/m/Y'` | Formato pt-BR            |
| `enableTime` | `bool`    |     ❌      | `false`   | Habilita seleção de hora |
| `minDate`    | `?string` |     ❌      | `null`    | "today" ou data Y-m-d    |
| `maxDate`    | `?string` |     ❌      | `null`    | —                        |
| `hint`       | `?string` |     ❌      | `null`    | —                        |
| `required`   | `bool`    |     ❌      | `false`   | —                        |

### Código

```blade
{{-- resources/views/components/shared/date-picker.blade.php --}}
@props ([
    'name',
    'label' => null,
    'format' => 'd/m/Y',
    'enableTime' => false,
    'minDate' => null,
    'maxDate' => null,
    'hint' => null,
    'required' => false,
])

@php
    $hasError = $errors->has($name);
    $finalFormat = $enableTime ? $format . ' H:i' : $format;
@endphp

<div class="mb-4" wire:ignore>
    @if ($label)
        <label class="form-label" for="{{ $name }}">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <div class="input-icon-group">
        <i class="iconify tabler--calendar input-icon"></i>
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="text"
            data-provider="flatpickr"
            data-date-format="{{ $finalFormat }}"
            @if ($enableTime) data-enable-time @endif
            @if ($minDate) data-mindate="{{ $minDate }}" @endif
            @if ($maxDate) data-maxdate="{{ $maxDate }}" @endif
            {{ $attributes->class([
                   'form-input',
                   'border-danger!' => $hasError,
               ]) }}
            @required ($required)
            placeholder="dd/mm/aaaa"
        />
    </div>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs">{{ $errors->first($name) }}</small>
    @elseif ($hint)
        <small class="text-default-400 mt-1 block text-xs">{{ $hint }}</small>
    @endif
</div>
```

---

## Exemplos de Uso

### Real (datas de um registro)

```blade
<x-shared.date-picker name="data_inicio" label="Data Início" wire:model="pedido.data_inicio" required />
<x-shared.date-picker name="data_evento" label="Data do Evento" min-date="today" wire:model="pedido.data_evento" />
```

### Real (intervalo início/fim)

```blade
<div class="grid grid-cols-2 gap-4">
    <x-shared.date-picker name="inicio" label="Data Início" wire:model="form.inicio" required />
    <x-shared.date-picker name="fim" label="Data Fim" wire:model="form.fim" required />
</div>
```

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Formato pt-BR `d/m/Y`** padrão — diferente do Inspinia que usa `d M, Y`
2. **Locale pt-BR:** aplicado no helper final de forms do admin
3. **Implementação final usa `data-af-flatpickr`** em vez do init legado global do template, para evitar conflitos com defaults do `app.js`
4. **Dependência JS final:** `resources/js/admin/forms.js`
5. **Ícone calendário** à esquerda via `input-icon-group`
6. **Livewire sync:** o helper dispara `input` e `change` após seleção/fechamento do calendário

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/date-picker.blade.php`
- **Dependência JS final:** `resources/js/admin/forms.js`
- **Preview visual:** `/admin/dev/components/date-picker`

### API final

- props: `name`, `id`, `label`, `format`, `enableTime`, `minDate`, `maxDate`, `defaultDate`, `disabledDates`, `placeholder`, `hint`, `required`
- ajustes aplicados:
    - `defaultDate` e `disabledDates` entraram na API final para cobrir os cenários já previstos na doc original
    - a inicialização passou a usar config JSON explícita por instância

### Exemplo final

```blade
<x-shared.date-picker name="data_evento" label="Data do Evento" min-date="today" default-date="2026-11-21" required />
```
