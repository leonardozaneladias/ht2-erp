@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.cpf-input',
    'description' => 'Wrapper de input com máscara brasileira de CPF sobre o helper oficial de Inputmask.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Casos comuns" subtitle="Formandos e responsáveis">
            <div class="space-y-4">
                <x-shared.cpf-input name="cpf_formando" label="CPF do formando" value="12345678901" required />
                <x-shared.cpf-input name="cpf_responsavel" label="CPF do responsável" value="98765432100" />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Cadastro manual">
            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                <x-shared.cpf-input
                    name="cpf_busca"
                    label="Buscar por CPF"
                    hint="Pode ser reaproveitado em filtros rápidos e formulários completos."
                />
            </div>
        </x-shared.card>
    </div>
@endsection
