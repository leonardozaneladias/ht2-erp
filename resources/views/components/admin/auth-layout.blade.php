{{-- resources/views/components/admin/auth-layout.blade.php --}}
@props ([
    'title'        => null,
    'heroSubtitle' => 'Painel administrativo.',
])

@php
    $pageTitle = filled($title)
        ? sprintf('%s | %s', $title, config('app.name'))
        : config('app.name');
@endphp

<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" data-skin="default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $pageTitle }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" />

    <x-admin.partials.theme-bootstrap />

    @vite (['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <div class="min-h-screen">
        <div class="flex h-full w-full">
            {{-- Painel esquerdo: hero (oculto no mobile) --}}
            <div class="hidden w-full md:block">
                <div
                    class="relative h-full overflow-hidden bg-[url('/images/auth.jpg')] bg-cover bg-center bg-no-repeat"
                >
                    <div class="absolute inset-0 bg-linear-to-t from-black/60 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-9">
                        <img
                            alt="{{ config('app.name') }}"
                            class="mb-5 h-7"
                            src="{{ asset(config('branding.logo_path')) }}"
                        />
                        <p class="text-lg font-bold text-white">{{ config('app.name') }}</p>
                        <p class="mt-1 text-sm text-white/60">{{ $heroSubtitle }}</p>
                    </div>
                </div>
            </div>

            {{-- Painel direito: recebe x-admin.auth-form-card via $slot --}}
            <div class="min-w-full md:max-w-118 md:min-w-106">{{ $slot }}</div>
        </div>
    </div>
</body>
</html>
