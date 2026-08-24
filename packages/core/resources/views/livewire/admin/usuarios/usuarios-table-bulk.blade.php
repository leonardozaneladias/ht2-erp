@php
    $selecionados = count($this->checkboxValues);
    $ator = auth('admin')->user();
    $podeVerLixeira = $ator?->can('usuarios.restaurar') ?? false;
@endphp

@if ($podeVerLixeira)
    <div class="mb-3">
        <x-shared.button
            :variant="$this->verLixeira ? 'primary' : 'default'"
            appearance="outline"
            size="sm"
            :icon="$this->verLixeira ? 'tabler--arrow-back-up' : 'tabler--trash'"
            wire:click="alternarLixeira"
        >
            {{ $this->verLixeira ? 'Ver ativos' : 'Ver lixeira' }}
        </x-shared.button>
    </div>
@endif

@if ($selecionados > 0 && ! $this->verLixeira)
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
                <x-shared.select-search
                    name="perfilEmMassa"
                    :options="array_merge([['value' => '', 'label' => 'Atribuir perfil...']], $this->perfisAtribuiveis)"
                    :placeholder="null"
                    wire:model="perfilEmMassa"
                />
            </div>

            <x-shared.button variant="primary" size="sm" wire:click="solicitarAtribuirPerfilEmMassa">
                Aplicar perfil
            </x-shared.button>

            <x-shared.button
                variant="success"
                appearance="soft"
                size="sm"
                wire:click="solicitarAlternarStatusEmMassa(true)"
            >
                Reativar
            </x-shared.button>

            <x-shared.button
                variant="warning"
                appearance="soft"
                size="sm"
                wire:click="solicitarAlternarStatusEmMassa(false)"
            >
                Desativar
            </x-shared.button>

            <x-shared.button variant="default" appearance="ghost" size="sm" wire:click="limparSelecao">
                Limpar
            </x-shared.button>
        </div>
    </div>
@endif
