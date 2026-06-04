@php
    $user = auth('admin')->user();
    $showAllItems = $user === null || request()->routeIs('admin.dev.components.*');

    $canView = static function (?string $permission) use ($showAllItems, $user): bool {
        if ($permission === null || $showAllItems) {
            return true;
        }

        return $user !== null && method_exists($user, 'can') && $user->can($permission);
    };

    $displayUser = [
        'nome' => $user?->nome ?? config('branding.user_default_name'),
        'perfil' => $user?->getRoleNames()->first() ?? config('branding.user_default_role'),
        'avatar' => $user?->avatar_url ?: asset(config('branding.avatar_default')),
    ];

    $sections = collect(config('admin-menu', []))
        ->map(function (array $section) use ($canView) {
            $items = collect($section['items'])
                ->map(function (array $item) use ($canView) {
                    $children = collect($item['children'] ?? [])
                        ->filter(fn (array $child) => $canView($child['permission'] ?? null))
                        ->values()
                        ->all();

                    $isVisible = $children !== [] || $canView($item['permission'] ?? null);

                    return array_merge($item, [
                        'children' => $children,
                        'visible' => $isVisible,
                    ]);
                })
                ->filter(fn (array $item) => $item['visible'])
                ->values()
                ->all();

            return array_merge($section, ['items' => $items]);
        })
        ->filter(fn (array $section) => $section['items'] !== [])
        ->values()
        ->all();
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
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <a class="link-reset block" href="{{ route('admin.perfil.show') }}">
                            <img
                                alt="{{ $displayUser['nome'] }}"
                                class="mb-3 size-9 rounded-full"
                                src="{{ $displayUser['avatar'] }}"
                            />
                            <span class="sidenav-user-name block truncate font-bold">{{ $displayUser['nome'] }}</span>
                            <span class="text-xs font-semibold">{{ $displayUser['perfil'] }}</span>
                        </a>
                    </div>

                    <x-shared.dropdown placement="bottom-end">
                        <x-slot:button>
                            <button
                                type="button"
                                class="hs-dropdown-toggle cursor-pointer"
                                aria-label="Abrir menu rápido do usuário"
                            >
                                <i class="iconify tabler--settings ms-1 size-6 align-middle"></i>
                            </button>
                        </x-slot:button>

                        <x-shared.dropdown-item icon="tabler--user-circle" :href="route('admin.perfil.show')">
                            Meu perfil
                        </x-shared.dropdown-item>
                        <x-shared.dropdown-item icon="tabler--settings-2" :href="route('admin.conta.edit')">
                            Configurações
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
                                $isActive = $activePatterns !== [] && request()->routeIs(...$activePatterns);
                                $submenuId = 'menu-' . \Illuminate\Support\Str::slug($item['label']);
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
