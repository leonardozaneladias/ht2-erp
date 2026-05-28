# Page Header (Page Title + Breadcrumb)

**Categoria:** Navigation
**Origem Inspinia:** `resources/views/shared/partials/page-title.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classes `.page-title-head`, `.page-main-title` do Inspinia
**Documentação Inspinia:** Não há seção dedicada

---

## Descrição

Cabeçalho de página com **título** grande à esquerda, **breadcrumb** à direita (visível apenas em `md+`), e opcional **área de ações** (botões como "Novo Contrato", "Exportar", etc.). Aparece logo abaixo da topbar, no início de cada página. No Portal ArtFinal, será renderizado automaticamente pelo `<x-admin.layout>` quando uma página recebe `title` e opcionalmente `subtitle`.

---

## Preview Visual

```
┌──────────────────────────────────────────────────────────────────┐
│                                                                  │
│   Contratos                            Admin → Gestão → Contratos│
│                                                                  │
│                                        [+ Novo Contrato] [⬇ CSV] │
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

O original suporta apenas 2 níveis hard-coded ("Inspinia → subtitle → title"). Para o ArtFinal vamos generalizar para N níveis via array.

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
{{-- resources/views/admin/contratos/index.blade.php --}}
<x-admin.layout title="Contratos" subtitle="Cadastros">
    {{-- conteúdo da página --}}
</x-admin.layout>
```

Resultado do breadcrumb: `Admin → Cadastros → Contratos`

### Exemplo 2: Com botões de ação

```blade
<x-admin.layout title="Contratos" subtitle="Cadastros">
    <x-slot:actions>
        @can ('contratos.criar')
            <a href="{{ route('admin.contratos.create') }}" class="btn btn-primary">
                <i class="iconify tabler--plus"></i> Novo Contrato
            </a>
        @endcan
        @can ('contratos.exportar')
            <x-shared.button variant="secondary" wire:click="exportarCsv">
                <i class="iconify tabler--download"></i> CSV
            </x-shared.button>
        @endcan
    </x-slot:actions>

    <livewire:admin.contratos.tabela />
</x-admin.layout>
```

Porém: como `<x-admin.page-header>` é renderizado **dentro** do layout via `<x-admin.page-header :title="$title" :subtitle="$subtitle" />`, o slot `$actions` não chega nele automaticamente. **Solução** (ver nota 2 de Notas de Adaptação abaixo).

### Exemplo 3: Breadcrumb customizado (mais de 2 níveis)

```blade
<x-admin.layout title="Editar Contrato">
    <x-admin.page-header
        title="Editar Contrato"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Cadastros', 'url' => null],
            ['label' => 'Contratos', 'url' => route('admin.contratos.index')],
            ['label' => $contrato->codigo_turma, 'url' => route('admin.contratos.show', $contrato)],
            ['label' => 'Editar', 'url' => null, 'current' => true],
        ]"
    />

    <livewire:admin.contratos.form :contrato="$contrato" />
</x-admin.layout>
```

Resultado: `Admin → Cadastros → Contratos → 2026-ENG-NOT → Editar`

### Exemplo 4: Ficha do Formando (14.12)

```blade
<x-admin.layout>
    <x-admin.page-header
        :title="$formando->nome_completo"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formandos', 'url' => route('admin.formandos.index')],
            ['label' => $formando->nome_completo, 'url' => null, 'current' => true],
        ]"
    >
        <x-slot:actions>
            <x-shared.badge :variant="$formando->adimplente ? 'success' : 'danger'">
                {{ $formando->adimplente ? 'Em dia' : 'Inadimplente' }}
            </x-shared.badge>
            <x-shared.button variant="secondary" wire:click="editar">
                <i class="iconify tabler--edit"></i> Editar Dados
            </x-shared.button>
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.formandos.ficha :formando="$formando" />
</x-admin.layout>
```

---

## Quando Usar ✅

- Em todas as views do admin que precisam de título + breadcrumb (20 telas do 14.\*)
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

## Mapeamento no PRD (Portal ArtFinal)

| Tela                | Seção PRD | Breadcrumb sugerido                           | Ações no header                       | Sprint |
| ------------------- | --------- | --------------------------------------------- | ------------------------------------- | :----: |
| Dashboard           | 14.2      | `Admin → Dashboard` (ou omitir)               | —                                     |   16   |
| Instituições (list) | 14.3      | `Admin → Cadastros → Instituições`            | `+ Nova Instituição`, `CSV`           |   17   |
| Contratos (list)    | 14.4      | `Admin → Cadastros → Contratos`               | `+ Novo Contrato`, `CSV`              |   17   |
| Contratos (edit)    | 14.4      | `Admin → ... → Contratos → [código] → Editar` | `Cancelar`, `Salvar` (também no form) |   17   |
| Produtos (list)     | 14.6      | `Admin → Cadastros → Produtos`                | `+ Novo Produto`                      |   18   |
| Termos (list)       | 14.11     | `Admin → Cadastros → Termos`                  | `+ Novo Termo`                        |   19   |
| Formandos (list)    | 14.12     | `Admin → Gestão → Formandos`                  | `+ Cadastro Manual`, `Exportar`       |   20   |
| Formandos (ficha)   | 14.12     | `Admin → Gestão → Formandos → [nome]`         | Badge status, `Editar`                |   20   |
| Parcelas            | 14.13     | `Admin → Financeiro → Parcelas`               | `Exportar`, `Baixa em Lote`           |   21   |
| Simulador           | 14.14     | `Admin → Financeiro → Simulador`              | —                                     |   22   |
| Configurações       | 14.15     | `Admin → Configurações → Globais`             | `Salvar`                              |   23   |
| Relatórios          | 14.17     | `Admin → Financeiro → Relatórios`             | Filtros                               |   22   |
| Usuários Admin      | 14.18     | `Admin → Configurações → Usuários`            | `+ Novo Usuário`                      |   24   |
| Perfis ACL          | 14.19     | `Admin → Configurações → Perfis`              | `+ Novo Perfil`                       |   24   |

---

## Classificação

| Critério                   | Valor                  |
| -------------------------- | ---------------------- |
| **Vai usar no projeto**    | 🟢 Sim                 |
| **Prioridade**             | P0                     |
| **Sprint planejada**       | 16                     |
| **Complexidade**           | Simples (props + loop) |
| **Status componentização** | 🟢 Concluído           |

---

## Dependências

| Tipo                        | Item                                                       |
| --------------------------- | ---------------------------------------------------------- |
| **Depende de (JS)**         | Nenhum                                                     |
| **Depende de (CSS)**        | Classes `.page-title-head`, `.page-main-title` do Inspinia |
| **Depende de (Iconify)**    | `tabler--chevron-right`                                    |
| **Usado por (telas)**       | Todas as 20 telas admin (exceto auth e 404)                |
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
7. **Considerar versão "hero":** algumas páginas (14.2 Dashboard) podem querer uma variante maior com ícone/descrição. **Criar depois** se surgir necessidade — `<x-admin.page-hero>` separado

## Código Final Blade

Implementação consolidada em `resources/views/components/admin/page-header.blade.php`.

Principais ajustes aplicados no código final:

- o breadcrumb final reutiliza diretamente `x-shared.breadcrumb` em vez de reimplementar a trilha no componente
- o layout forwarda `actions`, `subtitle` e `breadcrumbs` para o page-header
- o componente preserva a API de breadcrumbs customizados, mas mantém um default simples `Admin → subtitle → title`

---

## Changelog do Componente

| Data       | Descrição                  |
| ---------- | -------------------------- |
| 2026-04-11 | Doc criada — Fase 2 Onda 1 |
