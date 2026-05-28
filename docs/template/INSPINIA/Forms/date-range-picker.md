# Date Range Picker

**Categoria:** Form  
**Origem Inspinia:** `resources/views/form/pickers.blade.php`  
**Plugins JS:** Flatpickr 4.6.13 em modo `range`

---

## Descrição

Componente irmão de `x-shared.date-picker` para intervalos de data. A decisão oficial do projeto é manter os dois componentes coexistindo, ambos apoiados pelo mesmo helper JS de Flatpickr em `resources/js/admin/forms.js`.

---

## API Final

**Nome:** `<x-shared.date-range-picker>`  
**Arquivo:** `resources/views/components/shared/date-range-picker.blade.php`

### Props

| Prop            | Tipo      | Default                 |
| --------------- | --------- | ----------------------- |
| `name`          | `string`  | —                       |
| `id`            | `?string` | `null`                  |
| `label`         | `?string` | `null`                  |
| `format`        | `string`  | `d/m/Y`                 |
| `minDate`       | `?string` | `null`                  |
| `maxDate`       | `?string` | `null`                  |
| `defaultDate`   | `mixed`   | `null`                  |
| `disabledDates` | `array`   | `[]`                    |
| `placeholder`   | `string`  | `Selecione o intervalo` |
| `hint`          | `?string` | `null`                  |
| `required`      | `bool`    | `false`                 |

---

## Código Final Blade

```blade
@props ([
    'name',
    'id' => null,
    'label' => null,
    'format' => 'd/m/Y',
    'minDate' => null,
    'maxDate' => null,
    'defaultDate' => null,
    'disabledDates' => [],
    'placeholder' => 'Selecione o intervalo',
    'hint' => null,
    'required' => false,
])

@php
    $flatpickrConfig = array_filter([
        'dateFormat' => $format,
        'mode' => 'range',
        'minDate' => $minDate,
        'maxDate' => $maxDate,
        'defaultDate' => $defaultDate,
        'disable' => filled($disabledDates) ? array_values($disabledDates) : null,
    ], static fn ($value) => ! in_array($value, [null, false, []], true));
@endphp

<div class="mb-4">
    <div class="input-icon-group">
        <i class="iconify tabler--calendar-event input-icon"></i>
        <input
            name="{{ $name }}"
            type="text"
            data-af-flatpickr='@json($flatpickrConfig)'
            {{ $attributes->class(['form-input']) }}
            placeholder="{{ $placeholder }}"
        />
    </div>
</div>
```

---

## Exemplos de Uso

```blade
<x-shared.date-range-picker name="periodo" label="Período do relatório" wire:model.live="filtros.periodo" />
```

```blade
<x-shared.date-range-picker name="vigencia" label="Vigência" min-date="2026-01-01" max-date="2026-12-31" />
```

---

## Notas de Implementação

1. A API real usa `data-af-flatpickr`, não mais os atributos genéricos do template original.
2. O helper dispara `input` e `change` ao fechar/alterar o range para manter Livewire sincronizado.
3. Daterangepicker jQuery continua fora do escopo.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2           |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |
