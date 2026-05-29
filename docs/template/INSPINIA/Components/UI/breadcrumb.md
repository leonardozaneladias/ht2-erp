# Breadcrumb

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/breadcrumb.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Apenas Tailwind + Iconify (tabler)

---

## Descrição

Navegação hierárquica horizontal mostrando o caminho até a página atual. Usa `<nav>` + `<ol>` semântico, com chevrons Iconify como divisores. Último item não é clicável e tem `aria-current="page"`.

> **Overlap com `<x-admin.page-header>`:** o page-header já inclui breadcrumb próprio à direita do título. Este componente é a versão **standalone** para casos isolados (ex: dentro de um card, drawer ou modal).

---

## Código Original (Inspinia — essência)

```html
<nav class="py-2.5">
    <ol class="flex items-center whitespace-nowrap">
        <li class="inline-flex items-center">
            <a class="text-default-600 hover:text-primary flex items-center font-medium" href="#">Home</a>
            <i class="iconify tabler--chevron-right text-default-400 m-0.75 pe-1 text-base"></i>
        </li>
        <li class="inline-flex items-center">
            <a class="text-default-600 hover:text-primary flex items-center font-medium" href="#">Library</a>
            <i class="iconify tabler--chevron-right text-default-400 m-0.75 pe-1 text-base"></i>
        </li>
        <li aria-current="page" class="text-default-400 inline-flex items-center truncate font-medium">Data</li>
    </ol>
</nav>
```

Variantes no template: com ícones nos crumbs, em card colored, com dividers alternativos (slash, dot).

---

## Componente Blade Final

**Nome:** `<x-shared.breadcrumb>`
**Arquivo:** `resources/views/components/shared/breadcrumb.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/breadcrumb.blade.php`

### Props

| Prop      | Tipo     | Obrigatório | Default                   | Descrição                                                                      |
| --------- | -------- | :---------: | ------------------------- | ------------------------------------------------------------------------------ |
| `items`   | `array`  |     ✅      | —                         | Lista de crumbs `[['label' => 'X', 'url' => '...', 'icon' => 'tabler--home']]` |
| `divider` | `string` |     ❌      | `'tabler--chevron-right'` | Ícone Iconify ou texto puro (`/`, `•`) usado como separador                    |

### Código

```blade
{{-- resources/views/components/shared/breadcrumb.blade.php --}}
@props ([
    'items' => [],
    'divider' => 'tabler--chevron-right',
])

@php
    $items = collect(is_array($items) ? $items : [])
        ->filter(fn (array $item) => filled($item['label'] ?? null))
        ->values()
        ->all();

    $dividerIsIcon = str_contains((string) $divider, '--');
@endphp

@if ($items !== [])
    <nav aria-label="Breadcrumb" {{ $attributes->class(['py-2.5']) }}>
        <ol class="flex flex-wrap items-center gap-y-1 whitespace-nowrap text-sm">
            @foreach ($items as $index => $item)
                @php
                    $label = $item['label'];
                    $url = $item['url'] ?? $item['href'] ?? null;
                    $icon = $item['icon'] ?? null;
                    $isLast = ($item['current'] ?? false) || $index === array_key_last($items);
                @endphp
                <li
                    @class ([
                    'inline-flex max-w-full items-center gap-1.5',
                    'text-default-400 font-medium' => $isLast,
                ])
                    @if ($isLast) aria-current="page" @endif
                >
                    @if (! $isLast && filled($url))
                        <a
                            href="{{ $url }}"
                            class="inline-flex min-w-0 items-center gap-1 text-default-600 font-medium transition hover:text-primary"
                        >
                            @if ($icon)
                                <i class="iconify {{ $icon }} shrink-0 text-sm"></i>
                            @endif

                            <span>{{ $label }}</span>
                        </a>
                    @else
                        <span class="inline-flex min-w-0 items-center gap-1 truncate">
                            @if ($icon)
                                <i class="iconify {{ $icon }} shrink-0 text-sm"></i>
                            @endif

                            <span class="truncate">{{ $label }}</span>
                        </span>
                    @endif

                    @unless ($isLast)
                        @if ($dividerIsIcon)
                            <i class="iconify {{ $divider }} text-default-400 text-base rtl:rotate-180"></i>
                        @else
                            <span class="px-1 text-default-400" aria-hidden="true">{{ $divider }}</span>
                        @endif
                    @endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.breadcrumb
    :items="[
    ['label' => 'Admin', 'url' => route('admin.dashboard'), 'icon' => 'tabler--home'],
    ['label' => 'Pedidos', 'url' => route('admin.pedidos.index')],
    ['label' => 'Editar'],
]"
/>
```

### Real (dentro de um card de ficha de cliente)

```blade
<x-shared.card>
    <x-shared.breadcrumb
        :items="[
        ['label' => 'Clientes', 'url' => route('admin.clientes.index')],
        ['label' => $cliente->categoria->nome, 'url' => route('admin.categorias.show', $cliente->categoria)],
        ['label' => $cliente->nome_completo],
    ]"
    />
    {{-- demais campos da ficha --}}
</x-shared.card>
```

---

## Quando Usar ✅

- Dentro de modais/drawers que precisem indicar contexto
- Em pages onde `<x-admin.page-header>` já tem outro breadcrumb customizado e você quer um secundário interno
- Em views que **não** usam o layout master (raro)

## Quando NÃO Usar ❌

- No topo de uma view usando `<x-admin.layout>` — o `<x-admin.page-header>` já cobre
- Para navegação horizontal tipo menu — use `<x-admin.sidebar>`
- Como linkagem contextual (ex: "ver também") — use link normal

---

## Classificação

| Critério         | Valor                   |
| ---------------- | ----------------------- |
| **Vai usar**     | 🟢 Sim (uso secundário) |
| **Complexidade** | Simples                 |
| **Status**       | 🟢 Concluído            |

---

## Notas de Adaptação

1. **Lista filtrada antes do render** — a implementação final ignora items sem `label`, reduzindo erro silencioso em arrays incompletos
2. **`url` ou `href`** são aceitos como chave de destino, o que facilita integração com payloads já existentes do projeto
3. **`divider` aceita ícone ou texto** — útil para contextos compactos dentro de cards e drawers
4. **Sem links falsos:** quando um item intermediário não recebe URL, ele vira texto simples em vez de cair para `#`
5. **Preview pronto:** acessar `/admin/dev/components/breadcrumb` para validar breadcrumbs com ícone e divisor textual
