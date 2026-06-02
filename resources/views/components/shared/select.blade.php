@props ([
    'name',
    'id' => null,
    'label' => null,
    'options' => null,
    'value' => null,
    'placeholder' => 'Selecione...',
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
    $selectedValue = old($errorKey, $value);
    $normalizedOptions = collect($options ?? [])->map(function ($optionLabel, $optionValue) {
        if (is_array($optionLabel)) {
            return [
                'value' => $optionLabel['value'] ?? $optionValue,
                'label' => $optionLabel['label'] ?? $optionLabel['value'] ?? $optionValue,
                'disabled' => (bool) ($optionLabel['disabled'] ?? false),
            ];
        }

        if (is_object($optionLabel)) {
            return [
                'value' => $optionLabel->value ?? $optionValue,
                'label' => $optionLabel->label ?? $optionLabel->value ?? $optionValue,
                'disabled' => (bool) ($optionLabel->disabled ?? false),
            ];
        }

        return [
            'value' => is_int($optionValue) ? $optionLabel : $optionValue,
            'label' => $optionLabel,
            'disabled' => false,
        ];
    })->values();
@endphp

<div class="mb-4">
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        {{
$attributes->class([
            'form-select cursor-pointer transition-all duration-150 ease-out',
            'focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-1 focus-visible:ring-offset-card',
            'border-danger!' => $hasError,
        ])
}}
        @required ($required)
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
    >
        @if ($placeholder !== null)
            <option value="" @selected (blank($selectedValue)) @disabled ($required)>{{ $placeholder }}</option>
        @endif

        @if ($options !== null)
            @foreach ($normalizedOptions as $option)
                <option
                    value="{{ $option['value'] }}"
                    @selected ((string) $selectedValue === (string) $option['value'])
                    @disabled ($option['disabled'])
                >
                    {{ $option['label'] }}
                </option>
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
