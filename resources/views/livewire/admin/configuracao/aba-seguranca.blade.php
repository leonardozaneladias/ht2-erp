<div>
    <form wire:submit="salvar" class="grid gap-6">
        <x-shared.card
            title="Política de senha"
            subtitle="Exigências aplicadas ao cadastrar ou alterar senhas de usuários."
        >
            <div class="md:max-w-xs">
                <x-shared.select
                    name="senha_min_caracteres"
                    label="Tamanho mínimo"
                    wire:model="senha_min_caracteres"
                    :value="$senha_min_caracteres"
                    :placeholder="null"
                    :options="$opcoesMinimo"
                />
            </div>
            <div class="mt-2 space-y-1">
                <x-shared.toggle
                    name="senha_exige_maiuscula"
                    label="Exigir letras maiúsculas e minúsculas"
                    wire:model="senha_exige_maiuscula"
                />
                <x-shared.toggle name="senha_exige_numero" label="Exigir números" wire:model="senha_exige_numero" />
                <x-shared.toggle
                    name="senha_exige_especial"
                    label="Exigir símbolos (!@#$…)"
                    wire:model="senha_exige_especial"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Sessão e acesso" subtitle="Controle de tempo de sessão e autenticação.">
            <div class="md:max-w-xs">
                <x-shared.select
                    name="sessao_timeout_minutos"
                    label="Expirar sessão após"
                    wire:model="sessao_timeout_minutos"
                    :value="$sessao_timeout_minutos"
                    :placeholder="null"
                    :options="$opcoesTimeout"
                    hint="Tempo de inatividade até exigir novo login."
                />
            </div>
            <div class="flex items-center gap-2">
                <x-shared.toggle
                    name="exigir_2fa_admin"
                    label="Exigir 2FA para administradores"
                    wire:model="exigir_2fa_admin"
                />
                <x-shared.badge variant="warning">em breve</x-shared.badge>
            </div>
        </x-shared.card>

        <x-shared.card title="Auditoria" subtitle="Retenção dos registros de atividade.">
            <div class="md:max-w-xs">
                <x-shared.select
                    name="dias_retencao_logs"
                    label="Manter logs por"
                    wire:model="dias_retencao_logs"
                    :value="$dias_retencao_logs"
                    :placeholder="null"
                    :options="$opcoesRetencao"
                />
            </div>
        </x-shared.card>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar segurança
            </x-shared.loading-button>
        </div>
    </form>
</div>
