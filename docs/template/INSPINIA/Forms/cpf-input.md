# CPF Input

**Categoria:** Form (masked)  
**Origem Inspinia:** `resources/views/form/other-plugin.blade.php`  
**Plugins JS:** Inputmask 5.0.9

---

## Descrição

Wrapper oficial para CPF brasileiro. O componente reutiliza `x-shared.input`, adicionando apenas a máscara `999.999.999-99` e a semântica de campo pt-BR.

---

## API Final

**Nome:** `<x-shared.cpf-input>`  
**Arquivo:** `resources/views/components/shared/cpf-input.blade.php`

Props herdadas da base `x-shared.input`: `name`, `label`, `hint`, `required` e demais atributos HTML.

---

## Código Final Blade

```blade
@props ([
    'name',
    'label' => 'CPF',
    'hint' => null,
    'required' => false,
])

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="text"
    placeholder="000.000.000-00"
    data-af-inputmask='@json(["mask" => "999.999.999-99", "clearIncomplete" => true])'
    class="font-mono"
    {{ $attributes }}
/>
```

---

## Exemplo de Uso

```blade
<x-shared.cpf-input name="cpf_formando" label="CPF do formando" wire:model.live="form.cpf" required />
```

---

## Notas de Implementação

1. A inicialização real acontece em `resources/js/admin/forms.js` pelo seletor `[data-af-inputmask]`.
2. A máscara é UX; a validação oficial continua server-side.
3. Para persistência, normalizar o valor sem pontuação antes de salvar.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2           |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |
