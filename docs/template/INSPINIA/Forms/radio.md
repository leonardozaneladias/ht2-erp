# Radio

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/elements.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** `.form-radio` do Inspinia

---

## Descrição

Radio button para **seleção única exclusiva** entre múltiplas opções. Usado em casos onde select dropdown não seria suficiente (ex: mostrar 2-3 opções visualmente com descrições).

---

## Componente Blade Proposto

**Nome oficial:** `<x-shared.radio>`  
**Subcomponente necessário:** `<x-shared.radio-group>`

### `<x-shared.radio-group>`

```blade
{{-- resources/views/components/shared/radio-group.blade.php --}}
@props ([
    'name',
    'label' => null,
    'inline' => false,
])

@php $hasError = $errors->has($name); @endphp

<div class="mb-4">
    @if ($label)
        <div class="form-label mb-2">{{ $label }}</div>
    @endif
    <div @class (['flex gap-4', 'flex-col' => !$inline, 'flex-row' => $inline])> {{ $slot }}</div>
    @if ($hasError)
        <small class="text-danger mt-1 block text-xs">{{ $errors->first($name) }}</small>
    @endif
</div>
```

### `<x-shared.radio>`

```blade
{{-- resources/views/components/shared/radio.blade.php --}}
@props ([
    'name',
    'value',
    'label',
    'description' => null,
])

<label class="flex items-start gap-2 cursor-pointer">
    <input
        name="{{ $name }}"
        type="radio"
        value="{{ $value }}"
        {{ $attributes->class(['form-radio text-primary mt-0.5 focus:ring-primary']) }}
    />
    <span>
        <span class="block font-medium">{{ $label }}</span>
        @if ($description)
            <span class="block text-xs text-default-400">{{ $description }}</span>
        @endif
    </span>
</label>
```

---

## Exemplos de Uso

### Simples inline

```blade
<x-shared.radio-group name="status" label="Status" inline>
    <x-shared.radio name="status" value="ativo" label="Ativo" wire:model="form.status" />
    <x-shared.radio name="status" value="inativo" label="Inativo" wire:model="form.status" />
</x-shared.radio-group>
```

### Com descrições (modalidade pagamento)

```blade
<x-shared.radio-group name="modalidade" label="Modalidade de Pagamento">
    <x-shared.radio
        name="modalidade"
        value="boleto"
        label="Boleto Bancário"
        description="Vencimento mensal, à vista ou em até 12x"
        wire:model.live="modalidade"
    />
    <x-shared.radio
        name="modalidade"
        value="cartao"
        label="Cartão de Crédito"
        description="Cobrança recorrente automática"
        wire:model.live="modalidade"
    />
    <x-shared.radio
        name="modalidade"
        value="pix"
        label="PIX"
        description="À vista com desconto"
        wire:model.live="modalidade"
    />
</x-shared.radio-group>
```

---

## Classificação

| Critério         | Valor                      |
| ---------------- | -------------------------- |
| **Vai usar**     | 🟡 Sim (casos específicos) |
| **Complexidade** | Trivial                    |
| **Status**       | 🟢 Concluído               |

---

## Notas de Adaptação

1. **Usar select** quando tiver muitas opções ou não precisar de descrição
2. **Usar radio** quando precisar exibir 2-3 opções com descrição visual
3. **`name` obrigatório igual** em todos os radios do mesmo grupo
4. **Validação:** `'modalidade' => 'required|in:boleto,cartao,pix'`

---

## Código Final Blade

- **Arquivos finais:**
    - `resources/views/components/shared/radio.blade.php`
    - `resources/views/components/shared/radio-group.blade.php`
- **Preview visual:** `/admin/dev/components/radio`

### API final

- `x-shared.radio-group`: `name`, `label`, `hint`, `inline`
- `x-shared.radio`: `name`, `id`, `value`, `label`, `description`, `checked`
- ajuste oficial:
    - o catálogo mantém `x-shared.radio` como item do Batch 4
    - `x-shared.radio-group` ficou oficializado como subcomponente necessário para label, hint e erro do grupo

### Exemplo final

```blade
<x-shared.radio-group name="modalidade" label="Modalidade de Pagamento">
    <x-shared.radio
        name="modalidade"
        value="boleto"
        label="Boleto Bancário"
        description="Conciliação operacional padrão."
        checked
    />
    <x-shared.radio name="modalidade" value="pix" label="PIX" description="Confirmação mais rápida para baixa." />
</x-shared.radio-group>
```
