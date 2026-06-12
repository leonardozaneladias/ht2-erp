<div>
    @if ($aberto)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50" wire:click="fechar"></div>

            <div class="border-default-300 bg-card relative z-10 w-full max-w-md rounded-xl border p-6 shadow-lg">
                <div class="flex items-center gap-3">
                    <x-shared.avatar :name="$alvoNome" :src="$alvoAvatarUrl" size="size-10" class="shrink-0" />
                    <h3 class="text-body-color text-lg font-semibold">Entrar como {{ $alvoNome }}</h3>
                </div>
                <p class="text-default-500 mt-2 mb-4 text-sm">Você vai operar o painel como este usuário. Informe o motivo — ele fica registrado na auditoria.</p>

                <form wire:submit="confirmarEntrada">
                    <x-shared.input
                        name="motivo"
                        label="Motivo"
                        wire:model="motivo"
                        placeholder="Ex.: reproduzir problema relatado no chamado #123"
                        autofocus
                    />

                    <div class="mt-5 flex justify-end gap-2">
                        <x-shared.button type="button" variant="light" wire:click="fechar"> Cancelar </x-shared.button>
                        <x-shared.loading-button type="submit" target="confirmarEntrada" icon="tabler--user-shield">
                            Entrar como usuário
                        </x-shared.loading-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include ('admin.partials.confirms-password')
</div>
