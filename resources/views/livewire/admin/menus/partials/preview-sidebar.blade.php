{{-- Espelho em miniatura da sidebar (estrutura efetiva, sem filtro de ACL).
     Não reusa x-admin.sidebar (auth/branding/simplebar). --}}
<div class="sticky top-20">
    <div class="bg-dark rounded-xl p-4 shadow-sm">
        <p class="text-2xs font-semibold tracking-[0.22em] text-white/40 uppercase">Pré-visualização</p>

        @forelse ($this->previewSidebar as $secao)
            <p
                wire:key="preview-secao-{{ $secao['key'] }}"
                class="text-2xs mt-4 font-semibold tracking-wider text-white/40 uppercase"
            >
                {{ $secao['title'] }}
            </p>
            <ul class="mt-1 space-y-0.5">
                @foreach ($secao['items'] as $item)
                    <li wire:key="preview-item-{{ $item['key'] }}">
                        <div class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-white/80">
                            <i class="iconify {{ $item['icon'] }} shrink-0 text-base text-white/60"></i>
                            <span class="truncate">{{ $item['label'] }}</span>

                            @if ($item['children'] !== [])
                                <i class="iconify tabler--chevron-down ms-auto shrink-0 text-xs text-white/40"></i>
                            @endif
                        </div>

                        @if ($item['children'] !== [])
                            <ul class="ms-5 space-y-0.5 border-s border-white/10 ps-3">
                                @foreach ($item['children'] as $filho)
                                    <li
                                        wire:key="preview-filho-{{ $filho['key'] }}"
                                        class="truncate px-2 py-1 text-xs text-white/60"
                                    >
                                        {{ $filho['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @empty
            <p class="mt-4 text-sm text-white/60">Nenhum item ativo no menu.</p>
        @endforelse

        <p class="mt-5 border-t border-white/10 pt-3 text-xs text-white/40">Itens inativos ficam de fora. A visibilidade real de cada usuário depende do ACL.</p>
    </div>
</div>
