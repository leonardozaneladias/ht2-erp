{{-- resources/views/components/admin/auth-layout.blade.php --}}
@props ([
    'title' => null,
    'heroSubtitle' => null,
])

@inject ('brandingService', 'App\Services\Admin\Settings\BrandingService')
@inject ('loginSettings', 'App\Settings\LoginSettings')

@php
    $pageTitle = filled($title)
        ? sprintf('%s — %s', $title, $brandingService->nomeSistema())
        : $brandingService->nomeSistema();

    $heroFundo = $loginSettings->fundo_imagem_arquivo !== ''
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($loginSettings->fundo_imagem_arquivo)
        : null;

    $heroTexto = $heroSubtitle ?? ($brandingService->slogan() ?: 'Painel administrativo.');
@endphp

<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" data-skin="default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $pageTitle }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ $brandingService->faviconUrl() }}" />

    <x-admin.partials.theme-bootstrap />

    @vite (['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <div class="min-h-screen">
        <div class="flex h-full w-full">
            {{-- Painel esquerdo: hero (oculto no mobile) --}}
            <div class="hidden w-full md:block">
                <div
                    @class ([
                            'relative h-full overflow-hidden bg-cover bg-center bg-no-repeat',
                            'bg-auth-hero' => ! $heroFundo,
                        ])
                    @if ($heroFundo) style="background-image: url('{{ $heroFundo }}')" @endif
                >
                    <div class="absolute inset-0 bg-linear-to-t from-black/60 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-9">
                        @if ($loginSettings->mostrar_logo)
                            <img
                                alt="{{ $brandingService->nomeSistema() }}"
                                class="mb-5 h-7"
                                src="{{ $brandingService->logoUrl('light') }}"
                            />
                        @endif
                        <p class="text-lg font-bold text-white">{{ $brandingService->nomeSistema() }}</p>
                        <p class="mt-1 text-sm text-white/60">{{ $heroTexto }}</p>
                    </div>
                </div>
            </div>

            {{-- Painel direito: recebe x-admin.auth-form-card via $slot --}}
            <div class="min-w-full md:max-w-118 md:min-w-106">{{ $slot }}</div>
        </div>
    </div>
</body>
</html>
