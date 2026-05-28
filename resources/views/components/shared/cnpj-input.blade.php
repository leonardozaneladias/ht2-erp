@props ([
    'name',
    'label' => 'CNPJ',
    'hint' => null,
    'required' => false,
])

@php
    $inputmaskConfig = [
        'mask' => '99.999.999/9999-99',
        'clearIncomplete' => true,
    ];
@endphp

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="text"
    placeholder="00.000.000/0000-00"
    :data-af-inputmask="\Illuminate\Support\Js::encode($inputmaskConfig)"
    class="font-mono"
    {{ $attributes }}
/>
