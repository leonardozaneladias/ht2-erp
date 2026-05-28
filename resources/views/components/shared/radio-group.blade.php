@props ([
    'name',
    'label' => null,
    'hint' => null,
    'inline' => false,
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $errorKey = str_replace('[]', '', $name);
    $hasError = $viewErrors->has($errorKey);
    $hintId = filled($hint) ? "{$errorKey}-hint" : null;
    $errorId = $hasError ? "{$errorKey}-error" : null;
@endphp

<fieldset class="mb-4">
    @if ($label)
        <legend class="form-label mb-2">{{ $label }}</legend>
    @endif

    <div @class (['flex gap-4', 'flex-col' => ! $inline, 'flex-row flex-wrap items-start' => $inline])>
        {{ $slot ?? '' }}
    </div>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</fieldset>
