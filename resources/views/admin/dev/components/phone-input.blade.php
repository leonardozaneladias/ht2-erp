@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.phone-input',
    'description' => 'Telefone BR com máscara adaptativa para fixo e celular, usando o helper oficial de Inputmask.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Formatos BR" subtitle="Fixo e celular no mesmo componente">
            <div class="space-y-4">
                <x-shared.phone-input name="telefone_fixo" label="Telefone fixo" value="1633214455" />
                <x-shared.phone-input name="telefone_celular" label="Celular" value="16998877665" />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Empresa e usuário">
            <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                <x-shared.phone-input
                    name="telefone_contato"
                    label="Telefone de contato"
                    hint="A máscara escolhe automaticamente entre 10 e 11 dígitos."
                />
            </div>
        </x-shared.card>
    </div>
@endsection
