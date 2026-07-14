{{-- resources/views/components/admin/auth-form-card.blade.php --}}
@inject ('brandingService', 'App\Services\Admin\Settings\BrandingService')
@inject ('loginSettings', 'App\Settings\LoginSettings')

<div class="card relative flex min-h-screen flex-col justify-between rounded-none p-12.5">
    <div class="mb-7.5 flex flex-col items-center justify-center">
        @if ($loginSettings->mostrar_logo)
            <a href="{{ route('admin.login') }}">
                <img
                    alt="{{ $brandingService->nomeSistema() }}"
                    class="flex h-14 dark:hidden"
                    src="{{ $brandingService->logoUrl('dark') }}"
                />
                <img
                    alt="{{ $brandingService->nomeSistema() }}"
                    class="hidden h-14 dark:flex"
                    src="{{ $brandingService->logoUrl('light') }}"
                />
            </a>
        @endif
    </div>

    <div>{{ $slot }}</div>

    <p class="text-default-400 mt-7.5 text-center text-sm">
        &copy; {{ date('Y') }} {{ $brandingService->nomeSistema() }}
        @if ($loginSettings->mostrar_versao)
            <span class="mt-1 block text-xs">versão {{ config('app.version', '1.0.0') }}</span>
        @endif
    </p>
</div>
