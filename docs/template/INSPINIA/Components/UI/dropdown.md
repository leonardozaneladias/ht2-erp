# Dropdown

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/dropdowns.blade.php`
**Plugins JS:** Preline 4.0.1 (`hs-dropdown`, `hs-dropdown-toggle`, `hs-dropdown-menu`)
**Plugins CSS:** Classes `hs-dropdown-*` + `dropdown-item`

---

## Descrição

Menu dropdown clicável (ou hover). Usado para **menu de ações por linha** em DataTables (Editar, Excluir, Duplicar), **user menu** no topbar, **placement de notificações**, etc. Suporta placement (top, bottom, left, right com variants -start/-end), trigger por click ou hover, items com ícones, dividers, headers.

---

## Código Original (Inspinia — essência)

```html
<div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
    <button type="button" class="hs-dropdown-toggle btn bg-light text-dark" aria-expanded="false" aria-haspopup="menu">
        Ações
        <i class="iconify tabler--chevron-down hs-dropdown-open:rotate-180 transition-transform"></i>
    </button>
    <div class="hs-dropdown-menu" role="menu" aria-orientation="vertical">
        <a class="dropdown-item" href="#"> <i class="iconify tabler--edit me-1"></i> Editar </a>
        <a class="dropdown-item" href="#"> <i class="iconify tabler--copy me-1"></i> Duplicar </a>
        <div class="my-1 border-t"></div>
        <a class="dropdown-item text-danger" href="#"> <i class="iconify tabler--trash me-1"></i> Excluir </a>
    </div>
</div>
```

### Atributos úteis

- `[--placement:bottom-right]` — posição (top/bottom/left/right com variants start/end)
- `[--trigger:hover]` — abrir no hover em vez de click
- `[--auto-close:inside]` — não fecha ao clicar no item

---

## Componente Blade Final

**Nome:** `<x-shared.dropdown>`
**Arquivo:** `resources/views/components/shared/dropdown.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/dropdown.blade.php`

### Props

| Prop        | Tipo     | Obrigatório | Default        | Descrição                                                              |
| ----------- | -------- | :---------: | -------------- | ---------------------------------------------------------------------- |
| `placement` | `string` |     ❌      | `'bottom-end'` | top, bottom, left, right, bottom-start, bottom-end, bottom-right, etc. |
| `trigger`   | `string` |     ❌      | `'click'`      | click, hover                                                           |
| `autoClose` | `string` |     ❌      | `'true'`       | `'true'`, `'inside'`, `'false'`                                        |

### Slots

| Slot              | Descrição                                            |
| ----------------- | ---------------------------------------------------- |
| `$button`         | O botão trigger (todo markup, incluindo classes btn) |
| `$slot` (default) | Os itens do menu (usar `<x-shared.dropdown-item>`)   |

### Código — dropdown

```blade
{{-- resources/views/components/shared/dropdown.blade.php --}}
@props ([
    'placement' => 'bottom-end',
    'trigger' => 'click',
    'autoClose' => 'true',
])

@php
    $trigger = in_array($trigger, ['click', 'hover'], true) ? $trigger : 'click';
    $autoClose = in_array((string) $autoClose, ['true', 'inside', 'false'], true) ? (string) $autoClose : 'true';
    $wrapperClasses = collect([
        'hs-dropdown relative inline-flex',
        "[--placement:{$placement}]",
        $trigger === 'hover' ? '[--trigger:hover]' : null,
        $autoClose !== 'true' ? "[--auto-close:{$autoClose}]" : null,
    ])->filter()->all();
@endphp

<div {{ $attributes->class($wrapperClasses) }}>
    @isset ($button)
        {{ $button }}
    @else
        <button
            type="button"
            class="hs-dropdown-toggle btn bg-light text-dark hover:text-primary"
            aria-expanded="false"
            aria-haspopup="menu"
        >
            Ações
            <i class="iconify tabler--chevron-down text-base transition-transform hs-dropdown-open:rotate-180"></i>
        </button>
    @endisset

    <div class="hs-dropdown-menu" role="menu" aria-orientation="vertical">{{ $slot }}</div>
</div>
```

### `<x-shared.dropdown-item>` (item individual)

```blade
{{-- resources/views/components/shared/dropdown-item.blade.php --}}
@props ([
    'href' => null,
    'icon' => null,
    'variant' => null,
    'active' => false,
    'disabled' => false,
])

@php
    $variantClasses = [
        'default' => 'text-default-700',
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'warning' => 'text-warning',
        'info' => 'text-info',
        'light' => 'text-dark',
        'dark' => 'text-dark',
    ];

    $classes = [
        'dropdown-item text-start',
        $variantClasses[$variant] ?? null,
        $active ? 'active' : null,
        $disabled ? 'pointer-events-none opacity-50' : null,
    ];
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} role="menuitem">
        @if ($icon)
            <i class="iconify {{ $icon }} shrink-0 text-base"></i>
        @endif

        <span>{{ $slot }}</span>
    </a>
@elseif ($href)
    <span {{ $attributes->class($classes) }} aria-disabled="true" role="menuitem">
        @if ($icon)
            <i class="iconify {{ $icon }} shrink-0 text-base"></i>
        @endif

        <span>{{ $slot }}</span>
    </span>
@else
    <button type="button" {{ $attributes->class($classes) }} @disabled ($disabled) role="menuitem">
        @if ($icon)
            <i class="iconify {{ $icon }} shrink-0 text-base"></i>
        @endif

        <span>{{ $slot }}</span>
    </button>
@endif
```

### `<x-shared.dropdown-divider>` (divisor)

```blade
{{-- resources/views/components/shared/dropdown-divider.blade.php --}}
<div {{ $attributes->class(['dropdown-divider']) }} role="separator"></div>
```

---

## Exemplos de Uso

### Menu de ações por linha (DataTable)

```blade
<x-shared.dropdown placement="bottom-end">
    <x-slot:button>
        <button type="button" class="hs-dropdown-toggle btn btn-icon btn-sm bg-light text-dark">
            <i class="iconify tabler--dots-vertical"></i>
        </button>
    </x-slot:button>

    @can ('contratos.editar')
        <x-shared.dropdown-item icon="tabler--edit" :href="route('admin.contratos.edit', $contrato)">
            Editar
        </x-shared.dropdown-item>
    @endcan

    <x-shared.dropdown-item icon="tabler--eye" :href="route('admin.contratos.show', $contrato)">
        Ver Formandos
    </x-shared.dropdown-item>

    @can ('contratos.duplicar')
        <x-shared.dropdown-item icon="tabler--copy" wire:click="duplicar({{ $contrato->id }})">
            Duplicar Contrato
        </x-shared.dropdown-item>
    @endcan

    <x-shared.dropdown-divider />

    @can ('contratos.inativar')
        <x-shared.dropdown-item
            icon="tabler--ban"
            variant="danger"
            wire:click="confirmarInativacao({{ $contrato->id }})"
        >
            Inativar
        </x-shared.dropdown-item>
    @endcan
</x-shared.dropdown>
```

### User menu (alternativo ao hardcoded no topbar)

```blade
<x-shared.dropdown placement="bottom-end">
    <x-slot:button>
        <button class="hs-dropdown-toggle flex items-center gap-2">
            <img src="{{ $user->avatar_url }}" class="size-8 rounded-full" />
            <span class="hidden md:inline">{{ $user->nome }}</span>
            <i class="iconify tabler--chevron-down"></i>
        </button>
    </x-slot:button>

    <x-shared.dropdown-item icon="tabler--user-circle" :href="route('admin.perfil.show')">
        Meu Perfil
    </x-shared.dropdown-item>
    <x-shared.dropdown-item icon="tabler--settings" :href="route('admin.conta.edit')">
        Configurações
    </x-shared.dropdown-item>
    <x-shared.dropdown-divider />
    <x-shared.dropdown-item icon="tabler--logout" variant="danger" wire:click="logout"> Sair </x-shared.dropdown-item>
</x-shared.dropdown>
```

---

## Quando Usar ✅

- Menu de ações por linha em tabelas (todas as 14.\*)
- User menu no topbar
- Seletores de filtro compacto
- Menu "mais opções" em toolbars

## Quando NÃO Usar ❌

- Select de formulário → usar `<x-shared.select>` ou `<x-shared.select-search>`
- Navegação principal → usar sidebar
- Dialog com conteúdo longo → usar drawer/modal

---

## Mapeamento no PRD

| Tela                     | Seção PRD   | Uso                                                                                 |
| ------------------------ | ----------- | ----------------------------------------------------------------------------------- |
| Topbar                   | —           | User menu, notificações                                                             |
| Todas tabelas 14.3–14.13 | —           | Menu "Ações" por linha                                                              |
| Produtos                 | 14.6        | Dropdown com 6 ações (Editar, Programações, Condições, Descontos, Termos, Inativar) |
| Formandos                | 14.12 Tab 5 | Ações por parcela                                                                   |

---

## Classificação

| Critério         | Valor                        |
| ---------------- | ---------------------------- |
| **Vai usar**     | 🟢 Sim (primitivo universal) |
| **Prioridade**   | P1 (Onda 2)                  |
| **Complexidade** | Média                        |
| **Status**       | 🟢 Concluído                 |

---

## Notas de Adaptação

1. **3 componentes reais:** `dropdown`, `dropdown-item`, `dropdown-divider` — composição implementada e pronta para reuso
2. **CSS já disponível:** `.hs-dropdown-menu`, `.dropdown-item` e `.dropdown-divider` já estão no projeto via `resources/css/custom/_dropdown.css`
3. **`placement="bottom-end"`** virou o default por ser o caso mais comum em tabelas e menus de ação
4. **`<x-slot:button>`** continua sendo a forma recomendada; a implementação final só adiciona um fallback simples para preview/debug
5. **`dropdown-item`** agora suporta `active` e `disabled`, além de variants explícitas sem depender de concatenação dinâmica
6. **`wire:click`** e `href` seguem funcionando normalmente; itens desabilitados não disparam ação nem navegação
7. **Preview pronto:** acessar `/admin/dev/components/dropdown` para validar action menu e user menu
