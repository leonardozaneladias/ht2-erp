@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.radio',
    'description' => 'Escolha exclusiva entre poucas opções, com x-shared.radio-group como subcomponente oficial para label e validação.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Grupo inline" subtitle="Escolha binária ou com poucas opções">
            <x-shared.radio-group
                name="status"
                label="Status"
                inline
                hint="Para muitas opções, prefira x-shared.select."
            >
                <x-shared.radio name="status" value="ativo" label="Ativo" checked />
                <x-shared.radio name="status" value="inativo" label="Inativo" />
                <x-shared.radio name="status" value="arquivado" label="Arquivado" />
            </x-shared.radio-group>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Modalidade de pagamento">
            <x-shared.radio-group name="modalidade" label="Modalidade preferencial">
                <x-shared.radio
                    name="modalidade"
                    value="boleto"
                    label="Boleto Bancário"
                    description="Vencimento mensal e conciliação operacional padrão."
                    checked
                />
                <x-shared.radio
                    name="modalidade"
                    value="pix"
                    label="PIX"
                    description="Pagamento instantâneo com confirmação mais rápida para baixa."
                />
                <x-shared.radio
                    name="modalidade"
                    value="cartao"
                    label="Cartão de Crédito"
                    description="Opção visual para quando o gateway estiver habilitado no contrato."
                />
            </x-shared.radio-group>
        </x-shared.card>
    </div>
@endsection
