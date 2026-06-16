<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Verificação em duas etapas</h4>

    @if ($usarEmail)
        <p class="text-default-400 mx-auto mb-9 w-full text-center text-sm lg:w-72">Enviamos um código de 6 dígitos para <span class="text-default-700 font-medium">{{ $emailMascarado }}</span>. Informe-o abaixo.</p>
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
            wire:click="reenviarCodigoEmail"
            wire:target="reenviarCodigoEmail"
            class="text-primary mt-6 block w-full text-center text-sm font-semibold underline underline-offset-4"
        >
            Reenviar código
        </button>
        @if ($temTotp)
            <button
                type="button"
                wire:click="$set('usarEmail', false)"
                class="text-default-500 mt-3 block w-full text-center text-sm"
            >
                Voltar ao aplicativo autenticador
            </button>
        @endif
    @elseif ($usarRecovery)
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
    @else
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
        <div class="border-default-200 mt-6 space-y-2 border-t pt-4">
            <p class="text-default-400 text-center text-xs tracking-wide uppercase">Tentar outro método de entrada</p>
            <button
                type="button"
                wire:click="$set('usarRecovery', true)"
                class="text-primary block w-full text-center text-sm font-semibold underline underline-offset-4"
            >
                Usar um código de recuperação
            </button>
            @if ($emailDisponivel)
                <button
                    type="button"
                    wire:click="usarMetodoEmail"
                    wire:target="usarMetodoEmail"
                    class="text-primary block w-full text-center text-sm font-semibold underline underline-offset-4"
                >
                    Receber um código por e-mail
                </button>
            @endif
        </div>
    @endif
</x-admin.auth-form-card>
