<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Definir nova senha</h4>
    <p class="text-default-400 mb-9 text-center text-sm">Sua nova senha deve ter pelo menos 8 caracteres.</p>

    <form wire:submit="resetPassword">
        <input type="hidden" wire:model="token" />
        <input type="hidden" wire:model="email" />

        <div class="mb-5">
            <x-shared.password-input
                name="password"
                label="Nova senha"
                wire:model="password"
                placeholder="••••••••"
                required
                autocomplete="new-password"
            />
        </div>

        <div class="mb-5">
            <x-shared.password-input
                name="password_confirmation"
                label="Confirmar nova senha"
                wire:model="password_confirmation"
                placeholder="••••••••"
                required
                autocomplete="new-password"
            />
        </div>

        <div class="mb-6">
            <x-shared.password-strength-meter field-id="password" />
        </div>

        @if ($errors->any())
            <x-shared.alert variant="danger" class="mb-5">{{ $errors->first() }}</x-shared.alert>
        @endif

        <x-shared.loading-button type="submit" class="w-full py-3" wire:target="resetPassword">
            Redefinir senha
        </x-shared.loading-button>
    </form>

    <p class="text-default-400 mt-7.5 text-center text-sm">
        <a class="text-primary font-semibold underline underline-offset-4" href="{{ route('admin.login') }}">
            ← Voltar para o login
        </a>
    </p>
</x-admin.auth-form-card>
