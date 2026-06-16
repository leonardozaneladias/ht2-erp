<div>
    <form wire:submit="solicitarSalvar" class="grid gap-6">
        <x-shared.card
            title="Política de senha"
            subtitle="Exigências aplicadas ao cadastrar ou alterar senhas de usuários."
        >
            <div class="md:max-w-xs">
                <x-shared.select-search
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
                <x-shared.select-search
                    name="sessao_timeout_minutos"
                    label="Expirar sessão após"
                    wire:model="sessao_timeout_minutos"
                    :value="$sessao_timeout_minutos"
                    :placeholder="null"
                    :options="$opcoesTimeout"
                    hint="Tempo de inatividade até exigir novo login."
                />
            </div>
            <div class="mt-2">
                <x-shared.toggle
                    name="exigir_2fa_admin"
                    label="Exigir 2FA para administradores"
                    wire:model="exigir_2fa_admin"
                />
                <p class="text-default-500 mt-1 text-sm">Quando ativada, administradores sem 2FA são direcionados a configurá-lo antes de acessar o sistema. Desligada por padrão.</p>
            </div>
        </x-shared.card>

        <x-shared.card title="Auditoria" subtitle="Retenção dos registros de atividade.">
            <div class="md:max-w-xs">
                <x-shared.select-search
                    name="dias_retencao_logs"
                    label="Manter logs por"
                    wire:model="dias_retencao_logs"
                    :value="$dias_retencao_logs"
                    :placeholder="null"
                    :options="$opcoesRetencao"
                />
            </div>
        </x-shared.card>

        <x-shared.card
            title="Proteção de acesso"
            subtitle="Rate-limit de login, bloqueio de conta e alertas de segurança."
        >
            <div class="grid gap-4 md:max-w-xs">
                <x-shared.input
                    type="number"
                    name="login_max_tentativas"
                    label="Máx. de tentativas de login"
                    wire:model="login_max_tentativas"
                    :value="$login_max_tentativas"
                    min="1"
                    max="100"
                    hint="Tentativas por janela antes de bloquear o rate-limit."
                />
                <x-shared.input
                    type="number"
                    name="login_janela_minutos"
                    label="Janela do rate-limit (min)"
                    wire:model="login_janela_minutos"
                    :value="$login_janela_minutos"
                    min="1"
                    max="60"
                    hint="Duração da janela de contagem de tentativas (minutos)."
                />
                <x-shared.input
                    type="number"
                    name="lockout_max_falhas"
                    label="Falhas até bloquear a conta"
                    wire:model="lockout_max_falhas"
                    :value="$lockout_max_falhas"
                    min="1"
                    max="100"
                    hint="Quantidade de falhas consecutivas para bloquear o usuário."
                />
                <x-shared.input
                    type="number"
                    name="lockout_duracao_minutos"
                    label="Duração do bloqueio (min)"
                    wire:model="lockout_duracao_minutos"
                    :value="$lockout_duracao_minutos"
                    min="1"
                    max="1440"
                    hint="Tempo em minutos que a conta fica bloqueada após exceder as falhas."
                />
            </div>
            <div class="mt-4 space-y-1">
                <x-shared.toggle
                    name="alertas_seguranca_habilitados"
                    label="Alertas de segurança por e-mail"
                    wire:model="alertas_seguranca_habilitados"
                />
                <x-shared.toggle
                    name="alerta_login_super_admin"
                    label="Alertar login de super-admin"
                    wire:model="alerta_login_super_admin"
                />
            </div>
        </x-shared.card>

        <div class="flex justify-end">
            <x-shared.loading-button target="solicitarSalvar" icon="tabler--device-floppy">
                Salvar segurança
            </x-shared.loading-button>
        </div>
    </form>

    @include ('admin.partials.confirms-password')
    @include ('admin.partials.confirms-two-factor')
</div>
