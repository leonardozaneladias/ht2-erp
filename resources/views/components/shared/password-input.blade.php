@props ([
    'name',
    'id' => null,
    'label' => 'Senha',
    'hint' => null,
    'required' => false,
    'withMeter' => false,
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $meterLabelId = $withMeter ? "{$fieldId}-meter-label" : null;
    $describedBy = collect([$errorId, $hintId, $meterLabelId])->filter()->implode(' ') ?: null;
@endphp

<div class="mb-4" data-password-field>
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}

            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="password"
            data-password-input
            {{
$attributes->class([
                'form-input pe-10',
                'border-danger!' => $hasError,
            ])
}}
            @required ($required)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        />

        <button
            type="button"
            class="text-default-400 hover:text-body-color absolute end-3 top-1/2 -translate-y-1/2 transition"
            data-password-toggle
            aria-controls="{{ $fieldId }}"
            aria-label="Mostrar senha"
            aria-pressed="false"
        >
            <i class="iconify tabler--eye text-lg" data-password-toggle-icon></i>
        </button>
    </div>

    @if ($withMeter)
        <x-shared.password-strength-meter :field-id="$fieldId" :label-id="$meterLabelId" />
    @endif

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
