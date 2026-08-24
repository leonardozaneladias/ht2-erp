@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.cnpj-input',
    'description' => 'Variação oficial de input mascarado para CNPJ, seguindo a mesma base do CPF.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Instituições" subtitle="Campo obrigatório no backoffice">
            <div class="space-y-4">
                <x-shared.cnpj-input name="cnpj" label="CNPJ" value="12345678000190" required />
                <x-shared.cnpj-input name="cnpj_secundario" label="CNPJ secundário" value="99888777000166" />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Configurações globais">
            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                <x-shared.cnpj-input
                    name="cnpj_empresa"
                    label="CNPJ da empresa"
                    hint="Compartilha a mesma base de acessibilidade e erro do x-shared.input."
                />
            </div>
        </x-shared.card>
    </div>
@endsection
