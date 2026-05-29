# Input

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/elements.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classes `.form-input`, `.form-label`, `.input-icon-group` do Inspinia

---

## Descrição

Input de texto padrão. Base de todos os demais campos de formulário (text, email, number, password, search, url, tel, date). Integra com Livewire via `wire:model`, com `@error` blade para validação, e suporta label, helping text, ícone, prefix/suffix, estados (valid/invalid/disabled).

---

## Código Original (Inspinia — essência)

```html
<!-- Básico -->
<label class="form-label" for="nome">Nome</label>
<input class="form-input" id="nome" type="text" />

<!-- Com ícone -->
<div class="input-icon-group">
    <i class="iconify tabler--mail input-icon"></i>
    <input class="form-input" type="email" placeholder="Email" />
</div>

<!-- Valid state -->
<input class="form-input border-success!" type="text" />

<!-- Helping text -->
<input class="form-input" type="text" />
<small class="text-default-400 mt-1 block text-xs">Texto de ajuda.</small>
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.input>`
**Arquivo:** `resources/views/components/shared/input.blade.php`

### Props

| Prop       | Tipo      | Obrigatório | Default  | Descrição                                             |
| ---------- | --------- | :---------: | -------- | ----------------------------------------------------- |
| `name`     | `string`  |     ✅      | —        | Nome do campo (usado em `wire:model` também)          |
| `label`    | `?string` |     ❌      | `null`   | Label acima do input                                  |
| `type`     | `string`  |     ❌      | `'text'` | text, email, number, password, search, tel, url, date |
| `icon`     | `?string` |     ❌      | `null`   | Ícone Iconify à esquerda                              |
| `hint`     | `?string` |     ❌      | `null`   | Texto de ajuda abaixo                                 |
| `required` | `bool`    |     ❌      | `false`  | Marca como obrigatório (asterisco no label)           |

### Código

```blade
{{-- resources/views/components/shared/input.blade.php --}}
@props ([
    'name',
    'label' => null,
    'type' => 'text',
    'icon' => null,
    'hint' => null,
    'required' => false,
])

@php
    $hasError = $errors->has($name);
@endphp

<div class="mb-4">
    @if ($label)
        <label class="form-label" for="{{ $name }}">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    @if ($icon)
        <div class="input-icon-group">
            <i class="iconify {{ $icon }} input-icon"></i>
            <input
                id="{{ $name }}"
                name="{{ $name }}"
                type="{{ $type }}"
                {{ $attributes->class([
                       'form-input',
                       'border-danger!' => $hasError,
                   ]) }}
                @required ($required)
            />
        </div>
    @else
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            {{ $attributes->class([
                   'form-input',
                   'border-danger!' => $hasError,
               ]) }}
            @required ($required)
        />
    @endif

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs">{{ $errors->first($name) }}</small>
    @elseif ($hint)
        <small class="text-default-400 mt-1 block text-xs">{{ $hint }}</small>
    @endif
</div>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.input name="nome" label="Nome Completo" required wire:model="usuario.nome" />
<x-shared.input name="email" label="E-mail" type="email" icon="tabler--mail" wire:model="usuario.email" />
<x-shared.input name="meta" label="Meta" type="number" hint="Opcional" wire:model="pedido.meta" />
```

### Real (cadastro)

```blade
<x-shared.input name="razao_social" label="Razão Social" required wire:model="form.razao_social" />
<x-shared.input name="nome_fantasia" label="Nome Fantasia" required wire:model="form.nome_fantasia" />
```

---

## Quando Usar ✅

- Todo campo de texto/email/number/password em forms Livewire
- Base para inputs especializados (CPF, CNPJ, CEP, etc.)

## Quando NÃO Usar ❌

- Textarea → `<x-shared.textarea>`
- Select → `<x-shared.select>` ou `<x-shared.select-search>`
- Masked inputs → `<x-shared.cpf-input>`, etc.

---

## Classificação

| Critério         | Valor                        |
| ---------------- | ---------------------------- |
| **Vai usar**     | 🟢 Sim (primitivo universal) |
| **Complexidade** | Simples                      |
| **Status**       | 🟢 Concluído                 |

---

## Notas de Adaptação

1. **`@error` integrado** — usa `$errors->has($name)` + `$errors->first($name)` do Laravel
2. **Asterisco `*` vermelho** no label quando required
3. **Attribute forwarding** — permite passar `wire:model`, `placeholder`, `autocomplete`, etc.
4. **`mb-4`** como padrão — espaçamento entre campos. Remover se usar em grid
5. **Base para masked inputs** — CPF/CNPJ/CEP/Phone/Money usam este como fundação + Alpine para máscara

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/input.blade.php`
- **Preview visual:** `/admin/dev/components/input`

### API final

- props: `name`, `id`, `label`, `type`, `icon`, `hint`, `required`
- ajustes aplicados:
    - `id` passou a ser opcional, com fallback derivado de `name`
    - o componente agora expõe `aria-invalid` e `aria-describedby`
    - funciona mesmo quando `$errors` não está presente no contexto da view

### Exemplo final

```blade
<x-shared.input
    name="email"
    label="E-mail"
    type="email"
    icon="tabler--mail"
    hint="Usado para notificações operacionais."
    required
/>
```
