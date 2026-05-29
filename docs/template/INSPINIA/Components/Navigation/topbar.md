# Topbar (App Header)

**Categoria:** Navigation
**Origem Inspinia:** `resources/views/shared/partials/topbar.blade.php`
**Plugins JS:** Preline 4.0.1 (dropdowns, collapse), Simplebar 6.3.3 (scroll em mega menus)
**Plugins CSS:** Classes `.app-header`, `.topbar-item`, `.topbar-link`, CSS próprio do Inspinia
**Documentação Inspinia:** `Docs/index.html` § "Layout" (config de `topbar-color`)

---

## Descrição

Barra superior fixa do admin. Contém: **botão toggle do sidenav** (hamburger), **logo** (visível só quando sidenav em offcanvas), **search global**, **botão dark/light**, **dropdown de notificações**, **dropdown de mensagens** (removido neste projeto), **botão fullscreen**, **dropdown de usuário**, **botão de customizer** (removido). Altura fixa definida pela CSS custom property `h-topbar`, fundo cor configurável via `data-topbar-color`.

---

## Preview Visual

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [☰]  [Logo]     🔍 Buscar...              🌙  🔔  ⛶  👤 Admin ▾          │
└──────────────────────────────────────────────────────────────────────────┘
  ↑     ↑           ↑                         ↑   ↑   ↑        ↑
 menu  logo(hidden search (xl:only)          theme bell full   user
 toggle quando      toggle                   toggle    screen  dropdown
       sidenav
       visível)
```

### Seções (da esquerda para direita)

1. **Hamburger toggle** (`#button-toggle-menu`) — colapsa/expande o sidenav
2. **Logo-topbar** — só aparece quando sidenav está em offcanvas (mobile). Light/dark variants.
3. **Topnav toggle** (modo horizontal) — não usado neste projeto
4. **Search global** (`#search-box`, `#topbar-search`) — visível apenas em `xl:` (1280px+)
5. **Mega menu (Header/Apps)** — showcase do Inspinia, **removido neste projeto**
6. **Theme toggle** (`#light-dark-mode`) — alterna `data-theme` light/dark
7. **Apps dropdown** (`#apps-dropdown-rounded`) — grid com atalhos para apps, **reservado**
8. **Messages dropdown** (`#simple-messages-dropdown`) — inbox preview, **reservado**
9. **Notifications** (`#notification-dropdown-alert`) — dropdown com ícone `bell`
10. **Fullscreen button** (`data-toggle="fullscreen"`) — toggle `.fullscreen-active`
11. **Monochrome toggle** (`#monochrome-mode`) — tema monocromático, **removido**
12. **Theme customizer button** — abre offcanvas de customização visual, **removido**
13. **Language selector** (`#language-selector`) — PT-BR fixo, **removido**
14. **User dropdown** (`#simple-user-dropdown`) — perfil, configurações, logout

---

## Código Original (Inspinia)

O arquivo original tem **768 linhas**. Mostro apenas os blocos estruturais relevantes:

```html
<header class="app-header">
    <div class="container-fluid flex items-center justify-between">
        <!-- LEFT SIDE -->
        <div class="flex items-center gap-2.5">
            <!-- Logo (mostrado apenas quando sidenav offcanvas) -->
            <div class="logo-topbar">
                <a class="logo-box" href="/">
                    <div class="logo-light">
                        <img class="logo-lg h-6" src="/images/logo.png" />
                        <img class="logo-sm h-6" src="/images/logo-sm.png" />
                    </div>
                    <div class="logo-dark">
                        <img class="logo-lg h-6" src="/images/logo-black.png" />
                        <img class="logo-sm h-6" src="/images/logo-sm.png" />
                    </div>
                </a>
            </div>

            <!-- Hamburger toggle -->
            <button
                id="button-toggle-menu"
                class="sidenav-toggle-button btn bg-primary btn-icon rounded-full text-white"
            >
                <i class="iconify tabler--menu-4 text-xl"></i>
            </button>

            <!-- Search (xl+) -->
            <div class="hidden xl:flex" id="search-box">
                <div class="input-icon-group">
                    <i class="iconify tabler--search input-icon text-lg"></i>
                    <input
                        class="form-input w-57.5"
                        id="topbar-search"
                        placeholder="Search for something..."
                        type="search"
                    />
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="flex items-center gap-2">
            <!-- Theme toggle -->
            <div class="topbar-item">
                <button id="light-dark-mode" type="button" class="topbar-link btn btn-icon size-8 rounded-full">
                    <i class="iconify tabler--moon absolute scale-100 rotate-0 dark:scale-0 dark:-rotate-90"></i>
                    <i class="iconify tabler--sun absolute scale-0 rotate-90 dark:scale-100 dark:rotate-0"></i>
                </button>
            </div>

            <!-- Notifications -->
            <div
                class="topbar-item hs-dropdown relative inline-flex [--placement:bottom-right]"
                id="notification-dropdown-alert"
            >
                <button class="topbar-link hs-dropdown-toggle relative flex items-center" type="button">
                    <i class="iconify tabler--bell topbar-link-icon"></i>
                    <span class="bg-danger absolute end-1 top-1 size-2 rounded-full"></span>
                </button>
                <div class="hs-dropdown-menu" role="menu">
                    <!-- Lista de notificações -->
                </div>
            </div>

            <!-- Fullscreen -->
            <div class="topbar-item">
                <button class="topbar-link btn group size-8 rounded-full" data-toggle="fullscreen">
                    <i class="iconify tabler--maximize group-[.fullscreen-active]:hidden"></i>
                    <i class="iconify tabler--minimize hidden group-[.fullscreen-active]:inline-block"></i>
                </button>
            </div>

            <!-- User dropdown -->
            <div class="topbar-item hs-dropdown relative inline-flex" id="simple-user-dropdown">
                <button class="hs-dropdown-toggle topbar-link ms-2.5 flex cursor-pointer items-center px-3!">
                    <img src="/images/users/user-1.jpg" alt="user" class="me-2 size-8 rounded-full" />
                    <span class="hidden flex-col text-start md:flex">
                        <span class="text-sm font-bold">Damian D.</span>
                        <span class="text-xs opacity-80">Art Director</span>
                    </span>
                </button>
                <div class="hs-dropdown-menu" role="menu">
                    <a class="dropdown-item" href="#!">Profile</a>
                    <a class="dropdown-item" href="#!">Account Settings</a>
                    <a class="dropdown-item text-danger" href="#!">Log Out</a>
                </div>
            </div>
        </div>
    </div>
</header>
```

### Recursos removidos neste projeto

- `#topnav-menu-collapse` (modo horizontal)
- `#megamenu-header`, `#megamenu-apps` (mega menus)
- `#apps-dropdown-rounded` (grid de apps)
- `#simple-messages-dropdown` (preview de mensagens)
- `#monochrome-toggler` (modo monocromático)
- `.btn-theme-setting` (customizer)
- `#language-selector` (seleção de idioma)

---

## Componente Blade Proposto

**Nome:** `<x-admin.topbar>`
**Arquivo view:** `resources/views/components/admin/topbar.blade.php`
**Classe PHP:** Blade anônimo — sem classe
**Tipo:** Blade anônimo

### Props

| Prop | Tipo | Obrigatório | Default | Descrição                                       |
| ---- | ---- | :---------: | ------- | ----------------------------------------------- |
| —    | —    |      —      | —       | Sem props (user vem de `auth('admin')->user()`) |

### Slots

Nenhum. Estrutura fixa.

### Código do Componente Blade

```blade
{{-- resources/views/components/admin/topbar.blade.php --}}
@php
    $user = auth('admin')->user();
    $notificacoesCount = $user?->unreadNotifications()->count() ?? 0;
@endphp

<header class="app-header">
    <div class="container-fluid flex items-center justify-between">
        {{-- LEFT SIDE --}}
        <div class="flex items-center gap-2.5">
            {{-- Logo visível quando sidenav offcanvas (mobile) --}}
            <div class="logo-topbar">
                <a class="logo-box" href="{{ route('admin.dashboard') }}">
                    <div class="logo-light">
                        <img class="logo-lg h-6" alt="{{ config('app.name') }}" src="{{ asset('images/admin/logo.png') }}" />
                        <img class="logo-sm h-6" alt="{{ config('app.name') }}" src="{{ asset('images/admin/logo-sm.png') }}" />
                    </div>
                    <div class="logo-dark">
                        <img class="logo-lg h-6" alt="{{ config('app.name') }}" src="{{ asset('images/admin/logo-dark.png') }}" />
                        <img class="logo-sm h-6" alt="{{ config('app.name') }}" src="{{ asset('images/admin/logo-sm-dark.png') }}" />
                    </div>
                </a>
            </div>

            {{-- Toggle sidenav --}}
            <button
                id="button-toggle-menu"
                class="sidenav-toggle-button btn rounded-full bg-primary btn-icon text-white"
                aria-label="Alternar menu lateral"
            >
                <i class="iconify tabler--menu-4 text-xl"></i>
            </button>

            {{-- Busca global (xl+) --}}
            <div class="hidden xl:flex" id="search-box">
                <livewire:admin.topbar.busca-global />
            </div>
        </div>

        {{-- RIGHT SIDE --}}
        <div class="flex items-center gap-2">
            {{-- Dark/Light toggle --}}
            <div class="topbar-item">
                <button
                    id="light-dark-mode"
                    type="button"
                    class="topbar-link btn btn-icon size-8 rounded-full transition-[scale,background]"
                    aria-label="Alternar tema claro/escuro"
                >
                    <i
                        class="iconify tabler--moon absolute scale-100 rotate-0 topbar-link-icon transition-all duration-200 dark:scale-0 dark:-rotate-90"
                    ></i>
                    <i
                        class="iconify tabler--sun absolute scale-0 rotate-90 topbar-link-icon transition-all duration-200 dark:scale-100 dark:rotate-0"
                    ></i>
                </button>
            </div>

            {{-- Notificações --}}
            <div
                class="topbar-item hs-dropdown relative inline-flex [--auto-close:inside] [--placement:bottom-right]"
                id="notification-dropdown-alert"
            >
                <button
                    type="button"
                    class="topbar-link hs-dropdown-toggle relative flex items-center"
                    aria-expanded="false"
                    aria-label="Notificações"
                >
                    <i class="iconify tabler--bell topbar-link-icon"></i>
                    @if ($notificacoesCount > 0)
                        <span
                            class="absolute top-1 end-1 size-2 rounded-full bg-danger"
                            aria-label="{{ $notificacoesCount }} notificações não lidas"
                        ></span>
                    @endif
                </button>
                <div class="hs-dropdown-menu" role="menu">
                    <livewire:admin.topbar.notificacoes />
                </div>
            </div>

            {{-- Fullscreen --}}
            <div class="topbar-item">
                <button
                    type="button"
                    class="topbar-link btn group size-8 rounded-full"
                    data-toggle="fullscreen"
                    aria-label="Tela cheia"
                >
                    <i class="iconify tabler--maximize topbar-link-icon group-[.fullscreen-active]:hidden"></i>
                    <i
                        class="iconify tabler--minimize hidden topbar-link-icon group-[.fullscreen-active]:inline-block"
                    ></i>
                </button>
            </div>

            {{-- User dropdown --}}
            <div
                class="topbar-item hs-dropdown before:bg-default-700/35 relative inline-flex before:h-4.5 before:w-px before:content-['']"
                id="simple-user-dropdown"
            >
                <button
                    type="button"
                    class="hs-dropdown-toggle topbar-link ms-2.5 cursor-pointer items-center px-3! flex"
                    aria-expanded="false"
                    aria-label="Menu do usuário"
                >
                    <img
                        src="{{ $user?->avatar_url ?? asset('images/admin/avatar-default.png') }}"
                        alt="{{ $user?->nome ?? 'Usuário' }}"
                        class="size-8 rounded-full me-2"
                    />
                    <span class="hidden md:flex flex-col text-start">
                        <span class="font-bold text-sm">{{ $user?->nome ?? 'Usuário' }}</span>
                        <span class="text-xs opacity-80">{{ $user?->perfil?->nome ?? '' }}</span>
                    </span>
                    <i class="iconify tabler--chevron-down ms-1 text-sm"></i>
                </button>
                <div class="hs-dropdown-menu" role="menu">
                    <div class="py-2 px-3.5">
                        <h6 class="text-xs text-default-500">Olá, {{ $user?->nome ?? 'Usuário' }} 👋</h6>
                    </div>
                    <a class="dropdown-item" href="{{ route('admin.perfil.show') }}">
                        <i class="iconify tabler--user-circle me-1 align-middle text-lg"></i>
                        Meu Perfil
                    </a>
                    <a class="dropdown-item" href="{{ route('admin.conta.edit') }}">
                        <i class="iconify tabler--settings-2 me-1 align-middle text-lg"></i>
                        Configurações da Conta
                    </a>
                    <div class="border-t my-2"></div>
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
</header>
```

---

## Exemplos de Uso

### Exemplo Básico (via layout master)

```blade
<x-admin.layout title="Dashboard">
    {{-- topbar é incluída automaticamente --}}
</x-admin.layout>
```

### Uso direto (para storybook/preview)

```blade
<x-admin.partials.theme-bootstrap /> <x-admin.topbar />
```

---

## Quando Usar ✅

- Em TODAS as views do admin autenticado — via `<x-admin.layout>`
- Como referência de cabeçalho fixo em modais full-screen (raríssimo)

## Quando NÃO Usar ❌

- Telas de auth — login não tem topbar (tela limpa)
- Visualização de PDFs/impressão — não faz sentido

## Boas Práticas 💡

- **Badge de notificações:** apenas renderizar quando `$notificacoesCount > 0` — evita a bolinha vermelha vazia
- **Busca global via Livewire:** extrair como `<livewire:admin.topbar.busca-global>` isolado. Se deixar inline, um typing-debounce acidental afeta todo o layout
- **Logout via POST:** segurança básica. NUNCA usar `<a href=".../logout">`
- **Avatar fallback:** sempre providenciar `avatar-default.png` — evita broken image
- **`aria-label` em todos os botões icon-only** — acessibilidade mínima

---

## Classificação

| Critério                   | Valor                                         |
| -------------------------- | --------------------------------------------- |
| **Vai usar no projeto**    | 🟢 Sim                                        |
| **Complexidade**           | Média (vários elementos, cada um com Preline) |
| **Status componentização** | 🟢 Concluído                                  |

---

## Dependências

| Tipo                        | Item                                                                                                                                                   |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Depende de (JS)**         | Preline (hs-dropdown), Iconify tabler, fullscreen API nativa                                                                                           |
| **Depende de (CSS)**        | Classes `.app-header`, `.topbar-*`, `.dropdown-*` do Inspinia                                                                                          |
| **Depende de (Livewire)**   | Nenhum no código atual — a busca global e a central de notificações ficaram preparadas para futura promoção, mas o batch fecha com fallback Blade puro |
| **Usado por (views)**       | Todas as views admin autenticado                                                                                                                       |
| **Usado por (componentes)** | `<x-admin.layout>`                                                                                                                                     |

---

## Notas de Adaptação

1. **Remover `#megamenu-header` e `#megamenu-apps`** — showcase do Inspinia, irrelevante
2. **Remover `#apps-dropdown-rounded` e `#simple-messages-dropdown`** — reservados (podem voltar se/quando File Manager e Email app entrarem)
3. **Remover `#monochrome-toggler` e `.btn-theme-setting`** — customização visual pelo usuário não faz parte do escopo
4. **Remover `#language-selector`** — sistema PT-BR fixo
5. **Remover toggle `#topnav-menu-collapse`** — não usamos modo horizontal
6. **Substituir `Damian D.` e `Art Director`** por `{{ $user->nome }}` e `{{ $user->perfil?->nome }}`
7. **Avatar padrão:** criar `public/images/admin/avatar-default.png` (64x64, círculo com iniciais)
8. **Logout por POST:** obrigatório (o original usa link direto — inseguro)
9. **Busca global:** nesta fase, o topbar fecha com campo de busca Blade puro apontando para rotas nomeadas do admin. A promoção para Livewire fica reservada para quando a pesquisa transversal existir de fato.
10. **Notificações:** o sino permanece composição interna do topbar com dados de preview/placeholder consistentes. A promoção para fonte real (`notifications` do Laravel) fica reservada para quando o módulo de notificações entrar.
11. **`#button-toggle-menu` JS:** o Inspinia tem handler próprio para alternar `data-sidenav-size`. Manter referência ao JS original, copiar para `resources/js/admin.js`
12. **Fullscreen API:** o `data-toggle="fullscreen"` requer handler em JS (do Inspinia) — copiar para `resources/js/admin.js`

## Código Final Blade

Implementação consolidada em `resources/views/components/admin/topbar.blade.php`.

Principais ajustes aplicados no código final:

- `mega-menu`, apps grid, messages, language selector, monochrome mode e customizer saíram do escopo final
- o sino de notificações permanece composição interna do topbar e reutiliza `x-shared.dropdown` + `x-shared.badge`
- o menu do usuário também reutiliza `x-shared.dropdown`
- a busca global ficou em Blade puro nesta fase para evitar dependência forçada de filhos Livewire inexistentes

---

## Changelog do Componente

| Data       | Descrição   |
| ---------- | ----------- |
| 2026-04-11 | Doc criada  |
