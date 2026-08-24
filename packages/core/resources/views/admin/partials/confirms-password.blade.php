{{-- Modal de reconfirmação de senha. Inclua via @include num componente que use
     o trait HT2ML\Core\Livewire\Concerns\ConfirmsPassword (herda o estado por escopo). --}}
@if ($confirmandoSenha)
    <div class="fixed inset-0 z-90 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" wire:click="cancelarConfirmacao"></div>

        <div class="border-default-300 bg-card relative z-10 w-full max-w-md rounded-xl border p-6 shadow-lg">
            <h3 class="text-body-color text-lg font-semibold">Confirme sua senha</h3>
            <p class="text-default-500 mt-1 mb-4 text-sm">Por segurança, confirme sua senha para concluir esta ação.</p>

            <form wire:submit="confirmarSenha">
                <x-shared.password-input
                    name="senhaConfirmacao"
                    label="Senha"
                    wire:model="senhaConfirmacao"
                    autocomplete="current-password"
                    autofocus
                />

                <div class="mt-5 flex justify-end gap-2">
                    <x-shared.button type="button" variant="light" wire:click="cancelarConfirmacao">
                        Cancelar
                    </x-shared.button>
                    <x-shared.loading-button type="submit" target="confirmarSenha" icon="tabler--lock-check">
                        Confirmar
                    </x-shared.loading-button>
                </div>
            </form>
        </div>
    </div>
@endif
