<div class="space-y-6">
    <x-admin.page-header title="Logs de auditoria" subtitle="Histórico append-only de mudanças relevantes do painel." />

    @if ($this->podeExpurgar)
        <div class="mb-4 flex justify-end">
            <x-shared.button
                type="button"
                variant="light"
                icon="tabler--trash"
                wire:click="expurgar"
                wire:confirm="Expurgar os logs de auditoria além do teto de retenção? Esta ação não pode ser desfeita."
            >
                Expurgar logs antigos
            </x-shared.button>
        </div>
    @endif

    <livewire:admin.auditoria.auditoria-table />
</div>
