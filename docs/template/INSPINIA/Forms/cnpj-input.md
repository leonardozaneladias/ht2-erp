# CNPJ Input

**Categoria:** Form (masked)  
**Origem Inspinia:** `resources/views/form/other-plugin.blade.php`  
**Plugins JS:** Inputmask 5.0.9

---

## Descrição

Variação da base `x-shared.input` para CNPJ brasileiro. Mantém a mesma ergonomia do `cpf-input`, mudando apenas máscara e semântica de uso.

---

## API Final

**Nome:** `<x-shared.cnpj-input>`  
**Arquivo:** `resources/views/components/shared/cnpj-input.blade.php`

---

## Código Final Blade

```blade
@props ([
    'name',
    'label' => 'CNPJ',
    'hint' => null,
    'required' => false,
])

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="text"
    placeholder="00.000.000/0000-00"
    data-af-inputmask='@json(["mask" => "99.999.999/9999-99", "clearIncomplete" => true])'
    class="font-mono"
    {{ $attributes }}
/>
```

---

## Exemplo de Uso

```blade
<x-shared.cnpj-input name="cnpj" label="CNPJ" wire:model.live="cliente.cnpj" required />
```

---

## Notas de Implementação

1. O componente depende do mesmo init oficial de Inputmask em `resources/js/admin/forms.js`.
2. Validar no backend com regra específica de CNPJ e `unique` quando aplicável.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |
