@props ([
    'name',
    'label' => 'CID',
    'hint' => null,
    'required' => false,
])

{{-- CID-10: uma letra + 2 dígitos, com subcategoria opcional (J11, J11.1). Sem máscara o
     campo é texto livre e aceita "gripe" — um código malformado costuma vazar daqui para
     integrações (no RH, para o eSocial). A máscara garante o FORMATO; ela não valida o
     código contra o catálogo CID-10. `casing: upper` porque a letra é sempre maiúscula. --}}
<x-shared.masked-input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    mask="A99[.9]"
    placeholder="J11.1"
    :uppercase="true"
    :clear-incomplete="false"
    {{ $attributes }}
/>
