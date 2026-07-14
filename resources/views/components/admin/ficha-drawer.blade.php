@props ([
    'registro' => null,
    'titulo' => null,
    'editarUrl' => null,
    'id' => 'drawer-ficha',
])

@php
    $ator = auth('admin')->user();
    $naLixeira = $registro !== null && method_exists($registro, 'trashed') && $registro->trashed();
    $podeEditar = $registro !== null
        && ! $naLixeira
        && filled($editarUrl)
        && ($ator?->can('update', $registro) ?? false);
@endphp

{{-- Drawer da ficha de visualização ("Ver") — consome ComFicha no Index: o
     evento browser `ficha-abrir` (disparado após autorizar+carregar) abre o
     overlay. Um por página (o Index é full-page). Ver docs/visualizacao.md. --}}
<div x-data x-on:ficha-abrir.window="window.HSOverlay?.open(document.querySelector('#{{ $id }}'))">
    <x-admin.drawer :id="$id" :title="$titulo ?? 'Detalhes do registro'" size="wide" :blur="true">
        @if ($registro !== null)
            @if ($naLixeira)
                <x-shared.badge variant="warning" pill class="mb-4">Na lixeira</x-shared.badge>
            @endif
            {{ $slot }}
        @else
            <p class="text-default-400 text-sm">Escolha um registro na listagem para ver os detalhes.</p>
        @endif

        <x-slot:footer>
            <div class="flex items-center justify-between gap-4">
                <p class="text-default-400 min-w-0 truncate text-xs">
                    @if ($registro?->created_at)
                        Criado em {{ $registro->created_at->format('d/m/Y H:i') }}
                        @if ($registro->updated_at && ! $registro->updated_at->equalTo($registro->created_at))
                            · atualizado em {{ $registro->updated_at->format('d/m/Y H:i') }}
                        @endif
                    @endif
                </p>
                <div class="flex shrink-0 gap-2">
                    <x-shared.button type="button" variant="default" appearance="outline" data-hs-overlay="#{{ $id }}">
                        Fechar
                    </x-shared.button>
                    @if ($podeEditar)
                        <x-shared.button :href="$editarUrl" wire:navigate variant="primary" icon="tabler--edit">
                            Editar
                        </x-shared.button>
                    @endif
                </div>
            </div>
        </x-slot:footer>
    </x-admin.drawer>
</div>
