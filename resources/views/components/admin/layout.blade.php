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
    <x-admin.partials.notification-config />

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

    {{--
        Notificações que sobrevivem a um redirect (sessão flash). Fonte canônica:
        a chave `notify` (App\Livewire\Concerns\EmiteNotificacoes::notificarAposRedirect).
        As chaves soltas success/error/warning/info/status cobrem fluxos legados/externos
        (auth, middleware) e não levam título. Componentes que permanecem na página
        devem usar $this->notificarSucesso()/notificarErro() (evento `toast`).
    --}}
    @php
        $notificacoesFlash = [];

        $flashNotify = session('notify');
        if (is_array($flashNotify) && filled($flashNotify['message'] ?? null)) {
            $notificacoesFlash[] = [
                'variant' => $flashNotify['variant'] ?? 'info',
                'message' => $flashNotify['message'],
                'title' => $flashNotify['title'] ?? null,
            ];
        }

        foreach (
            ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info', 'status' => 'info']
            as $chave => $variante
        ) {
            if (session()->has($chave)) {
                $notificacoesFlash[] = ['variant' => $variante, 'message' => session($chave), 'title' => null];
            }
        }

        // Token por render: o wire:navigate reexecuta os scripts do body tanto em
        // visitas novas quanto ao restaurar snapshot (botão voltar) — o token
        // distingue re-execução do MESMO render (bloqueia) de um flash novo (dispara).
        $flashToken = (string) \Illuminate\Support\Str::uuid();
    @endphp

    @if (! empty($notificacoesFlash))
        <script>
            (() => {
                window.__flashToastsExibidos ??= new Set();
                if (window.__flashToastsExibidos.has(@json ($flashToken))) {
                    return;
                }
                window.__flashToastsExibidos.add(@json ($flashToken));

                const disparar = () => {
                    @foreach ($notificacoesFlash as $notificacao)
                    window.dispatchEvent(new CustomEvent('toast', { detail: @json ($notificacao) }));
                    @endforeach
                };

                // Em navegação SPA (wire:navigate) o DOMContentLoaded NÃO dispara de
                // novo — os scripts do body reexecutam com o DOM já pronto.
                if (document.readyState === 'loading') {
                    window.addEventListener('DOMContentLoaded', disparar, { once: true });
                } else {
                    disparar();
                }
            })();
        </script>
    @endif

    @livewireScripts

    @isset ($scripts)
        {{ $scripts }}
    @endisset
</body>
</html>
