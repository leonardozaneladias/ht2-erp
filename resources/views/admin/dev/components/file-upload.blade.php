@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.file-upload',
    'description' => 'Upload oficial com dois modos: livewire simples e dropzone rico, no mesmo componente.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Modo livewire" subtitle="Padrão do projeto para uploads simples">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-shared.file-upload
                    name="logo"
                    label="Logo da instituição"
                    accept="image/png,image/jpeg"
                    preview="https://placehold.co/320x180/e2e8f0/334155?text=Logo+Atual"
                    hint="Modo padrão para logo, avatar e imagem única com Livewire."
                />

                <div class="border-default-300 bg-light/40 text-default-400 rounded-2xl border p-4 text-sm">
                    O componente mantém a UX simples como padrão, mas preserva preview atual e seleção por clique.
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Modo dropzone" subtitle="Quando a experiência de drag-and-drop realmente fizer sentido">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-shared.file-upload
                    name="anexos"
                    label="Documentos anexados"
                    mode="dropzone"
                    accept="image/*,application/pdf"
                    :max-size="4"
                    multiple
                    hint="Sem upload automático nesta preview; o foco aqui é validar a moldura rica."
                />

                <div class="border-default-300 bg-light/40 text-default-400 rounded-2xl border p-4 text-sm">
                    No uso real, <code>mode=&quot;dropzone&quot;</code> pode receber <code>upload-url</code> para
                    integração endpoint-based.
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
