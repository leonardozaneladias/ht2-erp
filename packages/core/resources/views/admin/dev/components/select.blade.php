@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.select',
    'description' => 'Select nativo para listas curtas, enums simples e filtros rápidos com melhor comportamento mobile.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Options array" subtitle="Placeholder, valor selecionado e opções desabilitadas">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.select
                    name="uf"
                    label="UF"
                    :options="['SP' => 'São Paulo', 'RJ' => 'Rio de Janeiro', 'MG' => 'Minas Gerais']"
                    value="SP"
                    required
                />

                <x-shared.select
                    name="modalidade"
                    label="Modalidade"
                    :options="[
                        ['value' => 'boleto', 'label' => 'Boleto Bancário'],
                        ['value' => 'pix', 'label' => 'PIX'],
                        ['value' => 'cartao', 'label' => 'Cartão', 'disabled' => true],
                    ]"
                    hint="Cartão permanece bloqueado até a homologação do gateway."
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Filtros financeiros">
            <div class="space-y-4">
                <x-shared.select name="status_parcela" label="Status da parcela" value="pendente">
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                    <option value="vencido">Vencido</option>
                    <option value="cancelado">Cancelado</option>
                </x-shared.select>

                <x-shared.select
                    name="agrupamento_relatorio"
                    label="Agrupar por"
                    :options="['registro' => 'Registro', 'categoria' => 'Categoria', 'equipe' => 'Equipe']"
                    value="equipe"
                    hint="Listas longas devem migrar para x-shared.select-search no Batch 5."
                />
            </div>
        </x-shared.card>
    </div>
@endsection
