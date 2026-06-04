@props ([
    'count' => 0,
    'save' => 'salvar',
    'discard' => 'descartar',
])

@if ($count > 0)
    <div class="border-default-200 bg-default-50 flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3">
        <span class="text-sm">
            <span class="iconify tabler--alert-circle text-warning me-1 size-4 align-middle"></span>
            {{ $count }} {{ $count === 1 ? 'alteração não salva' : 'alterações não salvas' }}
        </span>
        <div class="flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="{{ $discard }}">
                Descartar
            </button>
            <button
                type="button"
                class="btn btn-primary btn-sm"
                wire:click="{{ $save }}"
                wire:loading.attr="disabled"
                wire:target="{{ $save }}"
            >
                <span wire:loading.remove wire:target="{{ $save }}">Salvar</span>
                <span wire:loading wire:target="{{ $save }}">Salvando...</span>
            </button>
        </div>
    </div>
@endif
