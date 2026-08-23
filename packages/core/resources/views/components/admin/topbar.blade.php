@php
    $user = auth('admin')->user();

    $displayUser = [
        'nome' => $user?->nomeExibicao() ?? config('branding.user_default_name'),
        'perfil' => $user?->perfilExibicao() ?? config('branding.user_default_role'),
    ];
@endphp

<header class="app-header">
    <div class="container-fluid flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            @php
                $brandingTopbar = app(\HT2ML\Core\Services\Admin\Settings\BrandingService::class);
            @endphp

            <div class="logo-topbar">
                <a class="logo-box" href="{{ route('admin.dashboard') }}">
                    <div class="logo-light">
                        <img
                            class="logo-lg h-6"
                            alt="{{ $brandingTopbar->nomeSistema() }}"
                            src="{{ $brandingTopbar->logoUrl('light') }}"
                        />
                        <img
                            class="logo-sm h-6"
                            alt="{{ $brandingTopbar->nomeSistema() }}"
                            src="{{ $brandingTopbar->logoUrl('sm') }}"
                        />
                    </div>

                    <div class="logo-dark">
                        <img
                            class="logo-lg h-6"
                            alt="{{ $brandingTopbar->nomeSistema() }}"
                            src="{{ $brandingTopbar->logoUrl('dark') }}"
                        />
                        <img
                            class="logo-sm h-6"
                            alt="{{ $brandingTopbar->nomeSistema() }}"
                            src="{{ $brandingTopbar->logoUrl('sm') }}"
                        />
                    </div>
                </a>
            </div>

            <button
                id="button-toggle-menu"
                type="button"
                class="sidenav-toggle-button btn bg-primary btn-icon rounded-full text-white"
                aria-label="Alternar menu lateral"
            >
                <i class="iconify tabler--menu-4 text-xl" aria-hidden="true"></i>
            </button>

            <form action="{{ route('admin.dashboard') }}" class="hidden xl:flex" id="search-box" role="search">
                <div class="input-icon-group">
                    <i
                        class="iconify tabler--search input-icon text-lg text-(--topbar-item-color)/50!"
                        aria-hidden="true"
                    ></i>
                    {{-- aria-label: o placeholder some ao digitar e não é nome acessível (WCAG 3.3.2/4.1.2); --}}
                    {{-- espelha o padrão da x-admin.table.toolbar (aria-label = placeholder da busca). --}}
                    <input
                        id="topbar-search"
                        type="search"
                        name="q"
                        class="form-input w-57.5 border-(--topbar-search-border)! bg-(--topbar-search-bg)! text-(--topbar-item-color)! placeholder:opacity-50"
                        placeholder="Buscar..."
                        aria-label="Buscar..."
                    />
                </div>
            </form>
        </div>

        <div class="flex items-center gap-2.5">
            <livewire:admin.tenancy.seletor-empresa-filial />

            <div class="hidden sm:inline-flex">
                <div class="topbar-item">
                    <button
                        id="light-dark-mode"
                        type="button"
                        class="topbar-link btn btn-icon size-8 rounded-full transition-[scale,background]"
                        aria-label="Alternar tema claro e escuro"
                    >
                        <i
                            class="iconify tabler--moon topbar-link-icon absolute scale-100 rotate-0 transition-all duration-200 dark:scale-0 dark:-rotate-90"
                            aria-hidden="true"
                        ></i>
                        <i
                            class="iconify tabler--sun topbar-link-icon absolute scale-0 rotate-90 transition-all duration-200 dark:scale-100 dark:rotate-0"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>
            </div>

            <livewire:admin.notificacoes.sino-notificacoes />

            <div class="hidden md:inline-flex">
                <div class="topbar-item">
                    <button
                        type="button"
                        class="topbar-link btn group size-8 rounded-full"
                        data-toggle="fullscreen"
                        aria-label="Alternar tela cheia"
                        aria-pressed="false"
                    >
                        <i
                            class="iconify tabler--maximize topbar-link-icon group-[.fullscreen-active]:hidden"
                            aria-hidden="true"
                        ></i>
                        <i
                            class="iconify tabler--minimize topbar-link-icon hidden group-[.fullscreen-active]:inline-block"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>
            </div>

            <x-shared.dropdown
                class="topbar-item before:bg-default-700/35 relative inline-flex before:h-4.5 before:w-px before:content-['']"
                placement="bottom-end"
            >
                <x-slot:button>
                    <button
                        type="button"
                        class="hs-dropdown-toggle topbar-link ms-2.5 flex cursor-pointer items-center gap-1.5 px-3!"
                        aria-label="Abrir menu do usuário"
                    >
                        <x-shared.avatar :name="$displayUser['nome']" :src="$user?->urlAvatar()" size="size-8" />

                        <i
                            class="iconify tabler--chevron-down hidden align-middle text-sm lg:inline-block"
                            aria-hidden="true"
                        ></i>
                    </button>
                </x-slot:button>

                <div class="px-3.5 py-2">
                    <h6 class="text-default-500 text-xs">Bem-vindo de volta</h6>
                    <p class="text-body-color mt-1 font-semibold">{{ $displayUser['nome'] }}</p>
                </div>

                <x-shared.dropdown-item icon="tabler--user-circle" :href="route('admin.conta')">
                    Meu perfil
                </x-shared.dropdown-item>
                <x-shared.dropdown-item
                    icon="tabler--settings-2"
                    :href="route('admin.conta', ['aba' => 'preferencias'])"
                >
                    Configurações da conta
                </x-shared.dropdown-item>
                <x-shared.dropdown-item icon="tabler--bell-ringing" :href="route('admin.conta.notificacoes')">
                    Notificações
                </x-shared.dropdown-item>
                <x-shared.dropdown-divider />

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf

                    <button type="submit" class="dropdown-item text-danger w-full text-start">
                        <i class="iconify tabler--logout align-middle text-base" aria-hidden="true"></i>
                        <span class="align-middle">Sair</span>
                    </button>
                </form>
            </x-shared.dropdown>
        </div>
    </div>
</header>
