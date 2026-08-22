# CID Input

**Categoria:** Form (masked)
**Plugins JS:** Inputmask 5.0.9
**Base:** [`x-shared.masked-input`](masked-input.md)

---

## Descrição

Campo de CID-10 (Classificação Internacional de Doenças): uma letra + dois dígitos, com
subcategoria opcional — `J11`, `J11.1`. Máscara `A99[.9]`, `casing: upper`.

Existe porque o campo era **texto livre** (`<x-shared.input maxlength="10">`) e aceitava
qualquer coisa — inclusive `"gripe"`. E esse valor **vai para o eSocial** (S-2230, afastamento
por doença), onde um CID malformado derruba o evento.

**O que ele NÃO faz:** não valida o código contra o catálogo CID-10. Garante o **formato**, que é
o que o leiaute exige. Um autocomplete contra a tabela CID-10 é um incremento futuro.

---

## API

**Nome:** `<x-shared.cid-input>`
**Arquivo:** `resources/views/components/shared/cid-input.blade.php`

| Prop       | Tipo    | Padrão  |
| ---------- | ------- | ------- |
| `name`     | string  | —       |
| `label`    | string  | `'CID'` |
| `hint`     | ?string | `null`  |
| `required` | bool    | `false` |

`clearIncomplete` é **desligado**: o CID pode ser válido sem a subcategoria (`J11`), então apagar
o que foi digitado por estar "incompleto" seria errado.

---

## Uso

```blade
<x-shared.cid-input name="cid" label="CID (opcional)" wire:model="cid" />
```

---

## Regra correspondente no servidor

A máscara é conveniência; a garantia é do servidor:

```php
'cid' => ['nullable', 'string', 'regex:/^[A-Z]\d{2}(\.\d)?$/'],
```

(em `packages/modulo-rh/src/Http/Requests/Afastamentos/AfastamentoRules.php`)
