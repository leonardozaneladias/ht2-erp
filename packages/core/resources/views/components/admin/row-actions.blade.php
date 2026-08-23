@props ([
    // Rotulo acessivel do botao gatilho (kebab).
    'label' => 'Ações',
])

{{--
    Menu de acoes de linha (kebab "⋮"). Padrao UNICO para acoes por registro em
    tabelas (PowerGrid e nativas). Agrupa todas as acoes num menu, evitando o
    acumulo de botoes soltos.

    Robustez (ver resources/js/admin/row-actions.js): usa Alpine + posicao fixed
    para sobreviver aos morphs do PowerGrid e escapar do overflow da tabela.

    Se o slot vier vazio (ex.: usuario sem permissao para nenhuma acao), nada e
    renderizado — evita um kebab "fantasma" sem itens.

    NAO escrever exemplos com a tag literal "x-admin.row-actions" (com angle
    bracket) aqui dentro: o Blade compila componentes ANTES de remover
    comentarios, e a auto-referencia causaria recursao infinita. Exemplo de uso
    real em resources/views/livewire/admin/usuarios/_acoes.blade.php e no
    showcase /admin/dev/components/row-actions.
--}}

@if (trim($slot) !== '')
    <div
        x-data="afRowActions"
        @keydown.escape.window="fechar();"
        @scroll.window.capture="open && fechar();"
        @resize.window="open && fechar();"
        class="inline-flex"
    >
        <button
            type="button"
            x-ref="trigger"
            @click="toggle();"
            @keydown.down.prevent="abrir();"
            @keydown.up.prevent="abrir();"
            class="btn btn-icon btn-sm text-default-500 hover:bg-light hover:text-default-700 rounded-full transition-colors"
            aria-haspopup="menu"
            :aria-expanded="open ? 'true' : 'false'"
            aria-label="{{ $label }}"
        >
            <i class="iconify tabler--dots-vertical text-lg" aria-hidden="true"></i>
        </button>

        <div
            x-show="open"
            x-cloak
            x-ref="menu"
            tabindex="-1"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click.outside="fechar();"
            @click="fechar();"
            @keydown="aoTeclar($event);"
            :style="menuStyle"
            class="bg-card border-default-300 fixed z-[1080] min-w-52 rounded border py-1 shadow-lg motion-reduce:transition-none"
            role="menu"
            aria-orientation="vertical"
            {{-- Nome acessivel do menu (WAI-ARIA Menu Button Pattern): sem ele o
                 leitor de tela anuncia apenas "menu" ao abrir. Usa a mesma label do
                 gatilho (aria-label, nao aria-labelledby, para nao exigir id unico
                 por instancia — ha um row-actions por linha de tabela). --}}
            aria-label="{{ $label }}"
        >
            {{ $slot }}
        </div>
    </div>
@endif
