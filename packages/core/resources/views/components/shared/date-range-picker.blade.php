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
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    // Mesmo desenho do x-shared.date-picker (F9-01): valor canônico ISO no input
    // original; exibição d/m/Y no altInput do flatpickr.
    $flatpickrConfig = array_filter([
        'dateFormat' => 'Y-m-d',
        'altInput' => true,
        'altFormat' => $format,
        'mode' => 'range',
        'minDate' => $minDate,
        'maxDate' => $maxDate,
        'defaultDate' => $defaultDate,
        'disable' => filled($disabledDates) ? array_values($disabledDates) : null,
    ], static fn ($value) => ! in_array($value, [null, false, []], true));

    // Mesma razão do x-shared.date-picker: o flatpickr muta o input e o morph desfazia
    // a mutação, esvaziando o campo na tela. wire:ignore + entangle.
    $wireModelKey = collect($attributes->all())
        ->keys()
        ->first(fn (string $k) => str_starts_with($k, 'wire:model'));
    $livewireProp = $wireModelKey !== null ? $attributes->get($wireModelKey) : null;
    $entangle = $livewireProp !== null
        ? "\$wire.entangle('" . e($livewireProp) . "')" . (str_contains((string) $wireModelKey, '.live') ? '.live' : '')
        : 'null';
@endphp

<div class="mb-4">
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <x-shared.required-indicator />
            @endif
        </label>
    @endif

    <div
        class="input-icon-group"
        wire:ignore
        x-data="afDatePicker({{ $entangle }}, {{ \Illuminate\Support\Js::encode($flatpickrConfig) }})"
    >
        <i class="iconify tabler--calendar-event input-icon" aria-hidden="true"></i>
        <input
            x-ref="campo"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="text"
            {{
$attributes->except($wireModelKey !== null ? [$wireModelKey] : [])->class([
                'form-input',
                'border-danger!' => $hasError,
            ])
}}
            @required ($required)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            autocomplete="off"
            placeholder="{{ $placeholder }}"
        />
    </div>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
