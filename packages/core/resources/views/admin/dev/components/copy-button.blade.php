@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.copy-button',
    'description' => 'Botão nativo de copiar para CPF, códigos e links públicos, com feedback visual e toast.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-shared.card title="Variações" subtitle="Ícone simples, com label e em tom primário">
            <div class="flex flex-wrap items-center gap-3">
                <x-shared.copy-button value="AF-2026-001" />
                <x-shared.copy-button value="AF-2026-001" label="Copiar código" />
                <x-shared.copy-button
                    value="https://sistema.exemplo.com.br/adesao/af-2026-001"
                    label="Copiar link"
                    variant="primary"
                    appearance="solid"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Casos reais" subtitle="CPF, e-mail e link de adesão">
            <div class="space-y-4">
                <div
                    class="border-default-300 bg-light/40 flex items-center justify-between gap-3 rounded-xl border px-4 py-3"
                >
                    <div>
                        <p class="text-2xs text-default-400 font-semibold tracking-[0.2em] uppercase">CPF</p>
                        <p class="text-body-color font-mono">123.456.789-00</p>
                    </div>
                    <x-shared.copy-button value="123.456.789-00" />
                </div>

                <div
                    class="border-default-300 bg-light/40 flex items-center justify-between gap-3 rounded-xl border px-4 py-3"
                >
                    <div>
                        <p class="text-2xs text-default-400 font-semibold tracking-[0.2em] uppercase">E-mail</p>
                        <p class="text-body-color">operacoes@exemplo.com.br</p>
                    </div>
                    <x-shared.copy-button value="operacoes@exemplo.com.br" label="Copiar" />
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
