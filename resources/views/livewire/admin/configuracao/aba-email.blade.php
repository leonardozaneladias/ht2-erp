<div>
    <form wire:submit="salvar" class="grid gap-6">
        <x-shared.card title="Servidor SMTP" subtitle="Credenciais do servidor de envio de e-mails.">
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input
                    name="smtp_host"
                    label="Servidor (host)"
                    wire:model="smtp_host"
                    placeholder="smtp.exemplo.com"
                />
                <x-shared.input name="smtp_port" label="Porta" type="number" wire:model="smtp_port" placeholder="587" />
                <x-shared.select
                    name="criptografia"
                    label="Criptografia"
                    wire:model="criptografia"
                    :value="$criptografia"
                    :placeholder="null"
                    :options="['tls' => 'TLS (porta 587)', 'ssl' => 'SSL (porta 465)', 'none' => 'Nenhuma']"
                />
                <x-shared.input name="smtp_username" label="Usuário" wire:model="smtp_username" autocomplete="off" />
                <div class="md:col-span-2">
                    <x-shared.password-input
                        name="smtp_password"
                        label="Senha"
                        wire:model="smtp_password"
                        placeholder="••••••• (deixe em branco para manter a atual)"
                        autocomplete="new-password"
                    />
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Remetente" subtitle="Como as mensagens aparecem para o destinatário.">
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input
                    name="from_email"
                    label="E-mail remetente"
                    type="email"
                    wire:model="from_email"
                    placeholder="nao-responda@exemplo.com"
                />
                <x-shared.input
                    name="from_name"
                    label="Nome remetente"
                    wire:model="from_name"
                    placeholder="Equipe Exemplo"
                />
            </div>
            <x-shared.toggle
                name="habilitar"
                label="Usar este servidor SMTP para enviar e-mails"
                wire:model="habilitar"
            />
        </x-shared.card>

        <x-shared.card
            title="Testar envio"
            subtitle="Envia uma mensagem de teste com os valores acima (sem precisar salvar)."
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="grow">
                    <x-shared.input name="emailTeste" label="Enviar teste para" type="email" wire:model="emailTeste" />
                </div>
                <div class="mb-4">
                    <x-shared.loading-button
                        type="button"
                        variant="secondary"
                        wire:click="enviarTeste"
                        target="enviarTeste"
                        icon="tabler--send"
                        loadingText="Enviando…"
                    >
                        Enviar e-mail de teste
                    </x-shared.loading-button>
                </div>
            </div>
        </x-shared.card>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar configurações de e-mail
            </x-shared.loading-button>
        </div>
    </form>
</div>
