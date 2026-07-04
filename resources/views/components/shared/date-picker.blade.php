@props ([
    'name',
    'id' => null,
    'label' => null,
    'format' => 'd/m/Y',
    'enableTime' => false,
    'minDate' => null,
    'maxDate' => null,
    'defaultDate' => null,
    'disabledDates' => [],
    'placeholder' => null,
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
    // O input ORIGINAL carrega o valor canônico ISO (o que o Livewire hidrata e o
    // servidor valida com a regra `date`); a exibição d/m/Y fica no altInput do
    // flatpickr. Sem isso, o valor ISO era re-parseado como d/m/Y (datas erradas na
    // tela) e a digitação d/m/Y persistia com mês/dia trocados (laudo 18, F9-01).
    $flatpickrConfig = array_filter([
        'dateFormat' => $enableTime ? 'Y-m-d H:i' : 'Y-m-d',
        'altInput' => true,
        'altFormat' => $format . ($enableTime && ! str_contains($format, 'H') ? ' H:i' : ''),
        'enableTime' => $enableTime,
        'minDate' => $minDate,
        'maxDate' => $maxDate,
        'defaultDate' => $defaultDate,
        'disable' => filled($disabledDates) ? array_values($disabledDates) : null,
    ], static fn ($value) => ! in_array($value, [null, false, []], true));
    $resolvedPlaceholder = $placeholder ?? ($enableTime ? 'dd/mm/aaaa hh:mm' : 'dd/mm/aaaa');
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

    <div class="input-icon-group">
        <i class="iconify tabler--calendar input-icon" aria-hidden="true"></i>
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="text"
            data-af-flatpickr="{{ \Illuminate\Support\Js::encode($flatpickrConfig) }}"
            {{
$attributes->class([
                'form-input',
                'border-danger!' => $hasError,
            ])
}}
            @required ($required)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            autocomplete="off"
            placeholder="{{ $resolvedPlaceholder }}"
        />
    </div>

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs" id="{{ $errorId }}">{{ $viewErrors->first($errorKey) }}</small>
    @endif

    @if ($hint)
        <small class="text-default-400 mt-1 block text-xs" id="{{ $hintId }}">{{ $hint }}</small>
    @endif
</div>
