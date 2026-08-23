@props ([
    'name',
    'label' => 'CNPJ',
    'hint' => null,
    'required' => false,
])

<x-shared.masked-input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    mask="99.999.999/9999-99"
    placeholder="00.000.000/0000-00"
    data-af-validate="cnpj"
    {{ $attributes }}
/>
