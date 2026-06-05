@props ([
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => null,
    'withLivewire' => true,
    'renderHeader' => true,
])

@php
    $pageTitle = filled($title)
        ? sprintf('%s | %s', $title, config('app.name'))
        : config('app.name');
@endphp

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
