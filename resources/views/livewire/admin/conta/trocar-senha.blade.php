<x-shared.card class="rounded-t-none border-t-0" title="Senha" subtitle="Troque sua senha de acesso ao painel.">
    <form wire:submit="trocar" class="grid max-w-md gap-4">
        <x-shared.password-input
            name="senhaAtual"
            label="Senha atual"
            wire:model="senhaAtual"
            autocomplete="current-password"
        />

        <x-shared.password-input
            name="novaSenha"
            label="Nova senha"
            wire:model="novaSenha"
            autocomplete="new-password"
            :hint="$politica"
            with-meter
        />

        <x-shared.password-input
            name="novaSenha_confirmation"
            label="Confirmar nova senha"
            wire:model="novaSenha_confirmation"
            autocomplete="new-password"
        />

        <div class="flex justify-end">
            <x-shared.loading-button target="trocar" icon="tabler--lock">Alterar senha</x-shared.loading-button>
        </div>
    </form>
</x-shared.card>
