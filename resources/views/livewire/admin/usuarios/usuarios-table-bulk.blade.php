@php
    $selecionados = count($this->checkboxValues);
@endphp

@if ($selecionados > 0)
    <div class="border-primary/25 bg-primary/8 mb-3 flex flex-wrap items-center gap-2 rounded-[10px] border px-3 py-2">
        <span class="text-default-700 inline-flex items-center gap-2 text-sm font-medium">
            <span
                class="bg-primary inline-flex size-5 items-center justify-center rounded-full text-xs font-bold text-white"
            >
                {{ $selecionados }}
            </span>
            usuário(s) selecionado(s)
        </span>

        <div class="ms-auto flex flex-wrap items-center gap-2">
            <div class="w-48">
                <x-shared.select
                    name="perfilEmMassa"
                    :options="array_merge([['value' => '', 'label' => 'Atribuir perfil...']], $this->perfisAtribuiveis)"
                    :placeholder="null"
                    wire:model="perfilEmMassa"
                />
            </div>

            <x-shared.button
                variant="primary"
                size="sm"
                wire:click="atribuirPerfilEmMassa"
                wire:confirm="Atribuir o perfil aos usuários selecionados?"
            >
                Aplicar perfil
            </x-shared.button>

            <x-shared.button
                variant="success"
                appearance="soft"
                size="sm"
                wire:click="alternarStatusEmMassa(true)"
                wire:confirm="Reativar os usuários selecionados?"
            >
                Reativar
            </x-shared.button>

            <x-shared.button
                variant="warning"
                appearance="soft"
                size="sm"
                wire:click="alternarStatusEmMassa(false)"
                wire:confirm="Desativar os usuários selecionados?"
            >
                Desativar
            </x-shared.button>

            <x-shared.button variant="default" appearance="ghost" size="sm" wire:click="limparSelecao">
                Limpar
            </x-shared.button>
        </div>
    </div>
@endif
