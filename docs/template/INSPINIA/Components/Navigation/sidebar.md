# Sidebar (Sidenav)

**Categoria:** Navigation
**Origem Inspinia:** `resources/views/shared/partials/sidenav.blade.php`
**Plugins JS:** Preline 4.0.1 (accordion via `hs-accordion-group`), Simplebar 6.3.3 (scroll custom)
**Plugins CSS:** Classes `.app-menu`, `.side-nav`, `.menu-item`, `.menu-link`, `.menu-title`, `.sub-menu`, `.sidenav-user` — CSS próprio do Inspinia
**Documentação Inspinia:** `Docs/index.html` § "Layout" (config de sidenav-size e sidenav-color)

---

## Descrição

Menu lateral de navegação do admin. Contém logo da empresa no topo, card opcional com dados do usuário logado (sidenav-user), menu hierárquico com seções (menu-title), itens simples (menu-link) e grupos collapsáveis (hs-accordion). Suporta 6 tamanhos (default, compact, condensed, on-hover, on-hover-active, offcanvas) e 5 cores (light, dark, gray, gradient, image) controlados por atributos no `<html>`.

---

## Preview Visual

```
┌──────────────────────┐
│  [LOGO ARTFINAL]     │  ← logo-box (logo-lg + logo-sm variants)
├──────────────────────┤
│                      │
│  [👤 Admin Nome]     │  ← sidenav-user (opcional, com dropdown de settings)
│      Super Admin [⚙] │
├──────────────────────┤
│  PRINCIPAL           │  ← menu-title (separador de seção)
│  🏠 Dashboard        │  ← menu-item (link direto)
│                      │
│  CADASTROS           │
│  🏢 Instituições     │
│  📄 Contratos     ▾  │  ← menu-item.hs-accordion (com submenu)
│    Todos             │
│    Novo              │
│  📦 Produtos      ▾  │
│    Todos             │
│    Categorias        │
│    Termos            │
│                      │
│  GESTÃO              │
│  👨‍🎓 Formandos    ▾  │
│  💰 Financeiro    ▾  │
│                      │
│  CONFIGURAÇÕES       │
│  ⚙ Configurações  ▾  │
│  📋 Auditoria        │
└──────────────────────┘
```

### Estados visuais

- **Item ativo:** `.menu-link.active` com destaque (borda lateral, bg, text color)
- **Accordion aberto:** `.hs-accordion.active` + chevron girado
- **Hover:** transição de cor no `.menu-link`
- **On-hover sidebar:** quando `data-sidenav-size="on-hover"`, expandida apenas ao passar o mouse

---

## Código Original (Inspinia)

### Estrutura geral

```html
<aside class="app-menu" id="app-menu">
    <!-- Logo Area -->
    <a class="logo-box" href="/">
        <span class="logo logo-light">
            <span class="logo-lg"><img src="/images/logo.png" /></span>
            <span class="logo-sm"><img src="/images/logo-sm.png" /></span>
        </span>
        <span class="logo logo-dark">
            <span class="logo-lg"><img src="/images/logo-black.png" /></span>
            <span class="logo-sm"><img src="/images/logo-sm.png" /></span>
        </span>
    </a>

    <!-- Toggle Button (modo on-hover) -->
    <div class="h-topbar justify absolute end-5 top-0 flex items-center">
        <button id="button-hover-toggle">
            <span class="btn-on-hover-icon"></span>
        </button>
    </div>

    <!-- Scrollable Area -->
    <div class="relative min-h-0 grow">
        <div class="size-full" data-simplebar="">
            <!-- Sidenav User Card (opcional) -->
            <div class="sidenav-user bg-[url(/images/user-bg-pattern.svg)] p-5" id="user-profile-settings">
                <div class="flex items-center justify-between">
                    <div>
                        <a class="link-reset" href="#!">
                            <img alt="user" class="mb-3 size-9 rounded-full" src="/images/users/user-1.jpg" />
                            <span class="sidenav-user-name block font-bold text-nowrap">Damian D.</span>
                            <span class="text-xs font-semibold">Art Director</span>
                        </a>
                    </div>
                    <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                        <button><i class="iconify tabler--settings ms-1 size-6 align-middle"></i></button>
                        <div class="hs-dropdown-menu" role="menu">
                            <a class="dropdown-item" href="#!">Profile</a>
                            <a class="dropdown-item" href="#!">Account Settings</a>
                            <a class="dropdown-item" href="/auth/lock-screen">Lock Screen</a>
                            <a class="dropdown-item text-danger" href="#!">Log Out</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu -->
            <div id="sidenav-menu">
                <ul class="side-nav hs-accordion-group px-2.5 pb-16.5">
                    <!-- Section Title -->
                    <li class="menu-title mt-0!">
                        <span>Main</span>
                    </li>

                    <!-- Item with Submenu (accordion) -->
                    <li class="menu-item hs-accordion">
                        <a
                            class="hs-accordion-toggle menu-link"
                            href="javascript:void(0)"
                            aria-controls="dashboards"
                            aria-expanded="false"
                        >
                            <span class="menu-icon"><i class="iconify tabler--dashboard"></i></span>
                            <span class="menu-text">Dashboards</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="sub-menu hs-accordion-content hs-accordion-group hidden">
                            <li class="menu-item">
                                <a class="menu-link" href="/dashboard/ecommerce">
                                    <span class="menu-text">Ecommerce</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a class="menu-link" href="/dashboard/analytics">
                                    <span class="menu-text">Analytics</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Item without Submenu (direct link) -->
                    <li class="menu-item">
                        <a class="menu-link" href="/apps/calendar">
                            <span class="menu-icon"><i class="iconify tabler--calendar"></i></span>
                            <span class="menu-text">Calendar</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</aside>
```

O arquivo original tem **1600+ linhas** com 6 seções de menu-title (Main, Apps, Custom Pages, Layouts, Components, Menu Items) e dezenas de itens — é showcase do template. **Nenhum desses menus aplica ao ArtFinal** — vamos extrair apenas a estrutura.

---

## Componente Blade Proposto

**Nome:** `<x-admin.sidebar>`
**Arquivo view:** `resources/views/components/admin/sidebar.blade.php`
**Classe PHP:** Blade anônimo — sem classe (menu hardcoded)
**Tipo:** Blade anônimo

### Props

Nenhuma. O menu do ArtFinal é fixo por enquanto (não vem de banco). Se no futuro quisermos menu dinâmico baseado em permissões, promover para class-based com `@inject`.

### Slots

Nenhum. Toda a estrutura interna é hardcoded no componente.

### Código do Componente Blade

```blade
{{-- resources/views/components/admin/sidebar.blade.php --}}

@php
    // Cada item do menu do ArtFinal — gate por permissão Spatie
    $user = auth('admin')->user();
@endphp

<aside class="app-menu" id="app-menu">
    {{-- Logo --}}
    <a class="logo-box" href="{{ route('admin.dashboard') }}">
        <span class="logo logo-light">
            <span class="logo-lg">
                <img alt="ArtFinal" src="{{ asset('images/admin/logo.png') }}" />
            </span>
            <span class="logo-sm">
                <img alt="ArtFinal" src="{{ asset('images/admin/logo-sm.png') }}" />
            </span>
        </span>
        <span class="logo logo-dark">
            <span class="logo-lg">
                <img alt="ArtFinal" src="{{ asset('images/admin/logo-dark.png') }}" />
            </span>
            <span class="logo-sm">
                <img alt="ArtFinal" src="{{ asset('images/admin/logo-sm-dark.png') }}" />
            </span>
        </span>
    </a>

    {{-- Toggle on-hover --}}
    <div class="h-topbar justify absolute end-5 top-0 flex items-center">
        <button id="button-hover-toggle" aria-label="Toggle sidenav hover mode">
            <span class="btn-on-hover-icon"></span>
        </button>
    </div>

    {{-- Área rolável --}}
    <div class="relative min-h-0 grow">
        <div class="size-full" data-simplebar>
            {{-- Card do usuário --}}
            @if ($user)
                <div class="sidenav-user p-5" id="user-profile-settings">
                    <div class="flex items-center justify-between">
                        <div>
                            <a class="link-reset" href="{{ route('admin.perfil.show') }}">
                                <img
                                    alt="{{ $user->nome }}"
                                    class="mb-3 size-9 rounded-full"
                                    src="{{ $user->avatar_url ?? asset('images/admin/avatar-default.png') }}"
                                />
                                <span class="sidenav-user-name block font-bold text-nowrap"> {{ $user->nome }} </span>
                                <span class="text-xs font-semibold"> {{ $user->perfil?->nome ?? 'Sem perfil' }} </span>
                            </a>
                        </div>
                        <div class="hs-dropdown relative inline-flex [--placement:bottom-right]">
                            <button aria-label="Menu do usuário">
                                <i class="iconify tabler--settings ms-1 size-6 align-middle"></i>
                            </button>
                            <div class="hs-dropdown-menu" role="menu">
                                <a class="dropdown-item" href="{{ route('admin.perfil.show') }}">
                                    <i class="iconify tabler--user-circle me-1 align-middle text-lg"></i>
                                    Meu Perfil
                                </a>
                                <a class="dropdown-item" href="{{ route('admin.conta.edit') }}">
                                    <i class="iconify tabler--settings-2 me-1 align-middle text-lg"></i>
                                    Configurações
                                </a>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger w-full text-start">
                                        <i class="iconify tabler--logout me-1 align-middle text-lg"></i>
                                        Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Menu principal --}}
            <div id="sidenav-menu">
                <ul class="side-nav hs-accordion-group px-2.5 pb-16.5">
                    {{-- PRINCIPAL --}}
                    <li class="menu-title mt-0!"><span>Principal</span></li>

                    <li class="menu-item">
                        <a
                            class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                            href="{{ route('admin.dashboard') }}"
                        >
                            <span class="menu-icon"><i class="iconify tabler--dashboard"></i></span>
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>

                    {{-- CADASTROS --}}
                    <li class="menu-title"><span>Cadastros</span></li>

                    @can ('instituicoes.listar')
                        <li class="menu-item">
                            <a
                                class="menu-link {{ request()->routeIs('admin.instituicoes.*') ? 'active' : '' }}"
                                href="{{ route('admin.instituicoes.index') }}"
                            >
                                <span class="menu-icon"><i class="iconify tabler--building"></i></span>
                                <span class="menu-text">Instituições</span>
                            </a>
                        </li>
                    @endcan

                    @can ('contratos.listar')
                        <li
                            class="menu-item hs-accordion {{ request()->routeIs('admin.contratos.*') ? 'active' : '' }}"
                        >
                            <a
                                class="hs-accordion-toggle menu-link"
                                href="javascript:void(0)"
                                aria-controls="menu-contratos"
                                aria-expanded="{{ request()->routeIs('admin.contratos.*') ? 'true' : 'false' }}"
                            >
                                <span class="menu-icon"><i class="iconify tabler--file-text"></i></span>
                                <span class="menu-text">Contratos</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul
                                class="sub-menu hs-accordion-content hs-accordion-group {{ request()->routeIs('admin.contratos.*') ? '' : 'hidden' }}"
                                id="menu-contratos"
                            >
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.contratos.index') }}">
                                        <span class="menu-text">Todos os Contratos</span>
                                    </a>
                                </li>
                                @can ('contratos.criar')
                                    <li class="menu-item">
                                        <a class="menu-link" href="{{ route('admin.contratos.create') }}">
                                            <span class="menu-text">Novo Contrato</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can ('produtos.listar')
                        <li
                            class="menu-item hs-accordion {{ request()->routeIs('admin.produtos.*', 'admin.categorias.*', 'admin.termos.*') ? 'active' : '' }}"
                        >
                            <a
                                class="hs-accordion-toggle menu-link"
                                href="javascript:void(0)"
                                aria-controls="menu-produtos"
                                aria-expanded="false"
                            >
                                <span class="menu-icon"><i class="iconify tabler--package"></i></span>
                                <span class="menu-text">Pacotes &amp; Produtos</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="sub-menu hs-accordion-content hs-accordion-group hidden" id="menu-produtos">
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.produtos.index') }}">
                                        <span class="menu-text">Todos os Produtos</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.categorias.index') }}">
                                        <span class="menu-text">Categorias</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.termos.index') }}">
                                        <span class="menu-text">Termos de Adesão</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endcan

                    {{-- GESTÃO --}}
                    <li class="menu-title"><span>Gestão</span></li>

                    @can ('formandos.listar')
                        <li
                            class="menu-item hs-accordion {{ request()->routeIs('admin.formandos.*') ? 'active' : '' }}"
                        >
                            <a
                                class="hs-accordion-toggle menu-link"
                                href="javascript:void(0)"
                                aria-controls="menu-formandos"
                                aria-expanded="false"
                            >
                                <span class="menu-icon"><i class="iconify tabler--users"></i></span>
                                <span class="menu-text">Formandos</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="sub-menu hs-accordion-content hs-accordion-group hidden" id="menu-formandos">
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.formandos.index') }}">
                                        <span class="menu-text">Todos os Formandos</span>
                                    </a>
                                </li>
                                @can ('formandos.criar')
                                    <li class="menu-item">
                                        <a class="menu-link" href="{{ route('admin.formandos.create') }}">
                                            <span class="menu-text">Cadastro Manual</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can ('financeiro.listar')
                        <li
                            class="menu-item hs-accordion {{ request()->routeIs('admin.financeiro.*') ? 'active' : '' }}"
                        >
                            <a
                                class="hs-accordion-toggle menu-link"
                                href="javascript:void(0)"
                                aria-controls="menu-financeiro"
                                aria-expanded="false"
                            >
                                <span class="menu-icon"><i class="iconify tabler--cash"></i></span>
                                <span class="menu-text">Financeiro</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="sub-menu hs-accordion-content hs-accordion-group hidden" id="menu-financeiro">
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.financeiro.parcelas.index') }}">
                                        <span class="menu-text">Parcelas</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.financeiro.simulador') }}">
                                        <span class="menu-text">Simulador</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.relatorios.index') }}">
                                        <span class="menu-text">Relatórios</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endcan

                    {{-- CONFIGURAÇÕES --}}
                    <li class="menu-title"><span>Configurações</span></li>

                    @can ('configuracoes.editar')
                        <li class="menu-item hs-accordion">
                            <a
                                class="hs-accordion-toggle menu-link"
                                href="javascript:void(0)"
                                aria-controls="menu-config"
                                aria-expanded="false"
                            >
                                <span class="menu-icon"><i class="iconify tabler--settings"></i></span>
                                <span class="menu-text">Configurações</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul class="sub-menu hs-accordion-content hs-accordion-group hidden" id="menu-config">
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.configuracoes.index') }}">
                                        <span class="menu-text">Configurações Globais</span>
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route('admin.indices-reajuste.index') }}">
                                        <span class="menu-text">Índices de Reajuste</span>
                                    </a>
                                </li>
                                @can ('usuarios.listar')
                                    <li class="menu-item">
                                        <a class="menu-link" href="{{ route('admin.usuarios.index') }}">
                                            <span class="menu-text">Usuários Admin</span>
                                        </a>
                                    </li>
                                @endcan
                                @can ('perfis.listar')
                                    <li class="menu-item">
                                        <a class="menu-link" href="{{ route('admin.perfis.index') }}">
                                            <span class="menu-text">Perfis &amp; Permissões</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcan

                    @can ('auditoria.visualizar')
                        <li class="menu-item">
                            <a
                                class="menu-link {{ request()->routeIs('admin.auditoria.*') ? 'active' : '' }}"
                                href="{{ route('admin.auditoria.index') }}"
                            >
                                <span class="menu-icon"><i class="iconify tabler--history"></i></span>
                                <span class="menu-text">Logs de Auditoria</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
        </div>
    </div>
</aside>
```

---

## Exemplos de Uso

### Exemplo Básico (via layout master)

O sidebar é incluído automaticamente pelo `<x-admin.layout>`:

```blade
<x-admin.layout title="Dashboard">
    {{-- conteúdo --}}
</x-admin.layout>
```

Não há uso direto — é sempre via layout.

---

## Quando Usar ✅

- Em TODAS as views do admin autenticado — incluído automaticamente pelo `<x-admin.layout>`
- Em viewports mobile (< 1140px) — o script de head-css força `offcanvas`, o próprio sidebar se adapta

## Quando NÃO Usar ❌

- Telas de auth (login, 404, 500) — usar layout mínimo sem sidebar
- Portal do formando — tem navegação própria (sem sidebar, mobile-first)
- Modal de preview (termos) — contexto sem navegação

## Boas Práticas 💡

- **Estado ativo:** usar `request()->routeIs('admin.contratos.*')` para aplicar `.active` em qualquer rota dentro do namespace
- **Submenus iniciam fechados** EXCETO se a rota atual estiver dentro deles — remover `hidden` condicionalmente
- **Spatie `@can`:** cada item raiz deve ser gated por permissão. Usuários sem `contratos.listar` nem veem o menu
- **Não passar ícones via prop** — defini-los no componente mantém consistência. Se precisar mudar, editar aqui
- **Não criar sub-components** (`<x-admin.sidebar.item>`, etc.) por enquanto — o ganho de abstração não compensa a complexidade para um menu fixo. Promover apenas se virar 30+ itens com regras complexas

---

## Mapeamento no PRD (Portal ArtFinal)

| Tela                             | Seção PRD    | Como É Usado                           | Sprint |
| -------------------------------- | ------------ | -------------------------------------- | :----: |
| Navegação Admin completa         | 14.21        | Sidebar com menu fixo hardcoded        |   16   |
| Dashboard                        | 14.2         | Item "Dashboard" ativo                 |   16   |
| Instituições                     | 14.3         | Item "Instituições" ativo              |   17   |
| Contratos (list + create)        | 14.4         | Submenu "Contratos" ativo              |   17   |
| Produtos/Categorias/Termos       | 14.5–14.11   | Submenu "Pacotes & Produtos" ativo     |   18   |
| Formandos                        | 14.12, 14.20 | Submenu "Formandos" ativo              |   20   |
| Financeiro (parcelas, simulador) | 14.13, 14.14 | Submenu "Financeiro" ativo             | 21–22  |
| Relatórios                       | 14.17        | Item "Relatórios" dentro de Financeiro |   22   |
| Configurações Globais            | 14.15        | Submenu "Configurações" ativo          |   23   |
| Índices de Reajuste              | 14.16        | Item dentro de Configurações           |   23   |
| Usuários Admin                   | 14.18        | Item dentro de Configurações           |   24   |
| Perfis/Permissões                | 14.19        | Item dentro de Configurações           |   24   |
| Logs de Auditoria                | —            | Item raiz (fora de Configurações)      |   24   |

---

## Classificação

| Critério                   | Valor                                                         |
| -------------------------- | ------------------------------------------------------------- |
| **Vai usar no projeto**    | 🟢 Sim                                                        |
| **Prioridade**             | P0                                                            |
| **Sprint planejada**       | 16                                                            |
| **Complexidade**           | Média-alta (menu com 20+ itens + gates Spatie + estado ativo) |
| **Status componentização** | 🟢 Concluído                                                  |

---

## Dependências

| Tipo                        | Item                                                                      |
| --------------------------- | ------------------------------------------------------------------------- |
| **Depende de (JS)**         | Preline (accordion), Simplebar (scroll), Iconify tabler                   |
| **Depende de (CSS)**        | Classes do Inspinia: `.app-menu`, `.side-nav`, `.menu-*`, `.sidenav-user` |
| **Depende de (Laravel)**    | Spatie Permissions (`@can`), rotas nomeadas `admin.*`, guard `admin`      |
| **Usado por (telas)**       | Todas as views do admin autenticado (20 telas)                            |
| **Usado por (componentes)** | `<x-admin.layout>`                                                        |

---

## Notas de Adaptação

1. **Menu hardcoded em PT-BR:** os menus originais do Inspinia (Dashboards, Apps, Custom Pages, Layouts, Components, Menu Items) são totalmente descartados — substituídos pelo menu do PRD §14.21
2. **Remover atributos `data-lang`:** o Inspinia usa `data-lang="main"` para i18n via JS. Como o admin é PT-BR fixo, remover
3. **Logos:** substituir `/images/logo.png` → `asset('images/admin/logo.png')`. Providenciar 4 variants (light/dark × lg/sm)
4. **User card:** remover `bg-[url(/images/user-bg-pattern.svg)]` ou providenciar pattern próprio. Substituir `Damian D.` → `{{ $user->nome }}`. Remover link para "Lock Screen" (não temos).
5. **Logout por POST:** o Inspinia usa link direto para logout — inseguro. Trocar por `<form method="POST" action="{{ route('admin.logout') }}">`
6. **Estado ativo:** Inspinia usa JS para marcar o item ativo (loop pelo `pathname`). **Trocar por Blade server-side** com `request()->routeIs('...')` — mais confiável, funciona antes do JS carregar
7. **Spatie `@can`:** cada item do menu precisa de gate por permissão. Os nomes das permissões virão da Sprint 19 (Admin Auth + ACL)
8. **Submenu auto-open na rota ativa:** ao entrar em `/admin/contratos/123`, o accordion "Contratos" deve abrir automaticamente. Conseguido via `aria-expanded="true"` + remover `hidden` condicionalmente
9. **Ícones Tabler:** os nomes `tabler--dashboard`, `tabler--building`, `tabler--file-text`, etc. vêm do Iconify. Confirmar que todos existem na versão instalada
10. **Sem showcase de submenus 3+ níveis:** o Inspinia mostra submenus aninhados em 3 níveis. ArtFinal só precisa de 2 níveis (raiz + filhos)
11. **Mega menu ignorado:** o topbar do Inspinia tem mega menu (ver `topbar.md`). No ArtFinal não usamos — a navegação é toda via sidebar

## Código Final Blade

Implementação consolidada em `resources/views/components/admin/sidebar.blade.php`.

Principais ajustes aplicados no código final:

- o menu permanece hardcoded, mas agora é montado por arrays locais para reduzir repetição e centralizar estados ativos
- em ambiente de preview/local sem admin autenticado, a árvore completa continua visível para inspeção visual
- o user card e o menu rápido reutilizam `x-shared.dropdown`
- logos foram alinhados aos assets reais disponíveis em `public/images/`

---

## Changelog do Componente

| Data       | Descrição                  |
| ---------- | -------------------------- |
| 2026-04-11 | Doc criada — Fase 2 Onda 1 |
