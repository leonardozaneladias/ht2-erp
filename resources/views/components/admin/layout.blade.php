@props ([
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => null,
    'withLivewire' => true,
    'renderHeader' => true,
])

@php
    // Título com a empresa ativa (white-label) — fallback: nome do sistema.
    $pageTitle = app(\App\Services\Admin\Settings\BrandingService::class)->tituloPagina($title);
    // Aparência/layout vinda dos settings da instância (antes fixa aqui).
    $appearance = app(\App\Services\Admin\Settings\AppearanceService::class);
@endphp

<!DOCTYPE html>
<html
    lang="pt-BR"
    data-theme="{{ $appearance->temaPadrao() }}"
    data-skin="{{ $appearance->skin() }}"
    data-layout-width="{{ $appearance->layoutWidth() }}"
    data-layout-position="fixed"
    data-topbar-color="{{ $appearance->topbarColor() }}"
    data-menu-color="{{ $appearance->menuColor() }}"
    data-sidenav-size="{{ $appearance->sidenavSizePadrao() }}"
    @if ($appearance->sidenavUser()) data-sidenav-user="true" @endif
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ config('app.name') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @isset ($styles)
        {{ $styles }}
    @endisset

    <x-admin.partials.theme-bootstrap />

    @vite (['resources/css/admin.css', 'resources/js/admin.js'])

    {{-- Identidade visual configurável: favicon + cores do tema (sobrescreve o @theme em runtime). --}}
    <x-admin.branding-css />

    @if ($withLivewire)
        @livewireStyles
    @endif
</head>

<body>
    <div class="wrapper">
        <x-admin.impersonation-banner />
        <x-admin.topbar />
        <x-admin.sidebar />

        <div class="page-content">
            <main>
                <div class="container-fluid">
                    @if (filled($title) && $renderHeader)
                        <x-admin.page-header :title="$title" :subtitle="$subtitle" :breadcrumbs="$breadcrumbs">
                            @isset ($actions)
                                <x-slot:actions>
                                    {{ $actions }}
                                </x-slot:actions>
                            @endisset
                        </x-admin.page-header>
                    @endif

                    {{ $slot }}
                </div>
            </main>

            <x-admin.footer />
        </div>
    </div>

    <x-shared.toast-container />

    @foreach ([
            'success' => 'success',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
        ] as $flashKey => $flashVariant)
        @if (session()->has($flashKey))
            <script>
                window.addEventListener(
                    'DOMContentLoaded',
                    () => {
                        window.dispatchEvent(
                            new CustomEvent('toast', {
                                detail: {
                                    variant: @json ($flashVariant),
                                    message: @json (session($flashKey)),
                                },
                            }),
                        );
                    },
                    { once: true },
                );
            </script>
        @endif
    @endforeach

    @livewireScripts

    @isset ($scripts)
        {{ $scripts }}
    @endisset
</body>
</html>
