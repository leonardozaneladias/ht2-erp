# Masked Input (base)

**Categoria:** Form (masked)
**Plugins JS:** Inputmask 5.0.9

---

## Descrição

Base de **todo** campo mascarado do design system. `cpf`, `cnpj`, `phone`, `pis` e `cid` são
wrappers finos dela — a máscara é o único parâmetro que muda entre eles.

Existe por dois motivos:

1. O §15.4 do catálogo proíbe `data-af-inputmask` fora de `components/shared/`. Sem uma base
   genérica, **cada formato novo exigia um componente novo** — e quem tinha pressa acabava
   colando a máscara inline na view. Foi o que aconteceu com o PIS, que ficou meses como o
   único `data-af-inputmask` fora do core, direto no cadastro de funcionário.
2. `cpf`/`cnpj`/`phone` eram cópias do mesmo wrapper de `x-shared.input`.

**Quando usar a base direto:** um formato que aparece em uma tela só (uma placa, um número de
processo). Não crie componente para isso — use `mask="..."`.
**Quando criar um wrapper:** o formato é recorrente e tem semântica própria (um documento
brasileiro, um código legal), ou precisa de validação de dígito verificador.

---

## API

**Nome:** `<x-shared.masked-input>`
**Arquivo:** `resources/views/components/shared/masked-input.blade.php`

| Prop              | Tipo            | Padrão  | Descrição                                                                                 |
| ----------------- | --------------- | ------- | ----------------------------------------------------------------------------------------- |
| `name`            | string          | —       | Obrigatório. Deriva o `id` e a chave do erro.                                             |
| `mask`            | string \| array | —       | Obrigatório. Máscara do Inputmask. Lista = alternativas escolhidas pelo tamanho digitado. |
| `label`           | ?string         | `null`  |                                                                                           |
| `hint`            | ?string         | `null`  |                                                                                           |
| `required`        | bool            | `false` |                                                                                           |
| `placeholder`     | ?string         | `null`  |                                                                                           |
| `clearIncomplete` | bool            | `true`  | Apaga o conteúdo se a máscara ficar incompleta no blur. Um documento pela metade é lixo.  |
| `uppercase`       | bool            | `false` | `casing: upper` do Inputmask (CID, placa, UF).                                            |
| `icon`            | ?string         | `null`  | Ícone iconify no campo.                                                                   |
| `type`            | string          | `text`  |                                                                                           |
| `mono`            | bool            | `true`  | Fonte de largura fixa — os dígitos não "dançam" ao digitar.                               |
| `maskOptions`     | array           | `[]`    | Escape para qualquer outra opção do Inputmask, sem precisar de componente novo.           |

Todo o resto (`wire:model`, `data-*`, atributos HTML) é repassado para `x-shared.input`.

---

## Exemplos

```blade
{{-- Formato pontual: não crie componente para isso --}}
<x-shared.masked-input name="placa" label="Placa" mask="AAA-9*99" :uppercase="true" />

{{-- Alternativas: o Inputmask escolhe pelo tamanho --}}
<x-shared.masked-input name="telefone" mask='["(99) 9999-9999", "(99) 99999-9999"]' />

{{-- Opção extra do Inputmask sem componente novo --}}
<x-shared.masked-input name="valor" mask="9{1,10}" :mask-options="['greedy' => false]" />
```

---

## Armadilhas

- **`@class()` não funciona dentro de uma tag de componente** (`<x-...>`). O parser do Blade não
  a reconhece como atributo e **ecoa o componente inteiro como texto**. Use `:class="$expressao"`.
  (Foi assim que este componente nasceu quebrado.)
- O guard de "já inicializei" do Inputmask **não** é um `data-*`: o morph do Livewire apaga
  atributos setados por JS. Ver `resources/js/admin/forms.js`.
