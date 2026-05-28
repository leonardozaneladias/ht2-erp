@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.sortable-list',
    'description' => 'Lista reordenável com SortableJS para termos do produto e outros fluxos administrativos com drag-and-drop.',
])

@php
    $terms = [
        ['id' => 101, 'nome' => 'Termo Geral', 'versao' => '3.2', 'tipo' => 'Obrigatório'],
        ['id' => 102, 'nome' => 'Termo de Pagamento', 'versao' => '1.8', 'tipo' => 'Financeiro'],
        ['id' => 103, 'nome' => 'Política de Privacidade', 'versao' => '2.4', 'tipo' => 'Legal'],
        ['id' => 104, 'nome' => 'Autorização de Imagem', 'versao' => '1.1', 'tipo' => 'Mídia'],
    ];
@endphp

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <x-shared.card title="Termos vinculados" subtitle="Caso real do módulo de produtos">
            <x-admin.sortable-list id="preview-termos">
                @foreach ($terms as $term)
                    <div
                        data-id="{{ $term['id'] }}"
                        class="border-default-300 bg-card flex items-center gap-3 border-b px-4 py-3 last:border-b-0"
                    >
                        <i class="iconify tabler--grip-vertical drag-handle text-default-400 cursor-grab text-xl"></i>

                        <div class="min-w-0 grow">
                            <p class="text-body-color truncate text-sm font-semibold">{{ $term['nome'] }}</p>
                            <p class="text-default-400 mt-1 text-xs">v{{ $term['versao'] }} • {{ $term['tipo'] }}</p>
                        </div>

                        <x-shared.badge variant="default">{{ $loop->iteration }}</x-shared.badge>
                    </div>
                @endforeach
            </x-admin.sortable-list>
        </x-shared.card>

        <x-shared.card title="Estado atual" subtitle="Preview com evento local de reordenação">
            <div class="space-y-4 text-sm">
                <p class="text-default-400">O helper dispara o evento <code>sortable:changed</code> com os IDs na nova ordem. Em tela real, se existir <code>target</code>, ele chama o método Livewire automaticamente.</p>

                <div class="border-default-300 bg-light/40 rounded-xl border p-4">
                    <p class="text-2xs text-default-400 font-semibold tracking-[0.22em] uppercase">Ordem atual</p>
                    <pre id="sortable-order-output" class="text-body-color mt-3 text-sm whitespace-pre-wrap">
[101, 102, 103, 104]</pre
                    >
                </div>

                <div class="border-default-300 bg-light/40 text-default-400 rounded-xl border p-4">
                    Arraste pelo ícone lateral para testar a interação. No módulo real, esse fluxo persiste
                    <code>produto_termos.ordem</code>.
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection

@section ('previewScripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sortable = document.getElementById('preview-termos');
            const output = document.getElementById('sortable-order-output');

            if (!sortable || !output) {
                return;
            }

            sortable.addEventListener('sortable:changed', (event) => {
                output.textContent = JSON.stringify(event.detail.ids, null, 2);
            });
        });
    </script>
@endsection
