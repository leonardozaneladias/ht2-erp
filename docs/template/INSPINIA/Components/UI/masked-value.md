# masked-value — valor sensível atrás de Mostrar/Ocultar

Rótulo em caps pequenas + valor mascarado (`••••••`) com toggle Mostrar/Ocultar.
É o irmão "sensível" do `field-display`: mesma anatomia visual, mas o valor só
aparece por ação explícita do operador (salário, remuneração — dados que não
devem ficar expostos numa tela aberta ao lado de terceiros).

Nasceu na 4ª rodada do RH (D13 do doc 31) unificando as duas cópias inline que
divergiam em tokens e não tinham `aria-pressed` (dívida anotada na C17).

## API

```blade
<x-shared.masked-value label="Salário base" aria-target="salário base">
    {{ $this->salarioFormatado }}
</x-shared.masked-value>
```

| Prop          | Tipo   | Default           | Descrição                                               |
| ------------- | ------ | ----------------- | ------------------------------------------------------- |
| `label`       | string | —                 | Rótulo do campo (caps pequenas, como no field-display). |
| `aria-target` | string | `label` minúsculo | O que está oculto, para o `aria-label` do botão.        |

O valor vai no slot **já formatado** (dinheiro continua vindo de
`Money::formatado()`); o componente não formata nada.

## Acessibilidade

- Botão com `aria-pressed` (false/true) e `aria-label` "Mostrar/Ocultar {alvo}".
- Os `••••••` levam `aria-hidden` (ruído para leitor de tela).

## Limitações

Como `x-shared.toggle`/`field-display`, **não mescla `class` na raiz** — para
col-span/margens, envolva num wrapper:

```blade
<div class="md:col-span-3">
    <x-shared.masked-value label="Salário base">…</x-shared.masked-value>
</div>
```

O estado inicial é sempre **oculto** (não persiste entre visitas — é proteção de
tela, não de dado; a autorização de ver o valor é gate de permissão no call site).
