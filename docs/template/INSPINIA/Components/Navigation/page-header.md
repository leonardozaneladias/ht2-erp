# Page Header (Page Title + Breadcrumb)

**Categoria:** Navigation
**Origem Inspinia:** `resources/views/shared/partials/page-title.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classes `.page-title-head`, `.page-main-title` do Inspinia
**Documentação Inspinia:** Não há seção dedicada

---

## Descrição

Cabeçalho de página com **título** grande à esquerda, **breadcrumb** à direita (visível apenas em `md+`), e opcional **área de ações** (botões como "Novo Pedido", "Exportar", etc.). Aparece logo abaixo da topbar, no início de cada página. É renderizado automaticamente pelo `<x-admin.layout>` quando uma página recebe `title` e opcionalmente `subtitle`.

---

## Preview Visual

```
┌──────────────────────────────────────────────────────────────────┐
│                                                                  │
│   Pedidos                              Admin → Gestão → Pedidos  │
│                                                                  │
│                                        [+ Novo Pedido] [⬇ CSV]   │
│                                                                  │
├──────────────────────────────────────────────────────────────────┤
│   (conteúdo da página)                                           │
```

- **Título grande** à esquerda (h4 com classe `.page-main-title`)
- **Breadcrumb** à direita apenas em `md+` (oculto no mobile)
- **Ações** (opcional) — botões abaixo ou à direita do breadcrumb
- **Ícones chevron** separadores do breadcrumb via Iconify (`tabler--chevron-right`) com `rtl:rotate-180`

---

## Código Original (Inspinia)

```html
<!-- Page Title Start -->
<div class="page-title-head">
    <h4 class="page-main-title">{{ $title }}</h4>
    <div class="hidden items-center gap-1.25 text-sm md:flex">
        <a class="text-sm" href="#">Inspinia</a>
        <i class="iconify tabler--chevron-right text-sm rtl:rotate-180"></i>
        <a class="text-sm" href="#">{{ $subtitle }}</a>
        <i class="iconify tabler--chevron-right text-sm rtl:rotate-180"></i>
        <a aria-current="page" class="text-default-400 text-sm" href="#">{{ $title }}</a>
    </div>
</div>
<!-- Page Title End -->
```

O original suporta apenas 2 níveis hard-coded ("Inspinia → subtitle → title"). Neste projeto vamos generalizar para N níveis via array.

---

## Componente Blade Proposto

**Nome:** `<x-admin.page-header>`
**Arquivo view:** `resources/views/components/admin/page-header.blade.php`
**Classe PHP:** Blade anônimo — sem classe
**Tipo:** Blade anônimo

### Props

| Prop          | Tipo      | Obrigatório | Default | Descrição                                                                                     |
| ------------- | --------- | :---------: | ------- | --------------------------------------------------------------------------------------------- |
| `title`       | `string`  |     ✅      | —       | Título principal da página                                                                    |
| `subtitle`    | `?string` |     ❌      | `null`  | Subtítulo (seção pai). Se `null`, breadcrumb mostra só "Admin → title"                        |
| `breadcrumbs` | `?array`  |     ❌      | `null`  | Array custom de breadcrumbs `[['label' => 'X', 'url' => '...'], ...]`. Sobrescreve `subtitle` |

### Slots

| Slot       | Descrição                                                               |
| ---------- | ----------------------------------------------------------------------- |
| `$actions` | Botões/elementos de ação à direita do título (ex: "+ Novo", "Exportar") |

### Código do Componente Blade

```blade
{{-- resources/views/components/admin/page-header.blade.php --}}
@props ([
    'title',
    'subtitle' => null,
    'breadcrumbs' => null,
])

@php
    // Se breadcrumbs não vier explícito, construir default: Admin → [Subtitle] → Title
    $crumbs = $breadcrumbs ?? array_filter([
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        $subtitle ? ['label' => $subtitle, 'url' => null] : null,
        ['label' => $title, 'url' => null, 'current' => true],
    ]);
@endphp

<div class="page-title-head flex items-center justify-between mb-6">
    <h4 class="page-main-title">{{ $title }}</h4>

    <div class="flex items-center gap-4">
        @if (isset($actions))
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endif

        <nav class="hidden items-center gap-1.25 text-sm md:flex" aria-label="Breadcrumb">
            @foreach ($crumbs as $i => $crumb)
                @if (!empty($crumb['current']))
                    <span aria-current="page" class="text-default-400 text-sm"> {{ $crumb['label'] }} </span>
                @elseif (!empty($crumb['url']))
                    <a class="text-sm" href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                @else
                    <span class="text-sm">{{ $crumb['label'] }}</span>
                @endif
                @if ($i < count($crumbs) - 1)
                    <i class="iconify tabler--chevron-right text-sm rtl:rotate-180"></i>
                @endif
            @endforeach
        </nav>
    </div>
</div>
```

---

## Exemplos de Uso

### Exemplo 1: Uso automático via layout master

```blade
{{-- resources/views/admin/pedidos/index.blade.php --}}
<x-admin.layout title="Pedidos" subtitle="Cadastros">
    {{-- conteúdo da página --}}
</x-admin.layout>
```

Resultado do breadcrumb: `Admin → Cadastros → Pedidos`

### Exemplo 2: Com botões de ação

```blade
<x-admin.layout title="Pedidos" subtitle="Cadastros">
    <x-slot:actions>
        @can ('pedidos.criar')
            <a href="{{ route('admin.pedidos.create') }}" class="btn btn-primary">
                <i class="iconify tabler--plus"></i> Novo Pedido
            </a>
        @endcan
        @can ('pedidos.exportar')
            <x-shared.button variant="secondary" wire:click="exportarCsv">
                <i class="iconify tabler--download"></i> CSV
            </x-shared.button>
        @endcan
    </x-slot:actions>

    <livewire:admin.pedidos.tabela />
</x-admin.layout>
```

Porém: como `<x-admin.page-header>` é renderizado **dentro** do layout via `<x-admin.page-header :title="$title" :subtitle="$subtitle" />`, o slot `$actions` não chega nele automaticamente. **Solução** (ver nota 2 de Notas de Adaptação abaixo).

### Exemplo 3: Breadcrumb customizado (mais de 2 níveis)

```blade
<x-admin.layout title="Editar Pedido">
    <x-admin.page-header
        title="Editar Pedido"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Cadastros', 'url' => null],
            ['label' => 'Pedidos', 'url' => route('admin.pedidos.index')],
            ['label' => $pedido->codigo, 'url' => route('admin.pedidos.show', $pedido)],
            ['label' => 'Editar', 'url' => null, 'current' => true],
        ]"
    />

    <livewire:admin.pedidos.form :pedido="$pedido" />
</x-admin.layout>
```

Resultado: `Admin → Cadastros → Pedidos → PED-2026-001 → Editar`

### Exemplo 4: Ficha do Cliente

```blade
<x-admin.layout>
    <x-admin.page-header
        :title="$cliente->nome_completo"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Clientes', 'url' => route('admin.clientes.index')],
            ['label' => $cliente->nome_completo, 'url' => null, 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.badge :variant="$cliente->em_dia ? 'success' : 'danger'">
                {{ $cliente->em_dia ? 'Em dia' : 'Em atraso' }}
            </x-shared.badge>
            <x-shared.button variant="secondary" wire:click="editar">
                <i class="iconify tabler--edit"></i> Editar Dados
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.clientes.ficha :cliente="$cliente" />
</x-admin.layout>
```

---

## Quando Usar ✅

- Em todas as views do admin que precisam de título + breadcrumb
- Acima de tabelas de listagem com botão "+ Novo X" via slot `$actions`
- Em telas de edição/show com breadcrumbs de 3+ níveis (passar `:breadcrumbs` explícito)

## Quando NÃO Usar ❌

- Modais e drawers — eles têm seu próprio cabeçalho
- Telas de auth — sem breadcrumb
- Dashboard raiz (`/admin`) — se usar, breadcrumb fica `Admin → Dashboard` o que é redundante. Considerar omitir para dashboard apenas
- Livewire partials que renderizam dentro de outra view — o page-header já está no pai

## Boas Práticas 💡

- **Breadcrumb não é menu:** não colocar links que não refletem a hierarquia da página atual
- **`aria-current="page"`** no item atual: acessibilidade
- **Última entrada sem link:** o item atual NÃO deve ser clicável
- **Mobile:** breadcrumb fica oculto (`hidden md:flex`) — não tentar mostrar em mobile, o título já dá contexto
- **Ações no slot `$actions`:** máximo 3 botões. Se precisar mais, agrupar num dropdown

---

## Classificação

| Critério                   | Valor                  |
| -------------------------- | ---------------------- |
| **Vai usar no projeto**    | 🟢 Sim                 |
| **Complexidade**           | Simples (props + loop) |
| **Status componentização** | 🟢 Concluído           |

---

## Dependências

| Tipo                        | Item                                                       |
| --------------------------- | ---------------------------------------------------------- |
| **Depende de (JS)**         | Nenhum                                                     |
| **Depende de (CSS)**        | Classes `.page-title-head`, `.page-main-title` do Inspinia |
| **Depende de (Iconify)**    | `tabler--chevron-right`                                    |
| **Usado por (views)**       | Todas as views admin (exceto auth e 404)                   |
| **Usado por (componentes)** | `<x-admin.layout>` (render automático)                     |

---

## Notas de Adaptação

1. **Breadcrumb generalizado em N níveis:** o Inspinia suporta 2 níveis hardcoded. Vamos generalizar via prop `$breadcrumbs` (array). O default (usando só `title` + `subtitle`) mantém compatibilidade com o padrão Inspinia mas permite override completo
2. **Slot `$actions` no layout master:** como `<x-admin.page-header>` é renderizado **dentro** do `<x-admin.layout>`, precisa de um caminho para o slot `$actions` chegar. **Solução:** no `<x-admin.layout>`, expor um slot nomeado `actions` que é forwarded:
    ```blade
    {{-- dentro de layout.blade.php --}}
    <x-admin.page-header :title="$title" :subtitle="$subtitle">
        @isset ($actions)
            <x-slot:actions>
                {{ $actions }}
            </x-slot:actions>
        @endisset
    </x-admin.page-header>
    ```
    E a view usa `<x-slot:actions>...</x-slot:actions>` no `<x-admin.layout>`.
3. **Remover links hardcoded `"Inspinia"`** → `route('admin.dashboard')`
4. **Substituir ícone via Iconify `tabler--chevron-right`** — já está OK, manter
5. **`rtl:rotate-180`:** manter para futuro suporte RTL (mesmo que hoje seja PT-BR LTR)
6. **Título dinâmico via Livewire:** quando Livewire component full-page emite `dispatch('title-updated', ...)`, poderíamos recalcular o page-header. **Decisão:** não implementar agora — título vem apenas como prop server-side
7. **Considerar versão "hero":** algumas páginas (ex: Dashboard) podem querer uma variante maior com ícone/descrição. **Criar depois** se surgir necessidade — `<x-admin.page-hero>` separado

## Código Final Blade

Implementação consolidada em `resources/views/components/admin/page-header.blade.php`.

Principais ajustes aplicados no código final:

- o breadcrumb final reutiliza diretamente `x-shared.breadcrumb` em vez de reimplementar a trilha no componente
- o layout forwarda `actions`, `subtitle` e `breadcrumbs` para o page-header
- o componente preserva a API de breadcrumbs customizados, mas mantém um default simples `Admin → subtitle → title`

---

## Changelog do Componente

| Data       | Descrição   |
| ---------- | ----------- |
| 2026-04-11 | Doc criada  |
