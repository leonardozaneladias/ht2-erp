# Password Input

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/elements.blade.php` + `plugins/pass-meter.blade.php`
**Plugins JS:** Helper JS leve em `resources/js/admin/forms.js`. Medidor de força via `pass-meter.md`
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Input de senha com **toggle de visibilidade** (olho/olho-cortado). Opcionalmente integra com o medidor de força (`pass-meter.md`) para forms de cadastro/alteração.

---

## Componente Blade Proposto

**Nome:** `<x-shared.password-input>`
**Arquivo:** `resources/views/components/shared/password-input.blade.php`

```blade
@props ([
    'name',
    'label' => 'Senha',
    'hint' => null,
    'required' => false,
    'withMeter' => false,
])

@php $hasError = $errors->has($name); @endphp

<div class="mb-4" x-data="{ show: false }">
    @if ($label)
        <label class="form-label" for="{{ $name }}">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            :type="show ? 'text' : 'password'"
            {{ $attributes->class([
                   'form-input pe-10',
                   'border-danger!' => $hasError,
               ]) }}
            @required ($required)
            @if ($withMeter) data-pass-meter @endif
        />

        <button
            type="button"
            @click="show = !show"
            class="absolute end-3 top-1/2 -translate-y-1/2 text-default-400 hover:text-default-600"
            aria-label="Alternar visibilidade"
        >
            <i class="iconify" x-bind:class="show ? 'tabler--eye-off' : 'tabler--eye'"></i>
        </button>
    </div>

    @if ($withMeter)
        <div class="mt-2 h-1 bg-default-200 rounded-full overflow-hidden" data-pass-meter-indicator>
            <div class="h-full transition-all" data-pass-meter-bar style="width: 0%"></div>
        </div>
        <small class="text-xs text-default-400 mt-1 block" data-pass-meter-label>Digite uma senha</small>
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

### Login (14.1)

```blade
<x-shared.password-input name="password" label="Senha" wire:model="password" required />
```

### Cadastro admin com força (14.18)

```blade
<x-shared.password-input
    name="password"
    label="Senha"
    hint="Mínimo 8 caracteres, incluindo letras e números"
    with-meter
    wire:model="form.password"
    required
/>

<x-shared.password-input
    name="password_confirmation"
    label="Confirmar Senha"
    wire:model="form.password_confirmation"
    required
/>
```

---

## Mapeamento no PRD

| Tela                  | Uso                           |
| --------------------- | ----------------------------- |
| 14.1 Login Admin      | Senha com toggle              |
| 14.18 Usuários Admin  | Cadastro com medidor de força |
| 14.18 Reset de senha  | Nova senha com meter          |
| Portal wizard etapa 5 | Senha do portal_user          |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2 (Onda 3)  |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Toggle via helper JS leve** em `resources/js/admin/forms.js` — evita depender de Alpine implícito no shell atual
2. **`pe-10`** — padding end para acomodar o botão olho
3. **`with-meter`** opcional — integra o subcomponente oficial `x-shared.password-strength-meter` com o mesmo helper JS dos forms base
4. **Validação Laravel:** `'password' => ['required', Password::min(8)->letters()->numbers()]`
5. **Livewire reset:** ao submeter, chamar `$this->reset('password', 'password_confirmation')` por segurança

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/password-input.blade.php`
- **Dependência JS final:** `resources/js/admin/forms.js`
- **Preview visual:** `/admin/dev/components/password-input`

### API final

- props: `name`, `id`, `label`, `hint`, `required`, `withMeter`
- ajustes aplicados:
    - o toggle de visibilidade e o meter opcional são inicializados pelo helper JS do admin
    - o componente preserva a API `with-meter` no Blade, mas não depende mais de Alpine embutido no shell
    - quando `with-meter` está ativo, o componente renderiza `x-shared.password-strength-meter` internamente

### Exemplo final

```blade
<x-shared.password-input
    name="password"
    label="Senha temporária"
    hint="Mínimo de 8 caracteres, com letras, números e símbolos."
    with-meter
    required
/>
```
