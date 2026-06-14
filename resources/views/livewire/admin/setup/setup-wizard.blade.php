<div>
    <x-shared.card>
        <div class="mb-6 text-center">
            <h1 class="text-default-900 text-xl font-bold">Configuração inicial</h1>
            <p class="text-default-400 text-sm">Vamos preparar o sistema para começar a usar.</p>
        </div>

        {{-- Stepper --}}
        <div class="mb-8 flex items-center justify-center gap-2">
            @foreach (['Empresa', 'Marca', 'Administrador'] as $i => $rotulo)
                @php ($n = $i + 1)
                <div class="flex items-center gap-2">
                    <span
                        @class ([
                            'flex size-8 items-center justify-center rounded-full text-sm font-semibold transition-colors',
                            'bg-primary text-white' => $passo >= $n,
                            'bg-default-200 text-default-500' => $passo < $n,
                        ])
                        >{{ $n }}</span
                    >
                    <span
                        @class ([
                            'hidden text-sm sm:inline',
                            'text-body-color font-medium' => $passo >= $n,
                            'text-default-400' => $passo < $n,
                        ])
                        >{{ $rotulo }}</span
                    >
                    @unless ($loop->last)
                        <span class="bg-default-200 mx-1 h-px w-6"></span>
                    @endunless
                </div>
            @endforeach
        </div>

        {{-- Passo 1: Empresa --}}
        @if ($passo === 1)
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input name="nome_cliente" label="Nome do cliente" wire:model="nome_cliente" required />
                <x-shared.input name="razao_social" label="Razão social" wire:model="razao_social" />
                <div class="md:col-span-2 md:max-w-xs">
                    <x-shared.cnpj-input name="cnpj" wire:model="cnpj" />
                </div>
            </div>
        @endif

        {{-- Passo 2: Marca --}}
        @if ($passo === 2)
            <div class="grid gap-4">
                <x-shared.input name="nome_sistema" label="Nome do sistema" wire:model="nome_sistema" required />
                <x-shared.color-picker
                    name="cor_primaria"
                    label="Cor primária"
                    wire:model="cor_primaria"
                    :value="$cor_primaria"
                />
            </div>
        @endif

        {{-- Passo 3: Administrador --}}
        @if ($passo === 3)
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.input name="admin_nome" label="Seu nome" wire:model="admin_nome" required />
                <x-shared.input
                    name="admin_email"
                    label="E-mail de acesso"
                    type="email"
                    wire:model="admin_email"
                    required
                />
                <div class="md:col-span-2">
                    <x-shared.password-input
                        name="admin_senha"
                        label="Senha"
                        wire:model="admin_senha"
                        required
                        with-meter
                    />
                    <small class="text-default-400 mt-1 block text-xs">
                        {{ \App\Support\Settings\PasswordPolicy::descricao() }}
                    </small>
                </div>
            </div>
        @endif

        {{-- Navegação --}}
        <div class="border-default-200 dark:border-default-700 mt-8 flex items-center justify-between border-t pt-5">
            <div>
                @if ($passo > 1)
                    <x-shared.button type="button" variant="light" wire:click="anterior" icon="tabler--arrow-left">
                        Voltar
                    </x-shared.button>
                @endif
            </div>
            <div>
                @if ($passo < 3)
                    <x-shared.button type="button" wire:click="proximo" iconRight="tabler--arrow-right">
                        Continuar
                    </x-shared.button>
                @else
                    <x-shared.loading-button type="button" wire:click="concluir" target="concluir" icon="tabler--check">
                        Concluir instalação
                    </x-shared.loading-button>
                @endif
            </div>
        </div>
    </x-shared.card>
</div>
