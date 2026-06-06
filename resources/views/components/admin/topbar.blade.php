@php
    $user = auth('admin')->user();

    $displayUser = [
        'nome' => $user?->nome ?? config('branding.user_default_name'),
        'perfil' => $user?->getRoleNames()->first() ?? config('branding.user_default_role'),
    ];

    $notifications = [];

    $notificationCount = count($notifications);

    $notificationToneClasses = [
        'primary' => 'bg-primary/15 text-primary',
        'success' => 'bg-success/15 text-success',
        'warning' => 'bg-warning/15 text-warning',
        'danger' => 'bg-danger/15 text-danger',
        'info' => 'bg-info/15 text-info',
    ];
@endphp

<header class="app-header">
    <div class="container-fluid flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            @php
                $brandingTopbar = app(\App\Services\Admin\Settings\BrandingService::class);
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
                <i class="iconify tabler--menu-4 text-xl"></i>
            </button>

            <form action="{{ route('admin.dashboard') }}" class="hidden xl:flex" id="search-box" role="search">
                <div class="input-icon-group">
                    <i class="iconify tabler--search input-icon text-lg text-(--topbar-item-color)/50!"></i>
                    <input
                        id="topbar-search"
                        type="search"
                        name="q"
                        class="form-input w-57.5 border-(--topbar-search-border)! bg-(--topbar-search-bg)! text-(--topbar-item-color)! placeholder:opacity-50"
                        placeholder="Buscar..."
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
                        ></i>
                        <i
                            class="iconify tabler--sun topbar-link-icon absolute scale-0 rotate-90 transition-all duration-200 dark:scale-100 dark:rotate-0"
                        ></i>
                    </button>
                </div>
            </div>

            <x-shared.dropdown class="topbar-item" placement="bottom-end" auto-close="inside">
                <x-slot:button>
                    <button
                        type="button"
                        class="topbar-link hs-dropdown-toggle relative flex items-center"
                        aria-label="Abrir notificações"
                    >
                        <i class="iconify tabler--bell topbar-link-icon"></i>

                        @if ($notificationCount > 0)
                            <x-shared.badge
                                variant="warning"
                                solid
                                class="absolute -end-px -top-[13px] min-w-4 px-1 py-0 text-[10px] leading-none text-white"
                            >
                                {{ $notificationCount }}
                            </x-shared.badge>
                        @endif
                    </button>
                </x-slot:button>

                <div class="border-default-300 border-b px-3 py-2">
                    <div class="flex items-center justify-between gap-3">
                        <h6 class="text-body-color text-base font-semibold">Notificações</h6>
                        <x-shared.badge variant="warning" solid>{{ $notificationCount }} alertas</x-shared.badge>
                    </div>
                </div>

                <div class="max-h-80 overflow-y-auto" data-simplebar>
                    @foreach ($notifications as $notification)
                        <a
                            href="{{ $notification['href'] }}"
                            class="dropdown-item items-start gap-3 px-4.5 py-3 text-wrap"
                        >
                            <span class="shrink-0">
                                <span
                                    class="flex size-9 items-center justify-center rounded {{ $notificationToneClasses[$notification['variant']] ?? $notificationToneClasses['info'] }}"
                                >
                                    <i class="iconify {{ $notification['icon'] }} text-lg"></i>
                                </span>
                            </span>

                            <span class="text-default-400 grow">
                                <span class="text-body-color font-medium">{{ $notification['title'] }}</span>
                                <br />
                                <span class="text-xs">{{ $notification['time'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>

                <a
                    href="{{ route('admin.conta.notificacoes') }}"
                    class="dropdown-item text-reset border-light justify-center border-t py-3 font-bold underline underline-offset-2"
                >
                    Ver central de notificações
                </a>
            </x-shared.dropdown>

            <div class="hidden md:inline-flex">
                <div class="topbar-item">
                    <button
                        type="button"
                        class="topbar-link btn group size-8 rounded-full"
                        data-toggle="fullscreen"
                        aria-label="Alternar tela cheia"
                    >
                        <i class="iconify tabler--maximize topbar-link-icon group-[.fullscreen-active]:hidden"></i>
                        <i
                            class="iconify tabler--minimize topbar-link-icon hidden group-[.fullscreen-active]:inline-block"
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
                        class="hs-dropdown-toggle topbar-link ms-2.5 flex cursor-pointer items-center px-3!"
                        aria-label="Abrir menu do usuário"
                    >
                        <x-shared.avatar
                            :name="$displayUser['nome']"
                            :src="$user?->urlAvatar()"
                            size="size-8"
                            class="lg:me-3"
                        />

                        <div class="hidden items-center gap-1.5 lg:flex">
                            <div class="text-start">
                                <p class="text-body-color font-semibold">{{ $displayUser['nome'] }}</p>
                                <p class="text-default-400 text-xs">{{ $displayUser['perfil'] }}</p>
                            </div>

                            <i class="iconify tabler--chevron-down align-middle text-sm"></i>
                        </div>
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
                        <i class="iconify tabler--logout align-middle text-base"></i>
                        <span class="align-middle">Sair</span>
                    </button>
                </form>
            </x-shared.dropdown>
        </div>
    </div>
</header>
