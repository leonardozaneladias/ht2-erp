@props ([
    'name',
    'label' => 'Valor',
    'prefix' => 'R$ ',
    'hint' => null,
    'required' => false,
])

@php
    $inputmaskConfig = [
        'alias' => 'currency',
        'prefix' => $prefix,
        'groupSeparator' => '.',
        'radixPoint' => ',',
        'digits' => 2,
        'digitsOptional' => false,
        'autoGroup' => true,
        'rightAlign' => false,
        'removeMaskOnSubmit' => false,
        'unmaskAsNumber' => false,
        'clearMaskOnLostFocus' => false,
    ];
@endphp

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="text"
    :placeholder="$prefix.'0,00'"
    :data-af-inputmask="\Illuminate\Support\Js::encode($inputmaskConfig)"
    class="font-mono"
    {{ $attributes }}
/>
