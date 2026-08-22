@props ([
    'name',
    'label' => 'PIS/PASEP',
    'hint' => null,
    'required' => false,
])

{{-- O PIS era o único `data-af-inputmask` fora de `components/shared/` — a máscara estava
     colada direto na view do cadastro de funcionário, contra o §15.4 do catálogo. O dígito
     verificador é conferido no cliente; App\Rules\Pis segue valendo no servidor. --}}
<x-shared.masked-input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    mask="999.99999.99-9"
    placeholder="000.00000.00-0"
    data-af-validate="pis"
    {{ $attributes }}
/>
