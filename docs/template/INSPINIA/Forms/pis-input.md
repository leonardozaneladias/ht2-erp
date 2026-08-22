# PIS/PASEP Input

**Categoria:** Form (masked)
**Plugins JS:** Inputmask 5.0.9
**Base:** [`x-shared.masked-input`](masked-input.md)

---

## Descrição

Campo de PIS/PASEP: máscara `999.99999.99-9` e conferência do dígito verificador no cliente.

Existe porque o PIS era **a única violação do §15.4** do catálogo — a máscara estava colada
direto na view do cadastro de funcionário (`packages/modulo-rh/.../form-funcionario.blade.php`),
como um `data-af-inputmask` inline, justamente por não haver componente nem base genérica.

---

## API

**Nome:** `<x-shared.pis-input>`
**Arquivo:** `resources/views/components/shared/pis-input.blade.php`

| Prop       | Tipo    | Padrão        |
| ---------- | ------- | ------------- |
| `name`     | string  | —             |
| `label`    | string  | `'PIS/PASEP'` |
| `hint`     | ?string | `null`        |
| `required` | bool    | `false`       |

---

## Uso

```blade
<x-shared.pis-input name="pis_pasep" wire:model.blur="pis_pasep" />
```

---

## Validação

O componente marca `data-af-validate="pis"`, e o `validation-errors.js` faz o **pré-flight** no
`salvar`: um PIS com dígito verificador impossível é barrado **sem gastar um round-trip**.

`App\Rules\Pis` continua sendo a autoridade no servidor — o cliente só antecipa o veredito. O
fixture `tests/Fixtures/documentos-dv.json` é rodado contra as duas implementações (PHP e JS);
se elas divergirem, um dos dois testes fica vermelho.
