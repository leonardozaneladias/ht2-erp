@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.toggle',
    'description' => 'Switch semântico para estados booleanos do sistema, distinto de checkbox de seleção múltipla.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Estados booleanos" subtitle="Ativação simples com hint opcional">
            <div class="space-y-4">
                <x-shared.toggle name="ativo" label="Registro ativo" checked />
                <x-shared.toggle
                    name="exige_responsavel"
                    label="Exige responsável de cadastro"
                    hint="Ative para forçar responsável em registros com usuários menores de idade."
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Preferências e regras do registro">
            <div class="border-default-300 bg-light/40 space-y-4 rounded-2xl border p-4">
                <x-shared.toggle name="envia_lembrete" label="Enviar lembrete de vencimento" checked />
                <x-shared.toggle name="ajustar_fim_mes" label="Ajustar vencimento para fim do mês" checked />
                <x-shared.toggle name="exibir_portal" label="Disponibilizar produto no portal" />
            </div>
        </x-shared.card>
    </div>
    <x-shared.card
        class="mt-6"
        title="Modo stacked (em formulários)"
        subtitle="Rótulo no topo e altura alinhada aos inputs do grid (use o prop stacked)"
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-shared.input name="demo_nome" label="Nome" placeholder="Ex.: Acme Ltda." />
            <x-shared.toggle
                name="demo_ativo"
                label="Registro ativo"
                stacked
                onText="Ativo"
                offText="Inativo"
                checked
            />
        </div>
    </x-shared.card>
@endsection
