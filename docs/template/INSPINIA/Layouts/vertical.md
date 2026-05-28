# Layout Vertical (Master do Admin)

**Categoria:** Layout
**Origem Inspinia:** `resources/views/shared/vertical.blade.php`, `resources/views/shared/base.blade.php`
**Plugins JS:** Preline 4.0.1 (base de tudo), Simplebar 6.3.3 (scroll customizado)
**Plugins CSS:** Apenas Tailwind 4
**Documentação Inspinia:** `Docs/index.html` § "Html Structure" e § "Layout"

---

## Descrição

Layout master do admin do Portal ArtFinal. Envolve toda view administrativa com `<html>` + `<head>` (com script crítico de tema) + `<body>` contendo o wrapper de 3 áreas: **topbar** fixa no topo, **sidenav** fixa à esquerda, **content-page** rolável no centro. Padrão "vertical orientation" do Inspinia, com posição `fixed` e width `fluid`.

---

## Preview Visual

```
┌─────────────────────────────────────────────────────────────┐
│  TOPBAR (h-topbar fixa)                                     │
│  [☰] Logo   [Search]            [🌙][🔔][👤 Usuário Admin] │
├───────────┬─────────────────────────────────────────────────┤
│           │                                                 │
│  SIDENAV  │   page-title-head                              │
│   (fixa)  │   ────────────────────                          │
│           │                                                 │
│  User     │   @yield('content')                             │
│  Profile  │                                                 │
│           │                                                 │
│  Main     │                                                 │
│  ├ Dash   │                                                 │
│  ├ ...    │                                                 │
│           │                                                 │
│  Apps     │                                                 │
│  ├ ...    │                                                 │
│           │                                                 │
│           │                                                 │
│           ├─────────────────────────────────────────────────┤
│           │   FOOTER                                        │
└───────────┴─────────────────────────────────────────────────┘
```

### Características

- **3 regiões fixas:** topbar no topo (`h-topbar`), sidenav à esquerda (`.app-menu`), content-page ocupa o restante com scroll próprio
- **Width fluid:** sem containers max-width (Portal ArtFinal optou por fluid)
- **Position fixed:** topbar e sidenav ficam fixos, apenas `content-page` rola
- **Responsividade:** abaixo de 1140px, o script no `head-css.blade.php` força automaticamente `sidenav-size=offcanvas` (sidenav vira drawer mobile)
- **Dark mode:** aplicado via `<html data-theme="dark">` — todas as seções respeitam

---

## Código Original (Inspinia)

### Base HTML (`shared/base.blade.php`)

```blade
<!DOCTYPE html>
<html @yield ("html_attribute") lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <meta content="width=device-width, initial-scale=1" name="viewport" />

    @yield ("styles")
    @include ("shared.partials/head-css")
</head>
<body @yield ("body_attribute")>
    @yield ("content")
    @yield ("scripts")
</body>
</html>
```

### Layout Vertical completo (`shared/vertical.blade.php`)

```blade
<!DOCTYPE html>
<html @yield ("html_attribute")>
<head>
    @include ("shared.partials/title-meta")
    @yield ("styles")
    @include ("shared.partials/head-css")
</head>
<body>
    <div class="wrapper">
        @include ("shared.partials/topbar")
        @include ("shared.partials/sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @yield ("content")
            </div>
            @include ("shared.partials/footer")
        </div>

        @include ("shared.partials/customizer")
    </div>

    @yield ("scripts")
</body>
</html>
```

---

## Componente Blade Proposto

**Nome:** `<x-admin.layout>`
**Arquivo view:** `resources/views/components/admin/layout.blade.php`
**Classe PHP:** Blade anônimo — sem classe
**Tipo:** Blade anônimo

### Props

| Prop       | Tipo      | Obrigatório | Default | Descrição                                             |
| ---------- | --------- | :---------: | ------- | ----------------------------------------------------- |
| `title`    | `string`  |     ✅      | —       | Título da página (aparece em `<title>` e page-header) |
| `subtitle` | `?string` |     ❌      | `null`  | Subtítulo do breadcrumb                               |

### Slots

| Slot              | Descrição                                                         |
| ----------------- | ----------------------------------------------------------------- |
| `$slot` (default) | Conteúdo principal da página                                      |
| `$styles`         | CSS extra para injetar antes do head-css (ex.: estilos de página) |
| `$scripts`        | JS extra para injetar ao fim do body (ex.: init de ApexCharts)    |

### Código do Componente Blade

```blade
{{-- resources/views/components/admin/layout.blade.php --}}
@props ([
    'title',
    'subtitle' => null,
])

<!DOCTYPE html>
<html
    lang="pt-BR"
    data-theme="light"
    data-skin="default"
    data-layout-width="fluid"
    data-layout-position="fixed"
    data-topbar-color="light"
    data-menu-color="dark"
    data-sidenav-size="default"
    data-sidenav-user="true"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title }} | Portal ArtFinal</title>
    <meta name="description" content="Sistema de gerenciamento de formaturas — Portal ArtFinal" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" />

    {{ $styles ?? '' }}

    <x-admin.partials.theme-bootstrap />

    @vite (['resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body>
    <div class="wrapper">
        <x-admin.topbar />
        <x-admin.sidebar />

        <div class="content-page">
            <div class="container-fluid">
                <x-admin.page-header :title="$title" :subtitle="$subtitle" />
                {{ $slot }}
            </div>
            <x-admin.footer />
        </div>
    </div>

    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>
```

> **Nota:** O `<x-admin.partials.theme-bootstrap />` é um Blade anônimo que contém apenas o script inline de tema (extraído de `head-css.blade.php`) — ver doc `Layouts/skins.md`.

---

## Exemplos de Uso

### Exemplo Básico

```blade
<x-admin.layout title="Dashboard">
    <div class="grid grid-cols-4 gap-4">
        {{-- KPIs, gráficos, etc. --}}
    </div>
</x-admin.layout>
```

### Exemplo Real (Portal ArtFinal — Tela 14.2 Dashboard)

```blade
{{-- resources/views/admin/dashboard/index.blade.php --}}
<x-admin.layout title="Dashboard" subtitle="Visão Gerencial">
    {{-- KPI Cards (topo, grid 4) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-admin.kpi-card
            label="Contratos Ativos"
            :value="$kpis->contratosAtivos"
            icon="tabler--file-text"
            color="primary"
        />
        <x-admin.kpi-card
            label="Formandos Aderidos"
            :value="$kpis->formandosAderidos"
            icon="tabler--users"
            color="success"
        />
        <x-admin.kpi-card
            label="Receita a Receber"
            :value="MoneyHelper::format($kpis->receitaPendenteCentavos)"
            icon="tabler--cash"
            color="warning"
        />
        <x-admin.kpi-card
            label="Inadimplência"
            :value="$kpis->percentualInadimplencia . '%'"
            icon="tabler--alert-triangle"
            color="danger"
        />
    </div>

    {{-- Gráficos (grid 2) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <livewire:admin.dashboard.grafico-adesoes-mensais />
        <livewire:admin.dashboard.grafico-receita-inadimplencia />
    </div>

    {{-- Meta por contrato --}}
    <x-admin.card title="Meta de Formandos por Contrato">
        <livewire:admin.dashboard.tabela-meta-contratos />
    </x-admin.card>
</x-admin.layout>
```

### Exemplo com Styles/Scripts Extras

```blade
<x-admin.layout title="Relatórios">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('css/print.css') }}" media="print" />
    </x-slot:styles>

    <livewire:admin.relatorios.listagem />

    <x-slot:scripts>
        <script>
            window.addEventListener('report-ready', () => window.print());
        </script>
    </x-slot:scripts>
</x-admin.layout>
```

---

## Quando Usar ✅

- Em TODAS as views do admin (`/admin/*`) — é o layout master
- Views autenticadas que exigem sidenav + topbar
- Em qualquer Livewire full-page component do admin via `->layout('components.admin.layout')`

## Quando NÃO Usar ❌

- **Telas de auth do admin** (login, reset, 404, 500) — usar layout mínimo `<x-admin.auth-layout>` baseado em `shared/base.blade.php` (sem sidenav/topbar)
- **Portal do formando** (`/portal/*`) — usar `<x-portal.layout>` próprio com identidade visual diferente (mobile-first, sem sidenav)
- **Documentos impressos/PDFs** — usar layout nu para DomPDF

## Boas Práticas 💡

- O `data-theme` e `data-menu-color` saem do script de head-css.blade.php quando o usuário alterna. **Não definir no servidor** — deixar o script cuidar ao ler sessionStorage
- Para **Livewire full-page**, usar `#[Layout('components.admin.layout')]` ou `protected $layout`. A prop `title` vem via `#[Title('...')]`
- O `container-fluid` já aplica o padding lateral correto. **Não adicionar `container` ou `mx-auto`** dentro — quebra o fluid
- Se a página tiver breadcrumb customizado (mais de 2 níveis), passar `subtitle` como string HTML não é suportado pelo `<x-admin.page-header>` atual — documentar extensão se necessário

---

## Mapeamento no PRD (Portal ArtFinal)

| Tela                                  | Seção PRD | Como É Usado                     | Sprint |
| ------------------------------------- | --------- | -------------------------------- | :----: |
| Dashboard Administrativo              | 14.2      | Envolve KPIs, gráficos e tabelas |   16   |
| Gestão de Instituições                | 14.3      | Envolve DataTable + form         |   17   |
| Gestão de Contratos                   | 14.4      | Envolve DataTable + form tabs    |   17   |
| Gestão de Produtos                    | 14.6      | Envolve DataTable + form tabs    |   18   |
| Gestão de Formandos                   | 14.12     | Envolve DataTable + ficha tabs   |   20   |
| Gestão Financeira                     | 14.13     | Envolve filtros + DataTable      |   21   |
| Simulador                             | 14.14     | Envolve form + card resultado    |   22   |
| Configurações Globais                 | 14.15     | Envolve form agrupado            |   23   |
| **Todas as 20 telas** de 14.2 a 14.20 | —         | Layout master único              | 15–24  |

---

## Classificação

| Critério                   | Valor                                    |
| -------------------------- | ---------------------------------------- |
| **Vai usar no projeto**    | 🟢 Sim (pré-requisito absoluto)          |
| **Prioridade**             | P0 (Sprint 15–16)                        |
| **Sprint planejada**       | 16 (finalização do shell admin)          |
| **Complexidade**           | Média (compõe múltiplos sub-componentes) |
| **Status componentização** | 🟢 Concluído                             |

---

## Dependências

| Tipo                         | Item                                                                                                             |
| ---------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| **Depende de (JS)**          | Preline 4.0.1, Simplebar 6.3.3, Iconify (tabler icons)                                                           |
| **Depende de (componentes)** | `x-admin.topbar`, `x-admin.sidebar`, `x-admin.footer`, `x-admin.page-header`, `x-admin.partials.theme-bootstrap` |
| **Usado por (telas)**        | TODAS as views do admin (20 telas)                                                                               |
| **Usado por (componentes)**  | Nenhum — é o layout raiz                                                                                         |

---

## Notas de Adaptação

Diferenças entre o Inspinia original e o que usaremos no Portal ArtFinal:

1. **Remoção do `@include("shared.partials/customizer")`** — o customizer é um painel lateral de customização visual do template (alterna skins, cores, size). Para o admin do ArtFinal é sobra de template; o skin será fixo (`default`) e não exporemos ao usuário. **Remover completamente**.
2. **Remoção do `@include("shared.partials/title-meta")`** — descrições/keywords específicas do Inspinia não aplicam. Colocar meta tags inline no layout.
3. **`lang="en"` → `lang="pt-BR"`** — localização do projeto.
4. **`@vite(['resources/js/vendor.js', 'resources/js/app.js'])` → `@vite(['resources/css/admin.css', 'resources/js/admin.js'])`** — entry points próprios do ArtFinal conforme `vite.config.js` do projeto (dois entries admin + portal, ver `CLAUDE.md` §5.2).
5. **Livewire integration** — adicionar `@livewireStyles` no `<head>` e `@livewireScripts` antes de `</body>`.
6. **CSRF token meta** — `<meta name="csrf-token" content="{{ csrf_token() }}">` para chamadas Livewire/Axios.
7. **Favicon** — substituir `/images/favicon.ico` por `asset('favicon.ico')` próprio do ArtFinal.
8. **Page-header dentro do container-fluid** — o Inspinia chama `page-title` manualmente em cada view. Vamos incluir automaticamente via prop `$title` para reduzir boilerplate.
9. **Classes `.wrapper`, `.content-page`, `.container-fluid`, `.footer`** — permanecem vindo do starter CSS do Inspinia. A implementação atual usa `resources/css/admin.css` como entrypoint dedicado, importando `app.css` como base do shell admin.

## Código Final Blade

Implementação consolidada em:

- `resources/views/components/admin/layout.blade.php`
- `resources/views/admin/layouts/app.blade.php` (adapter de compatibilidade)

Principais ajustes aplicados no código final:

- `title` agora pode ser omitido; quando presente, o `<x-admin.page-header>` é renderizado automaticamente
- `breadcrumbs` pode ser passado diretamente ao layout e é forwardado para o page-header
- `actions`, `styles` e `scripts` são forwardados via slots nomeados
- `@vite(['resources/css/admin.css', 'resources/js/admin.js'])` virou o contrato real do shell
- o adapter `admin/layouts/app.blade.php` preserva compatibilidade com `@extends` e `#[Layout(...)]`

---

## Changelog do Componente

| Data       | Descrição                  |
| ---------- | -------------------------- |
| 2026-04-11 | Doc criada — Fase 2 Onda 1 |
