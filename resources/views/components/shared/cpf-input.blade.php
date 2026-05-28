@props ([
    'name',
    'label' => 'CPF',
    'hint' => null,
    'required' => false,
])

@php
    $inputmaskConfig = [
        'mask' => '999.999.999-99',
        'clearIncomplete' => true,
    ];
@endphp

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="text"
    placeholder="000.000.000-00"
    :data-af-inputmask="\Illuminate\Support\Js::encode($inputmaskConfig)"
    class="font-mono"
    {{ $attributes }}
/>
