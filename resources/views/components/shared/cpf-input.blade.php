@props ([
    'name',
    'label' => 'CPF',
    'hint' => null,
    'required' => false,
])

{{-- Além da máscara, o dígito verificador é conferido no cliente (admin/validators.js):
     um CPF impossível (111.111.111-11) deixa de custar um round-trip. O App\Rules\Cpf
     segue sendo a autoridade no servidor — o cliente só antecipa o veredito. --}}
<x-shared.masked-input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    mask="999.999.999-99"
    placeholder="000.000.000-00"
    data-af-validate="cpf"
    {{ $attributes }}
/>
