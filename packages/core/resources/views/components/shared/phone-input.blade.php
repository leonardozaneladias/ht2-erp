@props ([
    'name',
    'label' => 'Telefone',
    'hint' => null,
    'required' => false,
])

{{-- Máscara adaptativa: o Inputmask escolhe entre fixo e celular pelo número de dígitos. --}}
<x-shared.masked-input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    :mask="['(99) 9999-9999', '(99) 99999-9999']"
    placeholder="(00) 00000-0000"
    type="tel"
    icon="tabler--phone"
    data-af-validate="telefone"
    {{ $attributes }}
/>
