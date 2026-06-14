<div>
    <x-shared.card
        class="rounded-t-none border-t-0"
        title="Dados da empresa"
        subtitle="Informações do cliente dono desta instalação."
    >
        <form wire:submit="salvar" class="grid gap-4">
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input name="nome_cliente" label="Nome do cliente" wire:model="nome_cliente" required />
                <x-shared.input name="razao_social" label="Razão social" wire:model="razao_social" />
                <x-shared.cnpj-input name="cnpj" wire:model="cnpj" />
                <x-shared.input name="inscricao_estadual" label="Inscrição estadual" wire:model="inscricao_estadual" />
                <x-shared.phone-input name="telefone" wire:model="telefone" />
                <x-shared.input
                    name="email_contato"
                    label="E-mail de contato"
                    type="email"
                    wire:model="email_contato"
                />
            </div>

            <div class="grid gap-x-4 md:grid-cols-6">
                <div class="md:col-span-4">
                    <x-shared.input name="endereco" label="Endereço" wire:model="endereco" />
                </div>
                <div class="md:col-span-2">
                    <x-shared.input name="numero" label="Número" wire:model="numero" />
                </div>
            </div>

            <div class="grid gap-x-4 md:grid-cols-4">
                <x-shared.cep-input name="cep" wire:model="cep" />
                <x-shared.input name="bairro" label="Bairro" wire:model="bairro" />
                <x-shared.input name="cidade" label="Cidade" wire:model="cidade" />
                <x-shared.input name="estado" label="UF" wire:model="estado" maxlength="2" />
            </div>

            <x-shared.input name="site_url" label="Site" type="url" wire:model="site_url" placeholder="https://" />

            <div class="border-default-300 dark:border-default-700 flex justify-end border-t pt-4">
                <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                    Salvar alterações
                </x-shared.loading-button>
            </div>
        </form>
    </x-shared.card>
</div>
