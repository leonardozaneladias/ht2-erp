@props ([
    'name',
    'label' => 'Telefone',
    'hint' => null,
    'required' => false,
])

@php
    $inputmaskConfig = [
        'mask' => ['(99) 9999-9999', '(99) 99999-9999'],
        'clearIncomplete' => true,
    ];
@endphp

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="tel"
    icon="tabler--phone"
    placeholder="(00) 00000-0000"
    :data-af-inputmask="\Illuminate\Support\Js::encode($inputmaskConfig)"
    class="font-mono"
    {{ $attributes }}
/>
