@php
    /** Toggle "Ver lixeira / Ver ativos" — roda com $this = componente PowerGrid. */
    $ator = auth('admin')->user();
    $podeVerLixeira = $ator?->can('empresas.restaurar') ?? false;
@endphp

<div class="flex flex-wrap items-center justify-between gap-2">
    <div>
        @if ($podeVerLixeira)
            <x-shared.button
                :variant="$this->verLixeira ? 'primary' : 'default'"
                appearance="outline"
                size="sm"
                :icon="$this->verLixeira ? 'tabler--arrow-back-up' : 'tabler--trash'"
                wire:click="alternarLixeira"
            >
                {{ $this->verLixeira ? 'Ver ativos' : 'Ver lixeira' }}
            </x-shared.button>
        @endif
    </div>

    @include ('livewire.admin.empresas._export-pdf')
</div>
