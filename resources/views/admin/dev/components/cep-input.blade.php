@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.cep-input',
    'description' => 'CEP com máscara BR e busca ViaCEP disparada no blur, sem depender de Alpine implícito.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="CEP com evento" subtitle="Busca ViaCEP e dispatch `cep-filled`">
            <div class="space-y-4">
                <x-shared.cep-input
                    name="cep"
                    label="CEP"
                    hint="Ao sair do campo, o helper consulta o ViaCEP e emite o evento oficial."
                />

                <x-shared.alert variant="info" title="Integração esperada">
                    Em formulários Livewire, você pode escutar o evento <code>cep-filled</code> ou usar a prop
                    <code>target</code> para chamar um método do componente.
                </x-shared.alert>
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Instituição e endereço do formando">
            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                <x-shared.cep-input
                    name="cep_sede"
                    label="CEP da sede"
                    target="preencherEnderecoSede"
                    hint="Exemplo de uso com método Livewire dedicado."
                />
            </div>
        </x-shared.card>
    </div>
@endsection

@section ('previewScripts')
    <script>
        document.addEventListener('cep-filled', (event) => {
            window.dispatchEvent(
                new CustomEvent('toast', {
                    detail: {
                        variant: 'info',
                        message: `CEP resolvido: ${event.detail.logradouro || 'logradouro não informado'} • ${event.detail.cidade || 'cidade não informada'}`,
                    },
                }),
            );
        });
    </script>
@endsection
