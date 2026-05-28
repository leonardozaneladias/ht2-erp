<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Esqueceu sua senha?</h4>
    <p class="text-default-400 mx-auto mb-9 w-full text-center text-sm lg:w-72">Digite seu e-mail e enviaremos um link para redefinir sua senha.</p>

    @if (session('status'))
        <x-shared.alert variant="success" class="mb-6">{{ session('status') }}</x-shared.alert>
    @endif

    <form wire:submit="sendLink">
        <div class="mb-6">
            <x-shared.input
                name="email"
                label="Endereço de e-mail"
                type="email"
                wire:model="email"
                icon="tabler--mail"
                placeholder="admin@exemplo.com.br"
                required
                autofocus
                autocomplete="email"
            />
        </div>

        @if ($errors->has('email'))
            <x-shared.alert variant="danger" class="mb-5">{{ $errors->first('email') }}</x-shared.alert>
        @endif

        <x-shared.loading-button type="submit" class="w-full py-3" wire:target="sendLink">
            Enviar link de redefinição
        </x-shared.loading-button>
    </form>

    <p class="text-default-400 mt-7.5 text-center text-sm">
        Lembrou a senha?
        <a class="text-primary font-semibold underline underline-offset-4" href="{{ route('admin.login') }}">
            Voltar para o login
        </a>
    </p>
</x-admin.auth-form-card>
