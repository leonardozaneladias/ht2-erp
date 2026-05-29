# Badge

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/badges.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Pequeno chip colorido para exibir status, contagem ou tag. 8 variantes de cor (primary, secondary, success, danger, warning, info, light, dark) com 2 intensidades (soft `bg/15 text` e solid `bg text-white`), 3 formatos (retângulo `rounded`, pill `rounded-full`, square `rounded-none`), e 3 tamanhos (sm, md, lg). Opção de ícone.

> **Relacionado:** `<x-shared.status-badge>` (ver `status-badge.md`) é uma especialização que recebe um Enum e escolhe cor/label automaticamente.

---

## Código Original (Inspinia — essência)

```html
<!-- Soft -->
<span class="bg-primary/15 text-primary rounded px-2 py-0.5 text-xs font-semibold">Primary</span>

<!-- Solid -->
<span class="bg-success rounded px-2 py-0.5 text-xs font-semibold text-white">Success</span>

<!-- Pill -->
<span class="bg-warning/15 text-warning rounded-full px-2.5 py-0.5 text-xs font-semibold">Warning</span>

<!-- Com ícone -->
<span class="bg-info/15 text-info inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold">
    <i class="iconify tabler--info-circle text-sm"></i>
    Info
</span>

<!-- Contador -->
<span class="relative inline-block">
    <button class="btn">Notifications</button>
    <span
        class="bg-danger absolute end-0 top-0 flex size-4 translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full text-xs text-white"
    >
        3
    </span>
</span>
```

---

## Componente Blade Final

**Nome:** `<x-shared.badge>`
**Arquivo:** `resources/views/components/shared/badge.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/badge.blade.php`

### Props

| Prop      | Tipo      | Obrigatório | Default     | Descrição                                                                  |
| --------- | --------- | :---------: | ----------- | -------------------------------------------------------------------------- |
| `variant` | `string`  |     ❌      | `'primary'` | `default`, primary, secondary, success, danger, warning, info, light, dark |
| `solid`   | `bool`    |     ❌      | `false`     | Fundo sólido em vez de soft                                                |
| `pill`    | `bool`    |     ❌      | `false`     | Formato pill (rounded-full)                                                |
| `size`    | `string`  |     ❌      | `'md'`      | sm, md, lg                                                                 |
| `icon`    | `?string` |     ❌      | `null`      | Ícone Iconify à esquerda                                                   |

### Código

```blade
{{-- resources/views/components/shared/badge.blade.php --}}
@props ([
    'variant' => 'primary',
    'solid' => false,
    'pill' => false,
    'size' => 'md',
    'icon' => null,
])

@php
    $variants = [
        'default' => [
            'soft' => 'border border-default-300 bg-light/70 text-default-700',
            'solid' => 'bg-default-700 text-white',
        ],
        'primary' => [
            'soft' => 'bg-primary/15 text-primary',
            'solid' => 'bg-primary text-white',
        ],
        'secondary' => [
            'soft' => 'bg-secondary/15 text-secondary',
            'solid' => 'bg-secondary text-white',
        ],
        'success' => [
            'soft' => 'bg-success/15 text-success',
            'solid' => 'bg-success text-white',
        ],
        'danger' => [
            'soft' => 'bg-danger/15 text-danger',
            'solid' => 'bg-danger text-white',
        ],
        'warning' => [
            'soft' => 'bg-warning/15 text-warning',
            'solid' => 'bg-warning text-white',
        ],
        'info' => [
            'soft' => 'bg-info/15 text-info',
            'solid' => 'bg-info text-white',
        ],
        'light' => [
            'soft' => 'bg-light text-dark',
            'solid' => 'bg-light text-dark',
        ],
        'dark' => [
            'soft' => 'bg-dark/15 text-dark',
            'solid' => 'bg-dark text-white',
        ],
    ];

    $sizes = [
        'sm' => 'px-1.5 py-0.5 text-2xs',
        'md' => null,
        'lg' => 'px-3 py-1 text-sm',
    ];

    $variant = array_key_exists($variant, $variants) ? $variant : 'primary';
    $size = array_key_exists($size, $sizes) ? $size : 'md';
    $tone = $solid ? 'solid' : 'soft';
@endphp

<span
    {{ $attributes->class([
    'badge whitespace-nowrap',
    $variants[$variant][$tone],
    $sizes[$size],
    $pill ? 'rounded-full' : null,
]) }}
>
    @if ($icon)
        <i class="iconify {{ $icon }} text-sm shrink-0"></i>
    @endif

    {{ $slot }}
</span>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.badge variant="success">Ativo</x-shared.badge>
<x-shared.badge variant="danger" solid>Cancelado</x-shared.badge>
<x-shared.badge variant="warning" pill icon="tabler--clock">Pendente</x-shared.badge>
<x-shared.badge variant="info" size="sm">Novo</x-shared.badge>
```

### Real (Tabela de pedidos)

```blade
<td>
    <x-shared.badge :variant="$pedido->em_dia ? 'success' : 'danger'" pill>
        {{ $pedido->em_dia ? 'Em dia' : 'Em atraso' }}
    </x-shared.badge>
</td>
```

### Real (Badge de contagem no sidebar)

```blade
<li class="menu-item relative">
    <a class="menu-link" href="{{ route('admin.financeiro.pagamentos.index', ['status' => 'vencido']) }}">
        <span class="menu-icon"><i class="iconify tabler--alert-triangle"></i></span>
        <span class="menu-text">Pagamentos Vencidos</span>
        @if ($contadorVencidas > 0)
            <x-shared.badge variant="danger" pill size="sm" solid class="ms-auto">
                {{ $contadorVencidas }}
            </x-shared.badge>
        @endif
    </a>
</li>
```

---

## Quando Usar ✅

- Status de linha em tabela
- Contador de notificações (ver `topbar.md`)
- Tags/categorias (ex: "Destaque" em produtos)
- Badges de estado ("ATIVA", "FUTURA", "EXPIRADA")

## Quando NÃO Usar ❌

- Botão clicável → usar `<x-shared.button>` variant "ghost" small
- Alerta de página inteira → usar `<x-shared.alert>`
- Quando precisa de Enum-driven color → usar `<x-shared.status-badge>`

---

## Classificação

| Critério         | Valor                            |
| ---------------- | -------------------------------- |
| **Vai usar**     | 🟢 Sim (extremamente recorrente) |
| **Complexidade** | Trivial                          |
| **Status**       | 🟢 Concluído                     |

---

## Notas de Adaptação

1. **Variant `default` suportado** — alinhado com o restante do design system e com os exemplos de tabelas/status usados em outras docs do projeto
2. **Sem safelist manual:** as variantes são resolvidas por mapa explícito de classes, evitando purge acidental
3. **Classe base `.badge` reaproveitada** — a implementação final usa o CSS já existente em `resources/css/custom/_badge.css`
4. **`whitespace-nowrap`** mantém badges compactos mesmo em células estreitas de tabela
5. **Não suportar `href`** continua intencional — se o badge precisar ser clicável, envolver o componente em um `<a>`
6. **Preview pronto:** acessar `/admin/dev/components/badge` para validar tamanhos, tons e uso em contexto de listagem
