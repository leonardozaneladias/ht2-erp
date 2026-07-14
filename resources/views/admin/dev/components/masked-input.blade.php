@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.masked-input',
    'description' =>
        'Base de todo campo mascarado. cpf/cnpj/phone/pis/cid são wrappers finos dela — a máscara é o único parâmetro que muda.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-shared.card title="A base" subtitle="Um formato pontual não precisa de componente novo">
            <div class="space-y-4">
                <x-shared.masked-input
                    name="placa"
                    label="Placa (Mercosul)"
                    mask="AAA-9A99"
                    :uppercase="true"
                    placeholder="ABC-1D23"
                    hint="mask + uppercase — sem criar componente."
                />

                <x-shared.masked-input
                    name="processo"
                    label="Número de processo"
                    mask="9999999-99.9999.9.99.9999"
                    hint="Formato CNJ. Existe só nesta tela — é exatamente o caso da base."
                />

                <x-shared.alert variant="info" title="Por que a base existe">
                    O §15.4 proíbe <code>data-af-inputmask</code> fora de <code>components/shared/</code>. Sem uma base
                    genérica, cada formato novo exigia um componente novo — e quem tinha pressa colava a máscara inline
                    na view. Foi o que aconteceu com o PIS no cadastro de funcionário.
                </x-shared.alert>
            </div>
        </x-shared.card>

        <x-shared.card title="Os wrappers" subtitle="Documentos brasileiros, com dígito verificador no cliente">
            <div class="space-y-4">
                <x-shared.cpf-input name="cpf_demo" />
                <x-shared.pis-input name="pis_demo" />
                <x-shared.cid-input name="cid_demo" label="CID (CID-10)" hint="Ex.: J11.1 — vai para o eSocial." />

                <x-shared.alert variant="warning" title="Dígito verificador">
                    CPF, CNPJ e PIS marcam <code>data-af-validate</code>. Ao salvar, o pré-flight barra um documento com
                    DV impossível <strong>sem gastar um round-trip</strong>. As Rules do PHP continuam sendo a
                    autoridade — o fixture <code>tests/Fixtures/documentos-dv.json</code> roda contra as duas
                    implementações.
                </x-shared.alert>
            </div>
        </x-shared.card>
    </div>
@endsection
