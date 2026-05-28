@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.money-input',
    'description' => 'Input monetário BRL com Inputmask e foco em persistência segura em centavos.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Variações principais" subtitle="Valores financeiros do backoffice">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.money-input name="valor_produto" label="Valor do produto" value="R$ 1.590,00" required />
                <x-shared.money-input name="valor_minimo" label="Valor mínimo da parcela" value="R$ 120,50" />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Programação e descontos">
            <div class="border-default-300 bg-light/40 space-y-4 rounded-2xl border p-4">
                <x-shared.money-input
                    name="programacao_valor"
                    label="Valor da programação"
                    hint="Persistir sempre em centavos via MoneyHelper::toCents()."
                    value="R$ 2.499,90"
                />

                <x-shared.money-input name="desconto_fixo" label="Desconto fixo" value="R$ 150,00" />
            </div>
        </x-shared.card>
    </div>
@endsection
