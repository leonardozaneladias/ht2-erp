{{--
    Select com busca (single/multiple) sobre o x-shared.combobox: gatilho compacto
    com contagem + dropdown com busca e checkbox. A API e preservada para os
    consumidores; o <select> nativo abaixo (sr-only, x-ref="native") segue sendo a
    fonte para POST tradicional, wire:model e old(). O combobox so o pilota.

    Props sem efeito no combobox v1 (mantidas por compatibilidade): removeItem
    (sempre removivel), allowCreate / maxItems / shouldSort. Entrada livre (chips)
    continua no x-shared.tags-input (Choices.js).
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
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
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
    // Lista sequencial (['Ativo', 'Inativo']) usa o próprio rótulo como value; mapa
    // int-keyed NÃO-sequencial ([3731 => 'Presidente Epitácio'], típico de
    // pluck('nome', 'id')) usa a KEY. Decidir por is_int(key) sozinho quebrava os ids:
    // o value virava o nome e o valor persistido nunca casava com opção nenhuma.
    $optionsEhLista = is_array($options) && array_is_list($options);

    $normalizedOptions = collect($options ?? [])->map(function ($optionLabel, $optionValue) use ($optionsEhLista) {
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
            $grupoEhLista = array_is_list($optionLabel['options']);

            return [
                'group' => $optionLabel['label'] ?? $optionValue,
                'options' => collect($optionLabel['options'])->map(function ($groupOption, $groupValue) use ($grupoEhLista) {
                    if (is_array($groupOption)) {
                        return [
                            'value' => $groupOption['value'] ?? $groupValue,
                            'label' => $groupOption['label'] ?? $groupOption['value'] ?? $groupValue,
                            'disabled' => (bool) ($groupOption['disabled'] ?? false),
                        ];
                    }

                    return [
                        'value' => $grupoEhLista && is_int($groupValue) ? $groupOption : $groupValue,
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
            'value' => $optionsEhLista && is_int($optionValue) ? $optionLabel : $optionValue,
            'label' => $optionLabel,
            'disabled' => false,
        ];
    })->values();
@endphp

<div class="mb-4">
    @if ($label)
        <label class="form-label" id="{{ $fieldId }}-label" for="{{ $fieldId }}-trigger">
            {{ $label }}

            @if ($required)
                <x-shared.required-indicator />
            @endif
        </label>
    @endif

    <x-shared.combobox
        mode="form"
        :id="$fieldId"
        :multiple="$multiple"
        :searchable="$searchable"
        :placeholder="$placeholder"
        :required="$required"
        :invalid="$hasError"
        :label-id="$label ? $fieldId . '-label' : null"
        :described-by="$describedBy"
    >
        <select
            id="{{ $fieldId }}"
            name="{{ $multiple ? $baseName . '[]' : $baseName }}"
            x-ref="native"
            class="sr-only"
            tabindex="-1"
            aria-hidden="true"
            {{ $attributes }}
            @if ($multiple) multiple @endif
            @required ($required)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
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
            @endif
        </select>
    </x-shared.combobox>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
