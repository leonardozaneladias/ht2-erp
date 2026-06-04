<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Verificação em duas etapas</h4>

    @if (! $usarRecovery)
        <p class="text-default-400 mx-auto mb-9 w-full text-center text-sm lg:w-72">Informe o código de 6 dígitos do seu aplicativo autenticador.</p>
        <form wire:submit="verificar">
            <div class="mb-5">
                <x-shared.input
                    name="codigo"
                    label="Código de verificação"
                    wire:model="codigo"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="000000"
                    autofocus
                />
            </div>

            @error ('codigo')
                <x-shared.alert variant="danger" class="mb-5">{{ $message }}</x-shared.alert>
            @enderror

            <x-shared.loading-button type="submit" class="w-full py-3" wire:target="verificar">
                Verificar
            </x-shared.loading-button>
        </form>
        <button
            type="button"
            wire:click="$set('usarRecovery', true)"
            class="text-primary mt-6 block w-full text-center text-sm font-semibold underline underline-offset-4"
        >
            Usar um código de recuperação
        </button>
    @else
        <p class="text-default-400 mx-auto mb-9 w-full text-center text-sm lg:w-72">Informe um dos códigos de recuperação salvos ao ativar o 2FA.</p>
        <form wire:submit="verificar">
            <div class="mb-5">
                <x-shared.input
                    name="recoveryCode"
                    label="Código de recuperação"
                    wire:model="recoveryCode"
                    placeholder="XXXXX-XXXXX"
                    autofocus
                />
            </div>

            @error ('recoveryCode')
                <x-shared.alert variant="danger" class="mb-5">{{ $message }}</x-shared.alert>
            @enderror

            <x-shared.loading-button type="submit" class="w-full py-3" wire:target="verificar">
                Verificar
            </x-shared.loading-button>
        </form>
        <button
            type="button"
            wire:click="$set('usarRecovery', false)"
            class="text-primary mt-6 block w-full text-center text-sm font-semibold underline underline-offset-4"
        >
            Usar o aplicativo autenticador
        </button>
    @endif
</x-admin.auth-form-card>
