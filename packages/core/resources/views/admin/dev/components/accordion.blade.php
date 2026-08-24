@extends ('admin.dev.components.shell', [
    'title' => 'Preview • família x-shared.accordion',
    'description' => 'Composição vertical para formulários longos, FAQs internas e seções do cadastro manual do admin.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Accordion padrão" subtitle="Fluxo parecido com o cadastro manual de usuário">
            <x-shared.accordion>
                <x-shared.accordion-item
                    id="cadastro-registro"
                    title="1. Registro, plano e período"
                    icon="tabler--file-text"
                    open
                >
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-shared.select
                            name="plano_preview"
                            label="Plano"
                            :options="['pro-2027' => 'Pro 2027', 'std-2026' => 'Standard 2026']"
                            selected="pro-2027"
                        />
                        <x-shared.select
                            name="categoria_preview"
                            label="Categoria"
                            :options="['com' => 'Comercial', 'sup' => 'Suporte']"
                            selected="com"
                        />
                        <x-shared.select
                            name="periodo_preview"
                            label="Período"
                            :options="['2026-2' => '2026.2', '2027-1' => '2027.1']"
                            selected="2027-1"
                        />
                    </div>
                </x-shared.accordion-item>

                <x-shared.accordion-item id="cadastro-dados" title="2. Dados do usuário" icon="tabler--user">
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
                    O vínculo do portal é liberado automaticamente depois da confirmação do cadastro e geração do acesso
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
