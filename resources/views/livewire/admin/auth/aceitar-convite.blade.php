<x-admin.auth-form-card>
    @if (! $conviteValido)
        <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Convite inválido</h4>
        <p class="text-default-400 mb-6 text-center text-sm">Este convite é inválido, já foi utilizado ou expirou. Solicite um novo ao administrador do sistema.</p>
        <p class="text-default-400 text-center text-sm">
            <a class="text-primary font-semibold underline underline-offset-4" href="{{ route('admin.login') }}">
                ← Voltar para o login
            </a>
        </p>
    @else
        <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Ativar sua conta</h4>
        <p class="text-default-400 mb-9 text-center text-sm">Defina a senha de acesso para <span class="font-semibold">{{ $emailConvidado }}</span>.</p>
        <form wire:submit="aceitar">
            <div class="mb-5">
                <x-shared.password-input
                    name="password"
                    label="Senha"
                    wire:model="password"
                    placeholder="••••••••"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="mb-5">
                <x-shared.password-input
                    name="password_confirmation"
                    label="Confirmar senha"
                    wire:model="password_confirmation"
                    placeholder="••••••••"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="mb-6">
                <x-shared.password-strength-meter target="password" />
            </div>

            @if ($errors->any())
                <x-shared.alert variant="danger" class="mb-5">{{ $errors->first() }}</x-shared.alert>
            @endif

            <x-shared.loading-button type="submit" class="w-full py-3" wire:target="aceitar">
                Ativar conta
            </x-shared.loading-button>
        </form>
    @endif
</x-admin.auth-form-card>
