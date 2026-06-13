@php
    $user = auth('admin')->user();
    $showAllItems = $user === null || request()->routeIs('admin.dev.components.*');

    $displayUser = [
        'nome' => $user?->nomeExibicao() ?? config('branding.user_default_name'),
        'perfil' => $user?->perfilExibicao() ?? config('branding.user_default_role'),
    ];

    // Registro (config/admin-menu.php) + personalizações da tela de Gestão de
    // Menus, com o filtro de permissão por usuário aplicado no serviço.
    $sections = app(\App\Services\Admin\Menu\MenuService::class)
        ->estruturaParaSidebar($user, $showAllItems);
@endphp

<aside class="app-menu" id="app-menu">
    @php
        $brandingSidebar = app(\App\Services\Admin\Settings\BrandingService::class);
    @endphp

    <a class="logo-box" href="{{ route('admin.dashboard') }}">
        <span class="logo logo-light">
            <span class="logo-lg">
                <img alt="{{ $brandingSidebar->nomeSistema() }}" src="{{ $brandingSidebar->logoUrl('light') }}" />
            </span>
            <span class="logo-sm">
                <img alt="{{ $brandingSidebar->nomeSistema() }}" src="{{ $brandingSidebar->logoUrl('sm') }}" />
            </span>
        </span>

        <span class="logo logo-dark">
            <span class="logo-lg">
                <img alt="{{ $brandingSidebar->nomeSistema() }}" src="{{ $brandingSidebar->logoUrl('dark') }}" />
            </span>
            <span class="logo-sm">
                <img alt="{{ $brandingSidebar->nomeSistema() }}" src="{{ $brandingSidebar->logoUrl('sm') }}" />
            </span>
        </span>
    </a>

    <div class="h-topbar justify absolute end-5 top-0 flex items-center">
        <button id="button-hover-toggle" type="button" aria-label="Alternar modo hover do menu">
            <span class="btn-on-hover-icon"></span>
        </button>
    </div>

    <div class="relative min-h-0 grow">
        <div class="size-full" data-simplebar>
            <div class="sidenav-user p-5" id="user-profile-settings">
                <a class="link-reset block min-w-0" href="{{ route('admin.conta') }}">
                    <x-shared.avatar
                        :name="$displayUser['nome']"
                        :src="$user?->urlAvatar()"
                        size="size-9"
                        class="mb-3"
                    />
                    <span class="sidenav-user-name block truncate font-bold">{{ $displayUser['nome'] }}</span>
                    <span class="text-xs font-semibold">{{ $displayUser['perfil'] }}</span>
                </a>
            </div>

            <div id="sidenav-menu">
                <ul class="side-nav hs-accordion-group px-2.5 pb-16.5">
                    @foreach ($sections as $section)
                        <li @class (['menu-title', 'mt-0!' => $loop->first])>
                            <span>{{ $section['title'] }}</span>
                        </li>
                        @foreach ($section['items'] as $item)
                            @php
                                $activePatterns = $item['active'] ?? [];
                                $isGroup = $item['children'] !== [];
                                // Grupo não tem rota própria: fica ativo quando algum filho casa a rota atual.
                                $isActive = $isGroup
                                    ? collect($item['children'])->contains(
                                        fn (array $child) => request()->routeIs(...($child['active'] ?? [$child['route']])),
                                    )
                                    : ($activePatterns !== [] && request()->routeIs(...$activePatterns));
                                $submenuId = 'menu-' . $item['key'];
                            @endphp
                            @if ($isGroup)
                                <li @class (['menu-item hs-accordion', 'active' => $isActive])>
                                    <a
                                        href="javascript:void(0)"
                                        class="hs-accordion-toggle menu-link"
                                        aria-controls="{{ $submenuId }}"
                                        aria-expanded="{{ $isActive ? 'true' : 'false' }}"
                                    >
                                        <span class="menu-icon"><i class="iconify {{ $item['icon'] }}"></i></span>
                                        <span class="menu-text">{{ $item['label'] }}</span>
                                        <span class="menu-arrow"></span>
                                    </a>

                                    <ul
                                        @class ([
                                        'sub-menu hs-accordion-content hs-accordion-group',
                                        'hidden' => ! $isActive,
                                    ])
                                        id="{{ $submenuId }}"
                                    >
                                        @foreach ($item['children'] as $child)
                                            @php
                                                $childPatterns = $child['active'] ?? [$child['route']];
                                                $childActive = $childPatterns !== [] && request()->routeIs(...$childPatterns);
                                            @endphp
                                            <li class="menu-item">
                                                <a
                                                    @class (['menu-link', 'active' => $childActive])
                                                    href="{{ route($child['route']) }}"
                                                >
                                                    <span class="menu-text">{{ $child['label'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li class="menu-item">
                                    <a @class (['menu-link', 'active' => $isActive]) href="{{ route($item['route']) }}">
                                        <span class="menu-icon"><i class="iconify {{ $item['icon'] }}"></i></span>
                                        <span class="menu-text">{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</aside>
