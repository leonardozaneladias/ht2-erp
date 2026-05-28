@props ([
    'name',
    'id' => null,
    'label' => null,
    'rows' => 4,
    'value' => null,
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
    $slotContent = (string) ($slot ?? '');
    $slotValue = trim($slotContent) !== '' ? $slotContent : null;
    $textareaValue = $value ?? $slotValue;
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

    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{
$attributes->class([
            'form-textarea',
            'border-danger!' => $hasError,
        ])
}}
        @required ($required)
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
    >

        {{ $textareaValue }}</textarea
    >

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
