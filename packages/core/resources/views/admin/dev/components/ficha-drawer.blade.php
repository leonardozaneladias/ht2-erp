@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-admin.ficha-drawer',
    'description' => 'Drawer da ficha de visualização (Ver) dos CRUDs — composição wide + blur sobre o x-admin.drawer.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Demonstração" subtitle="Shell do drawer (estado vazio — sem registro)">
            <p class="text-default-500 mb-4 text-sm">Em produção o drawer é alimentado pelo trait <code class="text-xs">ComFicha</code> no Index e abre pelo evento <code class="text-xs">ficha-abrir</code> após autorizar e carregar o registro. @if (Route::has('admin.exemplos.index'))Referência viva: <a href="{{ route('admin.exemplos.index') }}" class="text-primary hover:underline">módulo Exemplo</a> (kebab → Ver).@endif</p>
            <x-shared.button type="button" data-hs-overlay="#drawer-ficha">Abrir o drawer</x-shared.button>
        </x-shared.card>

        <x-shared.card title="Uso" subtitle="Fluxo completo em docs/visualizacao.md">
            <pre
                class="bg-light text-body-color overflow-x-auto rounded-lg p-4 text-xs"
            ><code>&lt;x-admin.ficha-drawer :registro="$this-&gt;ficha" :titulo="$this-&gt;fichaTitulo" :editar-url="$this-&gt;fichaUrlEditar"&gt;
    @verbatim@if ($this->ficha)
        @include('exemplo-demo::exemplos._ficha', ['registro' => $this->ficha])
    @endif@endverbatim
&lt;/x-admin.ficha-drawer&gt;</code></pre>
        </x-shared.card>
    </div>
    <x-admin.ficha-drawer titulo="Detalhes do registro" />
@endsection
