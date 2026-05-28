# Checkbox

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/elements.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** `.form-checkbox` do Inspinia

---

## Descrição

Checkbox padrão. Diferente do toggle, checkbox é usado para **seleção múltipla** em listas (ACL matrix 14.19, seleção de parcelas 14.13) ou opções independentes.

---

## Componente Blade Proposto

**Nome:** `<x-shared.checkbox>`
**Arquivo:** `resources/views/components/shared/checkbox.blade.php`

```blade
@props ([
    'name',
    'label' => null,
    'value' => '1',
])

<label class="inline-flex items-center gap-2 cursor-pointer">
    <input
        name="{{ $name }}"
        type="checkbox"
        value="{{ $value }}"
        {{ $attributes->class(['form-checkbox rounded text-primary focus:ring-primary']) }}
    />
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</label>
```

---

## Exemplos de Uso

### Lembrar-me 14.1

```blade
<x-shared.checkbox name="remember" label="Lembrar-me" wire:model="remember" />
```

### Matriz ACL 14.19

```blade
<table class="table">
    <thead>
        <tr>
            <th>Módulo</th>
            <th>Listar</th>
            <th>Criar</th>
            <th>Editar</th>
            <th>Excluir</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($modulos as $modulo)
            <tr>
                <td>{{ $modulo->nome }}</td>
                @foreach (['listar', 'criar', 'editar', 'excluir'] as $acao)
                    <td>
                        <x-shared.checkbox
                            :name="$modulo->slug . '_' . $acao"
                            wire:model="permissoes.{{ $modulo->slug }}.{{ $acao }}"
                        />
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
```

### Seleção múltipla (14.13 Baixa em lote)

```blade
@foreach ($parcelas as $parcela)
    <x-shared.checkbox :value="$parcela->id" wire:model="selecionadas" name="selecionadas[]" />
@endforeach
```

---

## Mapeamento no PRD

| Tela             | Uso                                 |
| ---------------- | ----------------------------------- |
| 14.1 Login       | "Lembrar-me"                        |
| 14.13 Parcelas   | Seleção múltipla para baixa em lote |
| 14.19 Perfis ACL | Matriz de permissões                |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2 (Onda 3)  |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Para on/off de estado** → usar `<x-shared.toggle>` (mais claro visualmente)
2. **Para seleção de lista** → usar checkbox
3. **`wire:model` em array:** `wire:model="selecionadas"` com `value="{{ $id }}"` e name `"selecionadas[]"`
4. **Preliminar Spatie permissions:** o Livewire controla `$permissoes` array, converte para `syncPermissions()` no save

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/checkbox.blade.php`
- **Preview visual:** `/admin/dev/components/checkbox`

### API final

- props: `name`, `id`, `label`, `value`, `hint`, `checked`
- ajustes aplicados:
    - agora suporta `hint`, estado inicial `checked` e mensagens de erro
    - mantém foco em seleção múltipla e flags independentes, sem competir com `x-shared.toggle`

### Exemplo final

```blade
<x-shared.checkbox name="remember" label="Lembrar-me neste dispositivo" checked />
```
