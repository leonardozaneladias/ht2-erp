@props ([
    'name',
    'id' => null,
    'label' => null,
    'value' => '1',
    'hint' => null,
    'checked' => false,
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: \Illuminate\Support\Str::of($name)->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$fieldId}-hint" : null;
    $errorId = $hasError ? "{$fieldId}-error" : null;
    $describedBy = collect([$errorId, $hintId])->filter()->implode(' ') ?: null;
    $isChecked = old($errorKey) !== null ? (bool) old($errorKey) : ($checked || $attributes->has('checked'));
@endphp

<div class="mb-4">
    <label class="inline-flex cursor-pointer items-start gap-3">
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="checkbox"
            value="{{ $value }}"
            {{
$attributes->class([
                'form-checkbox mt-0.5 rounded text-primary focus:ring-primary',
                'border-danger!' => $hasError,
            ])
}}
            @checked ($isChecked)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        />

        @if ($label)
            <span class="min-w-0">
                <span class="text-body-color block text-sm font-medium">{{ $label }}</span>
            </span>
        @endif
    </label>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
