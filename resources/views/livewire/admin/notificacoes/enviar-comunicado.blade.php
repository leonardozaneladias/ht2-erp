<div>
    <x-admin.page-header title="Enviar comunicado" subtitle="Componha um aviso in-app e escolha quem deve recebê-lo." />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-shared.card title="Conteúdo do comunicado">
                <form wire:submit="enviar" class="space-y-1">
                    <x-shared.select-search
                        name="tipo"
                        label="Tipo"
                        wire:model="tipo"
                        :options="$tipos"
                        :placeholder="null"
                        required
                    />

                    <x-shared.input
                        name="titulo"
                        label="Título"
                        wire:model="titulo"
                        hint="Até 120 caracteres."
                        required
                    />

                    <x-shared.rich-editor
                        name="mensagem"
                        label="Mensagem"
                        wire:model="mensagem"
                        hint="Use negrito, itálico, listas e links. Até 1000 caracteres."
                        placeholder="Escreva o comunicado..."
                        required
                    />

                    <x-shared.select-search
                        name="publico"
                        label="Destinatários"
                        wire:model.live="publico"
                        :options="$publicos"
                        :placeholder="null"
                        required
                    />

                    @if ($publico === \HT2ML\Core\Enums\PublicoComunicado::Papel->value)
                        <x-shared.select-search
                            name="papel"
                            label="Papel"
                            wire:model="papel"
                            :options="$this->papeis->all()"
                            placeholder="Selecione um papel..."
                            required
                        />
                    @endif

                    <div class="flex justify-end pt-2">
                        <x-shared.button type="submit" icon="tabler--send" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="enviar">Enviar comunicado</span>
                            <span wire:loading wire:target="enviar">Enviando...</span>
                        </x-shared.button>
                    </div>
                </form>
            </x-shared.card>
        </div>

        <div>
            <x-shared.card title="Como funciona">
                <ul class="text-default-500 space-y-3 text-sm">
                    <li class="flex gap-2">
                        <i class="iconify tabler--info-circle text-info mt-0.5 shrink-0"></i>
                        <span>O comunicado aparece no sino e na central de notificações de cada destinatário.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="iconify tabler--users text-primary mt-0.5 shrink-0"></i>
                        <span>Escolha entre todos os usuários ativos ou apenas os de um papel específico.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="iconify tabler--history text-default-400 mt-0.5 shrink-0"></i>
                        <span>Cada envio fica registrado na trilha de auditoria.</span>
                    </li>
                </ul>
            </x-shared.card>
        </div>
    </div>
</div>
