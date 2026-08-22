@props ([
    'name',
    'label' => null,
    'hint' => null,
    'required' => false,
    // Máscara do Inputmask: string ('999.999.999-99') ou lista de alternativas, que o
    // Inputmask escolhe pelo tamanho do que foi digitado (['(99) 9999-9999', '(99) 99999-9999']).
    'mask',
    'placeholder' => null,
    // Apaga o conteúdo quando a máscara fica incompleta ao sair do campo. Ligado por
    // padrão: um documento pela metade é lixo, não um rascunho.
    'clearIncomplete' => true,
    // Força maiúsculas na digitação (CID, placa, UF).
    'uppercase' => false,
    'icon' => null,
    'type' => 'text',
    // Documentos ficam mais legíveis em largura fixa (os dígitos não "dançam" ao digitar).
    'mono' => true,
    // Escape para qualquer outra opção do Inputmask, sem precisar de um componente novo.
    'maskOptions' => [],
])

{{--
    Base de todo campo mascarado (Inputmask). Existe por dois motivos:

    1. O catálogo (§15.4) proíbe data-af-inputmask fora de components/shared/ — sem uma
       base genérica, CADA formato novo exigia um componente novo, e quem tinha pressa
       acabava colando a máscara inline na view (era o caso do PIS no cadastro do RH).
       Com esta base, um formato pontual passa a ser só a prop `mask`.

    2. cpf/cnpj/phone/pis eram cópias do mesmo wrapper. Agora são wrappers finos daqui,
       e a máscara vira o único parâmetro que muda.
--}}

@php
    $inputmaskConfig = array_merge([
        'mask' => $mask,
        'clearIncomplete' => $clearIncomplete,
    ], $uppercase ? ['casing' => 'upper'] : [], $maskOptions);

    // @class() não vale dentro de uma tag de componente (<x-...>) — só em tag HTML: o
    // parser do Blade não a reconhece como atributo e ecoa o componente inteiro como texto.
    $classes = $mono ? 'font-mono' : '';
@endphp

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    :type="$type"
    :icon="$icon"
    :placeholder="$placeholder"
    :data-af-inputmask="\Illuminate\Support\Js::encode($inputmaskConfig)"
    :class="$classes"
    {{ $attributes }}
/>
