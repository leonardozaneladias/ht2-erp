# Button

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/buttons.blade.php`
**Plugins JS:** Nenhum (loading-button usa Ladda — ver `loading-button.md`)
**Plugins CSS:** Classe `.btn` do Inspinia

---

## Descrição

Botão base do sistema. 8 variantes de cor (primary, secondary, success, danger, warning, info, light, dark), 3 estilos (solid, outline, ghost), 3 tamanhos (sm, md, lg), opção de formato pill (`rounded-full`), icon-only e bloco (full-width).

---

## Código Original (Inspinia — essência)

```html
<!-- Solid -->
<button class="btn bg-primary hover:bg-primary-hover text-white" type="button">Primary</button>

<!-- Outline -->
<button class="btn border-primary text-primary hover:bg-primary hover:text-white" type="button">Primary</button>

<!-- Rounded (pill) -->
<button class="btn bg-primary hover:bg-primary-hover rounded-full text-white">Primary</button>

<!-- Default (cinza) -->
<button class="btn border-default-300" type="button">Default</button>

<!-- Icon button -->
<button class="btn btn-icon bg-primary text-white">
    <i class="iconify tabler--plus"></i>
</button>
```

Variantes completas no template: gradient, soft, block (w-full), disabled, com ícones à esquerda/direita.

---

## Componente Blade Final

**Nome:** `<x-shared.button>`
**Arquivo:** `resources/views/components/shared/button.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/button.blade.php`

### Props

| Prop         | Tipo      | Obrigatório | Default     | Descrição                                                                |
| ------------ | --------- | :---------: | ----------- | ------------------------------------------------------------------------ |
| `variant`    | `string`  |     ❌      | `'primary'` | primary, secondary, success, danger, warning, info, light, dark, default |
| `appearance` | `?string` |     ❌      | `null`      | API final: solid, outline, ghost                                         |
| `style`      | `?string` |     ❌      | `null`      | Alias compatível para `appearance` enquanto as demais docs migram        |
| `size`       | `string`  |     ❌      | `'md'`      | sm, md, lg                                                               |
| `pill`       | `bool`    |     ❌      | `false`     | Formato arredondado total                                                |
| `block`      | `bool`    |     ❌      | `false`     | Full-width                                                               |
| `icon`       | `?string` |     ❌      | `null`      | Ícone à esquerda                                                         |
| `iconRight`  | `?string` |     ❌      | `null`      | Ícone à direita                                                          |
| `iconOnly`   | `bool`    |     ❌      | `false`     | Botão só com ícone                                                       |
| `type`       | `string`  |     ❌      | `'button'`  | button, submit, reset                                                    |
| `href`       | `?string` |     ❌      | `null`      | Se informado, renderiza `<a>` em vez de `<button>`                       |
| `disabled`   | `bool`    |     ❌      | `false`     | —                                                                        |

### Código

```blade
{{-- resources/views/components/shared/button.blade.php --}}
@props ([
    'variant' => 'primary',
    'appearance' => null,
    'style' => null,
    'size' => 'md',
    'pill' => false,
    'block' => false,
    'icon' => null,
    'iconRight' => null,
    'iconOnly' => false,
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $allowedVariants = ['default', 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
    $allowedAppearances = ['solid', 'outline', 'ghost'];
    $allowedSizes = ['sm', 'md', 'lg'];

    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'primary';
    $appearance = $appearance ?? $style ?? 'solid';
    $appearance = in_array($appearance, $allowedAppearances, true) ? $appearance : 'solid';
    $size = in_array($size, $allowedSizes, true) ? $size : 'md';

    $solidClasses = [
        'default' => 'border-default-300 bg-card text-default-700 hover:bg-light',
        'primary' => 'bg-primary text-white hover:bg-primary-hover',
        'secondary' => 'bg-secondary text-white hover:bg-secondary-hover',
        'success' => 'bg-success text-white hover:bg-success-hover',
        'danger' => 'bg-danger text-white hover:bg-danger-hover',
        'warning' => 'bg-warning text-white hover:bg-warning-hover',
        'info' => 'bg-info text-white hover:bg-info-hover',
        'light' => 'bg-light text-dark hover:bg-light-hover',
        'dark' => 'bg-dark text-white hover:bg-dark-hover',
    ];

    $outlineClasses = [
        'default' => 'border-default-300 text-default-700 hover:bg-light',
        'primary' => 'border-primary text-primary hover:bg-primary hover:text-white',
        'secondary' => 'border-secondary text-secondary hover:bg-secondary hover:text-white',
        'success' => 'border-success text-success hover:bg-success hover:text-white',
        'danger' => 'border-danger text-danger hover:bg-danger hover:text-white',
        'warning' => 'border-warning text-warning hover:bg-warning hover:text-white',
        'info' => 'border-info text-info hover:bg-info hover:text-white',
        'light' => 'border-light text-dark hover:bg-light hover:text-dark',
        'dark' => 'border-dark text-dark hover:bg-dark hover:text-white',
    ];

    $ghostClasses = [
        'default' => 'text-default-700 hover:bg-light',
        'primary' => 'text-primary hover:bg-primary/15',
        'secondary' => 'text-secondary hover:bg-secondary/15',
        'success' => 'text-success hover:bg-success/15',
        'danger' => 'text-danger hover:bg-danger/15',
        'warning' => 'text-warning hover:bg-warning/15',
        'info' => 'text-info hover:bg-info/15',
        'light' => 'text-dark hover:bg-light',
        'dark' => 'text-dark hover:bg-dark/15',
    ];

    $variantClasses = match ($appearance) {
        'outline' => $outlineClasses[$variant],
        'ghost' => $ghostClasses[$variant],
        default => $solidClasses[$variant],
    };

    $sizeClasses = [
        'sm' => 'btn-sm',
        'md' => null,
        'lg' => 'btn-lg',
    ];

    $iconSizeClasses = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
    ];

    $label = trim(strip_tags((string) $slot));
    $hasLabel = $label !== '';

    $classes = [
        'btn',
        $variantClasses,
        $sizeClasses[$size],
        $pill ? 'rounded-full' : null,
        $block ? 'w-full' : null,
        $iconOnly ? 'btn-icon' : null,
        'inline-flex items-center justify-center transition-all',
        $iconOnly ? 'gap-0' : 'gap-x-2',
        $disabled ? 'pointer-events-none opacity-50' : null,
    ];

    $elementAttributes = $attributes->class($classes);

    if ($iconOnly && $hasLabel && ! $attributes->has('aria-label')) {
        $elementAttributes = $elementAttributes->merge(['aria-label' => $label]);
    }

    $leadingIcon = $icon ?: ($iconOnly ? $iconRight : null);
    $trailingIcon = $iconOnly ? null : $iconRight;
@endphp

@if ($href)
    <a
        @if (! $disabled) href="{{ $href }}" @endif
        {{ $elementAttributes }}
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
    >
        @if ($leadingIcon)
            <i class="iconify {{ $leadingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif

        @if ($hasLabel)
            <span @class (['sr-only' => $iconOnly])>{{ $slot }}</span>
        @endif

        @if ($trailingIcon)
            <i class="iconify {{ $trailingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $elementAttributes }} @disabled ($disabled)>
        @if ($leadingIcon)
            <i class="iconify {{ $leadingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif

        @if ($hasLabel)
            <span @class (['sr-only' => $iconOnly])>{{ $slot }}</span>
        @endif

        @if ($trailingIcon)
            <i class="iconify {{ $trailingIcon }} {{ $iconSizeClasses[$size] }} shrink-0"></i>
        @endif
    </button>
@endif
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.button variant="primary">Salvar</x-shared.button>
<x-shared.button variant="danger" icon="tabler--trash">Excluir</x-shared.button>
<x-shared.button variant="default" appearance="outline">Cancelar</x-shared.button>
<x-shared.button variant="success" size="sm" pill>Aprovar</x-shared.button>
<x-shared.button variant="primary" icon-only icon="tabler--plus" />
```

### Real (Form de Pedidos)

```blade
<div class="flex justify-end gap-2 mt-6">
    <x-shared.button variant="default" appearance="outline" :href="route('admin.pedidos.index')">
        Cancelar
    </x-shared.button>
    <x-shared.loading-button variant="primary" type="submit" wire:target="salvar">
        Salvar Pedido
    </x-shared.loading-button>
</div>
```

### Real (Header de listagem)

```blade
<x-slot:actions>
    @can ('pedidos.criar')
        <x-shared.button variant="primary" icon="tabler--plus" :href="route('admin.pedidos.create')">
            Novo Pedido
        </x-shared.button>
    @endcan
    @can ('pedidos.exportar')
        <x-shared.button variant="default" appearance="outline" icon="tabler--download" wire:click="exportarCsv">
            CSV
        </x-shared.button>
    @endcan
</x-slot:actions>
```

---

## Quando Usar ✅

- **Ações primárias/destrutivas** em forms, modais e headers
- **Navegação** via `href` (evita styling manual de `<a>`)
- **Icon buttons** em tabelas (editar, deletar, ver)

## Quando NÃO Usar ❌

- Links de texto inline → usar `<a>` com classe `text-primary`
- Botões de submit que precisam de loading → usar `<x-shared.loading-button>`
- Ações em dropdown → usar `.dropdown-item` dentro de `<x-shared.dropdown>`

---

## Classificação

| Critério         | Valor                                   |
| ---------------- | --------------------------------------- |
| **Vai usar**     | 🟢 Sim (primitivo universal)            |
| **Complexidade** | Média (muitas props, mas código direto) |
| **Status**       | 🟢 Concluído                            |

---

## Notas de Adaptação

1. **API final usa `appearance`** — `style` permanece como alias compatível para não quebrar exemplos antigos ainda não migrados
2. **Variant `default` suportado** — útil para cancelamento, ações secundárias e botões neutros do admin
3. **Sem safelist manual:** o componente usa mapas explícitos de classes por variant/appearance, evitando concatenação dinâmica
4. **`<a>` vs `<button>`** continua automático via prop `href`; links desabilitados não recebem `href` final e ganham `aria-disabled`
5. **Classe base `.btn`** já existe no projeto em `resources/css/custom/_buttons.css`, junto com `btn-sm`, `btn-lg` e `btn-icon`
6. **`iconOnly`** recebe `aria-label` automaticamente quando há texto no slot; para casos sem slot, informar `aria-label` explicitamente
7. **Ghost style** segue como adição do projeto, agora padronizado em tom suave (`hover:bg-variant/15`) para combinar com a linguagem do Inspinia
8. **Preview pronto:** acessar `/admin/dev/components/button` para validar tamanhos, links, pill e icon-only
