<div class="mb-3 flex justify-end">
    <x-shared.button
        variant="default"
        appearance="outline"
        size="sm"
        icon="tabler--file-type-pdf"
        wire:click="exportarPdf"
        wire:loading.attr="disabled"
        wire:target="exportarPdf"
    >
        Exportar PDF
    </x-shared.button>
</div>
