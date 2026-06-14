@props (['title' => 'Configuração inicial'])

@inject ('brandingService', 'App\Services\Admin\Settings\BrandingService')
@inject ('appearanceService', 'App\Services\Admin\Settings\AppearanceService')

@php
    $pageTitle = sprintf('%s | %s', $title, $brandingService->nomeSistema());
@endphp

<!DOCTYPE html>
<html lang="pt-BR" data-theme="{{ $appearanceService->temaPadrao() }}" data-skin="{{ $appearanceService->skin() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $pageTitle }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <x-admin.partials.theme-bootstrap />

    @vite (['resources/css/admin.css', 'resources/js/admin.js'])

    <x-admin.branding-css />

    @livewireStyles
</head>
<body>
    <div class="bg-body-bg flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-2xl">{{ $slot }}</div>
    </div>

    <x-shared.toast-container />

    @livewireScripts
</body>
</html>
