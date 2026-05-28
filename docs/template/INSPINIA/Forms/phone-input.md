# Phone Input (BR)

**Categoria:** Form (masked)  
**Origem Inspinia:** `resources/views/form/other-plugin.blade.php`  
**Plugins JS:** Inputmask 5.0.9

---

## Descrição

Campo de telefone brasileiro com máscara adaptativa para fixo e celular. A implementação oficial é uma variação da base `x-shared.input` com ícone e duas máscaras possíveis.

---

## API Final

**Nome:** `<x-shared.phone-input>`  
**Arquivo:** `resources/views/components/shared/phone-input.blade.php`

---

## Código Final Blade

```blade
@props ([
    'name',
    'label' => 'Telefone',
    'hint' => null,
    'required' => false,
])

<x-shared.input
    :name="$name"
    :label="$label"
    :hint="$hint"
    :required="$required"
    type="tel"
    icon="tabler--phone"
    placeholder="(00) 00000-0000"
    data-af-inputmask='@json(["mask" => ["(99) 9999-9999", "(99) 99999-9999"], "clearIncomplete" => true])'
    class="font-mono"
    {{ $attributes }}
/>
```

---

## Exemplo de Uso

```blade
<x-shared.phone-input name="telefone_celular" label="Celular" wire:model.live="form.telefone" />
```

---

## Notas de Implementação

1. O Inputmask escolhe automaticamente entre 10 e 11 dígitos.
2. Persistir preferencialmente sem máscara no backend.
3. A validação final continua server-side.

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2           |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |
