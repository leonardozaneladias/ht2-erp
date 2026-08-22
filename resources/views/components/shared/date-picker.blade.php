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

    // O flatpickr troca o input para type=hidden e insere um altInput — mutações que o
    // morph do Livewire desfazia, deixando o campo visualmente VAZIO a cada edição de
    // qualquer outro campo do formulário. Com wire:ignore o morph não toca aqui, e o
    // valor trafega pelo entangle (mesmo padrão do x-shared.money-input).
    $wireModelKey = collect($attributes->all())
        ->keys()
        ->first(fn (string $k) => str_starts_with($k, 'wire:model'));
    $livewireProp = $wireModelKey !== null ? $attributes->get($wireModelKey) : null;
    // `.live` precisa continuar disparando o round-trip (ex.: filtros de período).
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
        <i class="iconify tabler--calendar input-icon" aria-hidden="true"></i>
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
