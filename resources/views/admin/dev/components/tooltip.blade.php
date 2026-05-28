@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.tooltip',
    'description' => 'Tooltip textual simples para ícones de ajuda, badges e ações compactas do admin.',
])

@section ('preview')
    <div class="grid gap-6 md:grid-cols-2">
        <x-shared.card title="Placements" subtitle="Top, right, bottom e left">
            <div class="grid place-items-center gap-10 py-8">
                <div class="flex items-center gap-4">
                    <x-shared.tooltip content="Contrato vigente" placement="top">
                        <x-shared.badge variant="success">ATIVO</x-shared.badge>
                    </x-shared.tooltip>

                    <x-shared.tooltip content="Quando ativo, exige responsável adicional." placement="right">
                        <span
                            class="bg-light text-body-color inline-flex cursor-help items-center gap-2 rounded-full px-3 py-2 text-sm"
                        >
                            <i class="iconify tabler--info-circle text-primary text-base"></i>
                            Responsável
                        </span>
                    </x-shared.tooltip>
                </div>

                <div class="flex items-center gap-4">
                    <x-shared.tooltip content="Reemitir boleto" placement="bottom">
                        <button type="button" class="btn btn-icon bg-light text-dark hover:text-primary">
                            <i class="iconify tabler--receipt-2 text-base"></i>
                        </button>
                    </x-shared.tooltip>

                    <x-shared.tooltip content="Última sincronização há 12 minutos" placement="left">
                        <button type="button" class="btn btn-icon bg-light text-dark hover:text-primary">
                            <i class="iconify tabler--refresh text-base"></i>
                        </button>
                    </x-shared.tooltip>
                </div>
            </div>
        </x-shared.card>

        <x-shared.card title="Uso real no domínio" subtitle="Ajuda contextual ao lado de toggles e labels técnicas">
            <div class="space-y-5">
                <div class="flex items-center gap-2">
                    <x-shared.toggle name="tooltip_toggle_a" label="Exige responsável financeiro" checked />
                    <x-shared.tooltip
                        placement="right"
                        content="Quando ativo, o fluxo exige um responsável financeiro com CPF, telefone e vínculo de pagamento."
                    >
                        <i class="iconify tabler--help-circle text-default-400 cursor-help text-lg"></i>
                    </x-shared.tooltip>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-body-color text-sm font-medium">Margem de dias para reajuste</span>
                    <x-shared.tooltip
                        content="Usar somente quando o índice puder ser aplicado com tolerância operacional sem mudar a regra fiscal."
                    >
                        <i class="iconify tabler--info-circle text-primary cursor-help text-lg"></i>
                    </x-shared.tooltip>
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
