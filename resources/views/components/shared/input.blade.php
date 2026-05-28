@props ([
    'name',
    'id' => null,
    'label' => null,
    'type' => 'text',
    'icon' => null,
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
    $inputClasses = [
        'form-input',
        'border-danger!' => $hasError,
    ];
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

    @if ($icon)
        <div class="input-icon-group">
            <i class="iconify {{ $icon }} input-icon"></i>
            <input
                id="{{ $fieldId }}"
                name="{{ $name }}"
                type="{{ $type }}"
                {{ $attributes->class($inputClasses) }}
                @required ($required)
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            />
        </div>
    @else
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            {{ $attributes->class($inputClasses) }}
            @required ($required)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        />
    @endif

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
