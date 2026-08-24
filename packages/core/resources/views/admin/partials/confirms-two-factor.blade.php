{{-- Modal de step-up 2FA. Inclua via @include num componente que use o trait
     HT2ML\Core\Livewire\Concerns\ConfirmaSegundoFator (herda o estado por escopo). --}}
@if ($confirmando2fa)
    <div class="fixed inset-0 z-90 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" wire:click="cancelarConfirmacao2fa"></div>

        <div class="border-default-300 bg-card relative z-10 w-full max-w-md rounded-xl border p-6 shadow-lg">
            <h3 class="text-body-color text-lg font-semibold">Confirmação em duas etapas</h3>
            <p class="text-default-500 mt-1 mb-4 text-sm">Por segurança, confirme um código atual para concluir esta ação.</p>

            <form wire:submit="confirmar2fa">
                @if (! $usarRecovery2fa)
                    <x-shared.input
                        name="codigo2fa"
                        label="Código do aplicativo autenticador"
                        wire:model="codigo2fa"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        autofocus
                    />
                @else
                    <x-shared.input
                        name="codigo2fa"
                        label="Código de recuperação"
                        wire:model="codigo2fa"
                        placeholder="XXXXX-XXXXX"
                        autofocus
                    />
                @endif

                <button
                    type="button"
                    wire:click="$toggle('usarRecovery2fa')"
                    class="text-primary mt-3 text-sm font-semibold underline underline-offset-4"
                >
                    {{ $usarRecovery2fa ? 'Usar o aplicativo autenticador' : 'Usar um código de recuperação' }}
                </button>

                <div class="mt-5 flex justify-end gap-2">
                    <x-shared.button type="button" variant="light" wire:click="cancelarConfirmacao2fa">
                        Cancelar
                    </x-shared.button>
                    <x-shared.loading-button type="submit" target="confirmar2fa" icon="tabler--shield-check">
                        Confirmar
                    </x-shared.loading-button>
                </div>
            </form>
        </div>
    </div>
@endif
