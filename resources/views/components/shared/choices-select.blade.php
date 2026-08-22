{{--
    Select com busca baseado em Choices.js (data-af-choices -> initChoiceFields).

    LEGADO: e a base do x-shared.tags-input (chips + entrada livre via
    allowCreate), o unico caso que ainda nao migrou para o x-shared.combobox.
    Os selects de formulario comuns usam x-shared.select-search (combobox).
    Quando o combobox ganhar um modo "chips", este componente e o Choices.js
    serao aposentados.
--}}

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
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $baseName = str_replace('[]', '', $name);
    $errorKey = $baseName;
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $selectedValues = $multiple
        ? collect(old($errorKey, $value ?? []))->map(fn ($item) => (string) $item)->all()
        : [(string) old($errorKey, $value)];
    $selectedLookup = array_flip($selectedValues);
    $normalizedOptions = collect($options ?? [])->map(function ($optionLabel, $optionValue) {
        if (is_array($optionLabel) && array_is_list($optionLabel)) {
            return [
                'group' => $optionValue,
                'options' => collect($optionLabel)->map(function ($groupOption) {
                    if (is_array($groupOption)) {
                        return [
                            'value' => $groupOption['value'] ?? null,
                            'label' => $groupOption['label'] ?? $groupOption['value'] ?? '',
                            'disabled' => (bool) ($groupOption['disabled'] ?? false),
                        ];
                    }

                    return [
                        'value' => $groupOption,
                        'label' => $groupOption,
                        'disabled' => false,
                    ];
                })->all(),
            ];
        }

        if (is_array($optionLabel) && array_key_exists('options', $optionLabel)) {
            return [
                'group' => $optionLabel['label'] ?? $optionValue,
                'options' => collect($optionLabel['options'])->map(function ($groupOption, $groupValue) {
                    if (is_array($groupOption)) {
                        return [
                            'value' => $groupOption['value'] ?? $groupValue,
                            'label' => $groupOption['label'] ?? $groupOption['value'] ?? $groupValue,
                            'disabled' => (bool) ($groupOption['disabled'] ?? false),
                        ];
                    }

                    return [
                        'value' => is_int($groupValue) ? $groupOption : $groupValue,
                        'label' => $groupOption,
                        'disabled' => false,
                    ];
                })->all(),
            ];
        }

        if (is_array($optionLabel)) {
            return [
                'value' => $optionLabel['value'] ?? $optionValue,
                'label' => $optionLabel['label'] ?? $optionLabel['value'] ?? $optionValue,
                'disabled' => (bool) ($optionLabel['disabled'] ?? false),
            ];
        }

        return [
            'value' => is_int($optionValue) ? $optionLabel : $optionValue,
            'label' => $optionLabel,
            'disabled' => false,
        ];
    })->values();
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

<div class="mb-4">
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <x-shared.required-indicator />
            @endif
        </label>
    @endif

    <select
        id="{{ $fieldId }}"
        name="{{ $multiple ? $baseName.'[]' : $baseName }}"
        data-af-choices="{{ \Illuminate\Support\Js::encode($config) }}"
        {{
$attributes->class([
            'form-input',
            'border-danger!' => $hasError,
        ])
}}
        @if ($multiple) multiple @endif
        @required ($required)
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
    >
        @if (! $multiple && $placeholder !== null)
            <option value="" @selected (blank($selectedValues[0] ?? null))>{{ $placeholder }}</option>
        @endif

        @if ($options !== null)
            @foreach ($normalizedOptions as $option)
                @if (isset($option['group']))
                    <optgroup label="{{ $option['group'] }}">
                        @foreach ($option['options'] as $groupOption)
                            <option
                                value="{{ $groupOption['value'] }}"
                                @selected (isset($selectedLookup[(string) $groupOption['value']]))
                                @disabled ($groupOption['disabled'])
                            >
                                {{ $groupOption['label'] }}
                            </option>
                        @endforeach
                    </optgroup>
                @else
                    <option
                        value="{{ $option['value'] }}"
                        @selected (isset($selectedLookup[(string) $option['value']]))
                        @disabled ($option['disabled'])
                    >
                        {{ $option['label'] }}
                    </option>
                @endif
            @endforeach
        @else
            {{ $slot ?? '' }}
        @endif
    </select>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
