@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.status-badge',
    'description' => 'Wrapper enum-driven para badges de status, adimplência e modalidade.',
])

@section ('preview')
    @php
        $makeEnum = fn (string $value, string $label, string $color, ?string $icon = null) => new class($value, $label, $color, $icon)
        {
            public function __construct(
                public string $value,
                protected string $labelText,
                protected string $colorName,
                protected ?string $iconName,
            ) {
            }

            public function label(): string
            {
                return $this->labelText;
            }

            public function color(): string
            {
                return $this->colorName;
            }

            public function icon(): ?string
            {
                return $this->iconName;
            }
        };

        $examples = [
            $makeEnum('ativo', 'Ativo', 'success', 'tabler--circle-check'),
            $makeEnum('inadimplente', 'Inadimplente', 'danger', 'tabler--alert-octagon'),
            $makeEnum('pendente', 'Pendente', 'warning', 'tabler--clock'),
            $makeEnum('boleto', 'Boleto', 'info', 'tabler--receipt-2'),
            $makeEnum('arquivado', 'Arquivado', 'default', 'tabler--archive'),
        ];
    @endphp
    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <x-shared.card title="Matriz de estados" subtitle="soft, solid e com ícone">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($examples as $example)
                    <div class="border-default-300 bg-light/40 rounded-2xl border p-4">
                        <p class="text-body-color mb-3 text-sm font-medium">{{ $example->label() }}</p>

                        <div class="flex flex-wrap gap-3">
                            <x-shared.status-badge :enum="$example" />
                            <x-shared.status-badge :enum="$example" with-icon />
                            <x-shared.status-badge :enum="$example" with-icon solid />
                            <x-shared.status-badge :enum="$example" with-icon size="lg" />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Resumo financeiro do formando">
            <div class="border-default-300 bg-card rounded-2xl border p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-body-color text-lg font-semibold">Amanda Costa</p>
                        <p class="text-default-400 text-sm">Turma ADM-2026-01</p>
                    </div>

                    <x-shared.status-badge
                        :enum="$makeEnum('em-dia', 'Em dia', 'success', 'tabler--shield-check')"
                        with-icon
                    />
                </div>

                <div class="mt-5 grid gap-3">
                    <div
                        class="border-default-300 bg-light/40 flex items-center justify-between rounded-xl border px-4 py-3"
                    >
                        <span class="text-default-400 text-sm">Status da adesão</span>
                        <x-shared.status-badge
                            :enum="$makeEnum('confirmada', 'Confirmada', 'primary', 'tabler--sparkles')"
                            with-icon
                            solid
                        />
                    </div>

                    <div
                        class="border-default-300 bg-light/40 flex items-center justify-between rounded-xl border px-4 py-3"
                    >
                        <span class="text-default-400 text-sm">Modalidade atual</span>
                        <x-shared.status-badge :enum="$makeEnum('pix', 'PIX', 'info', 'tabler--qrcode')" with-icon />
                    </div>
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
