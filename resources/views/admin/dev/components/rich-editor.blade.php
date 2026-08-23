@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.rich-editor',
    'description' => 'Editor rich text (Quill, toolbar mínima). HTML mantido em textarea oculto; sanitizado no servidor antes de exibir.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Editor" subtitle="Negrito, itálico, sublinhado, listas e links">
            <x-shared.rich-editor
                name="exemplo"
                label="Mensagem"
                :value="'<p>Olá, <strong>time</strong>! Este é um <em>comunicado</em> de exemplo.</p><ul><li>Item um</li><li>Item dois</li></ul>'"
                hint="O conteúdo é sanitizado no servidor (HT2ML\Core\Support\Html\HtmlSanitizer) antes de persistir e exibir."
            />
        </x-shared.card>

        <x-shared.card title="Uso" subtitle="Binda via wire:model e sanitiza no backend">
            <pre
                class="bg-light/50 text-body-color overflow-x-auto rounded-lg p-4 text-xs leading-relaxed"
            ><code>&lt;x-shared.rich-editor
    name="mensagem"
    label="Mensagem"
    wire:model="mensagem"
    required
/&gt;</code></pre>
            <ul class="text-default-500 mt-3 space-y-2 text-sm">
                <li class="flex gap-2">
                    <i class="iconify tabler--shield-check text-success mt-0.5 shrink-0"></i>
                    <span
                        >Sempre sanitize o HTML no servidor com
                        <code class="text-body-color">HtmlSanitizer::clean()</code> antes de gravar e ao exibir.</span
                    >
                </li>
                <li class="flex gap-2">
                    <i class="iconify tabler--ruler text-info mt-0.5 shrink-0"></i>
                    <span
                        >Meça obrigatoriedade e limite de tamanho pelo texto puro (<code class="text-body-color"
                            >strip_tags</code
                        >).</span
                    >
                </li>
            </ul>
        </x-shared.card>
    </div>
@endsection
