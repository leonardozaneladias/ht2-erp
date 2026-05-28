@extends ('admin.dev.components.shell', [
    'title' => 'Preview • família x-shared.accordion',
    'description' => 'Composição vertical para formulários longos, FAQs internas e seções do cadastro manual do admin.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Accordion padrão" subtitle="Fluxo parecido com o cadastro manual de formando">
            <x-shared.accordion>
                <x-shared.accordion-item
                    id="cadastro-contrato"
                    title="1. Contrato, curso e período"
                    icon="tabler--file-text"
                    open
                >
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-shared.select
                            name="contrato_preview"
                            label="Contrato"
                            :options="['med-2027' => 'Medicina 2027', 'odt-2026' => 'Odontologia 2026']"
                            selected="med-2027"
                        />
                        <x-shared.select
                            name="curso_preview"
                            label="Curso"
                            :options="['med' => 'Medicina', 'odt' => 'Odontologia']"
                            selected="med"
                        />
                        <x-shared.select
                            name="periodo_preview"
                            label="Período"
                            :options="['2026-2' => '2026.2', '2027-1' => '2027.1']"
                            selected="2027-1"
                        />
                    </div>
                </x-shared.accordion-item>

                <x-shared.accordion-item id="cadastro-dados" title="2. Dados do formando" icon="tabler--user">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-shared.input name="nome_preview" label="Nome completo" value="Ana Luiza Prado" />
                        <x-shared.cpf-input name="cpf_preview" label="CPF" value="12345678900" />
                    </div>
                </x-shared.accordion-item>

                <x-shared.accordion-item
                    id="cadastro-pagamento"
                    title="3. Forma de pagamento"
                    icon="tabler--credit-card"
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-shared.select-search
                            name="modalidade_preview"
                            label="Modalidade"
                            :options="['pix' => 'PIX', 'boleto' => 'Boleto', 'cartao' => 'Cartão']"
                            selected="pix"
                        />
                        <x-shared.money-input name="valor_preview" label="Entrada" value="35000" />
                    </div>
                </x-shared.accordion-item>
            </x-shared.accordion>
        </x-shared.card>

        <x-shared.card title="Flush mode" subtitle="Quando o card já fornece a borda externa">
            <x-shared.accordion flush class="border-default-300 bg-card rounded-xl border">
                <x-shared.accordion-item id="faq-1" title="Como liberar o portal?" open>
                    O vínculo do portal é liberado automaticamente depois da confirmação de contrato e geração do acesso
                    inicial.
                </x-shared.accordion-item>
                <x-shared.accordion-item id="faq-2" title="Como reabrir uma seção bloqueada?">
                    Use o padrão do cadastro manual: a validação da tela pode reabrir a seção com erro via JS/Livewire
                    na integração da página.
                </x-shared.accordion-item>
            </x-shared.accordion>
        </x-shared.card>
    </div>
@endsection
