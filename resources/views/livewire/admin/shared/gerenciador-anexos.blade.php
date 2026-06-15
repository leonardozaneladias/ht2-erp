<div class="space-y-4">
    <div>
        <h3 class="mb-2 text-sm font-medium text-gray-700">Anexos</h3>

        <div class="flex items-center gap-2">
            <input
                type="file"
                wire:model="arquivo"
                class="block w-full text-sm text-gray-500 file:mr-4 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-sm file:text-blue-700 hover:file:bg-blue-100"
            />
            <button
                wire:click="uploadAnexo"
                wire:loading.attr="disabled"
                class="btn btn-primary btn-sm whitespace-nowrap"
            >
                <span wire:loading.remove wire:target="uploadAnexo">Enviar</span>
                <span wire:loading wire:target="uploadAnexo">Enviando...</span>
            </button>
        </div>

        @error ('arquivo')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    @if (count($anexos) > 0)
        <ul class="divide-y divide-gray-100 rounded border">
            @foreach ($anexos as $anexo)
                <li class="flex items-center justify-between px-3 py-2 text-sm">
                    <div class="flex min-w-0 items-center gap-2">
                        <i class="fas fa-paperclip flex-shrink-0 text-gray-400"></i>
                        <a href="{{ $anexo['url'] }}" target="_blank" class="truncate text-blue-600 hover:underline">
                            {{ $anexo['nome_original'] }}
                        </a>
                        <span class="flex-shrink-0 text-xs text-gray-400">{{ $anexo['tamanho_formatado'] }}</span>
                    </div>
                    <button
                        wire:click="solicitarRemoverAnexo({{ $anexo['id'] }})"
                        class="ml-2 flex-shrink-0 text-red-500 hover:text-red-700"
                        title="Remover"
                    >
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-400">Nenhum anexo adicionado.</p>
    @endif
</div>
