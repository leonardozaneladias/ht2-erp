<div>
    @if ($aberto)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50" wire:click="fechar"></div>

            <div class="border-default-300 bg-card relative z-10 w-full max-w-md rounded-xl border p-6 shadow-lg">
                <h3 class="text-danger text-lg font-semibold">Anonimizar {{ $alvoNome }}</h3>
                <p class="text-default-500 mt-1 mb-4 text-sm">Ação <strong>irreversível</strong>: remove a PII deste usuário, revoga acessos e desativa a conta. Digite <strong>ANONIMIZAR</strong> para confirmar.</p>

                <form wire:submit="confirmar">
                    <x-shared.input
                        name="confirmacao"
                        label="Confirmação"
                        wire:model="confirmacao"
                        placeholder="ANONIMIZAR"
                        autofocus
                    />

                    <div class="mt-5 flex justify-end gap-2">
                        <x-shared.button type="button" variant="light" wire:click="fechar">Cancelar</x-shared.button>
                        <x-shared.loading-button
                            type="submit"
                            target="confirmar"
                            variant="danger"
                            icon="tabler--user-off"
                        >
                            Anonimizar
                        </x-shared.loading-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include ('admin.partials.confirms-password')
</div>
