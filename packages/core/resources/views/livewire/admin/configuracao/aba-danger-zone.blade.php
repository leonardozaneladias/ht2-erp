<div class="grid gap-6">
    <div class="border-danger/30 rounded-lg border p-1">
        <x-shared.card
            title="Restaurar identidade e aparência"
            subtitle="Volta marca, cores e layout ao padrão de fábrica. Remove logos/favicon enviados. Não afeta empresa, e-mail, segurança ou localização."
        >
            <div class="grid max-w-md gap-3">
                <x-shared.input
                    name="confirmacaoReset"
                    label="Para confirmar, digite RESETAR"
                    wire:model.live="confirmacaoReset"
                    placeholder="RESETAR"
                    autocomplete="off"
                />
                <div>
                    <x-shared.loading-button
                        variant="danger"
                        icon="tabler--restore"
                        target="resetarAparencia"
                        wire:click="resetarAparencia"
                        :disabled="$confirmacaoReset !== 'RESETAR'"
                    >
                        Restaurar ao padrão
                    </x-shared.loading-button>
                </div>
            </div>
        </x-shared.card>
    </div>

    <div class="border-danger/30 rounded-lg border p-1">
        <x-shared.card
            title="Expurgar logs de auditoria antigos"
            subtitle="Remove definitivamente os registros mais antigos que o período de retenção (definido em Segurança). Ação irreversível."
        >
            <div class="grid max-w-md gap-3">
                <x-shared.input
                    name="confirmacaoExpurgo"
                    label="Para confirmar, digite EXPURGAR"
                    wire:model.live="confirmacaoExpurgo"
                    placeholder="EXPURGAR"
                    autocomplete="off"
                />
                <div>
                    <x-shared.loading-button
                        variant="danger"
                        icon="tabler--trash"
                        target="expurgarLogs"
                        wire:click="expurgarLogs"
                        :disabled="$confirmacaoExpurgo !== 'EXPURGAR'"
                    >
                        Expurgar logs agora
                    </x-shared.loading-button>
                </div>
            </div>
        </x-shared.card>
    </div>
</div>
