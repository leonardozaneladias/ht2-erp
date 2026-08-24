@inject ('loginSettings', 'HT2ML\Core\Settings\LoginSettings')

<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">{{ $loginSettings->titulo }}</h4>
    <p class="text-default-400 mx-auto mb-9 w-full text-center text-sm lg:w-72">{{ $loginSettings->subtitulo }}</p>

    @if (session('status'))
        <x-shared.alert variant="success" class="mb-6">{{ session('status') }}</x-shared.alert>
    @endif

    <form wire:submit="authenticate">
        <div class="mb-5">
            <x-shared.input
                name="email"
                label="E-mail"
                type="email"
                wire:model="email"
                icon="tabler--mail"
                placeholder="admin@exemplo.com.br"
                required
                autofocus
                autocomplete="email"
            />
        </div>

        <div class="mb-5">
            <x-shared.password-input
                name="password"
                label="Senha"
                wire:model="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            />
        </div>

        @if ($errors->any())
            <x-shared.alert variant="danger" class="mb-5">{{ $errors->first() }}</x-shared.alert>
        @endif

        <div class="mb-6 flex items-center justify-between">
            <x-shared.checkbox name="remember" label="Lembrar de mim" wire:model="remember" />
            <a
                class="text-primary text-sm font-semibold underline underline-offset-4"
                href="{{ route('admin.password.request') }}"
            >
                Esqueceu sua senha?
            </a>
        </div>

        <x-shared.loading-button type="submit" class="w-full py-3" wire:target="authenticate">
            Entrar
        </x-shared.loading-button>
    </form>
</x-admin.auth-form-card>
