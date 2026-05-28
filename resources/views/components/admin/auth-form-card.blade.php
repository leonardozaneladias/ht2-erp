{{-- resources/views/components/admin/auth-form-card.blade.php --}}
<div class="card relative flex min-h-screen flex-col justify-between rounded-none p-12.5">
    <div class="mb-7.5 flex flex-col items-center justify-center">
        <a href="{{ route('admin.login') }}">
            <img
                alt="{{ config('app.name') }}"
                class="flex h-8 dark:hidden"
                src="{{ asset(config('branding.logo_dark_path')) }}"
            />
            <img
                alt="{{ config('app.name') }}"
                class="hidden h-8 dark:flex"
                src="{{ asset(config('branding.logo_path')) }}"
            />
        </a>
    </div>

    <div>{{ $slot }}</div>

    <p class="text-default-400 mt-7.5 text-center text-sm">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
</div>
