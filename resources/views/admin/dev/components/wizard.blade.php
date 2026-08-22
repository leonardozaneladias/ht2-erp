@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.wizard',
    'description' => 'Stepper server-driven para fluxos multi-etapas: o estado vem do Livewire (prop current), o componente só indica onde o usuário está.',
])

@section ('preview')
    <div class="grid gap-6">
        <x-shared.card title="Estados do fluxo" subtitle="O passo atual é dirigido pela prop :current (1-indexed)">
            <div class="space-y-10">
                <div class="space-y-3">
                    <p class="text-body-color text-sm font-semibold">Início (passo 1 de 4)</p>
                    <x-shared.wizard :steps="['Enviar', 'Analisar', 'Confirmar', 'Concluído']" :current="1" />
                </div>

                <div class="space-y-3">
                    <p class="text-body-color text-sm font-semibold">No meio (passo 3 de 4) — passos anteriores ganham check</p>
                    <x-shared.wizard :steps="['Enviar', 'Analisar', 'Confirmar', 'Concluído']" :current="3" />
                </div>

                <div class="space-y-3">
                    <p class="text-body-color text-sm font-semibold">Fluxo concluído (último passo ativo)</p>
                    <x-shared.wizard :steps="['Enviar', 'Analisar', 'Confirmar', 'Concluído']" :current="4" />
                </div>
            </div>
        </x-shared.card>

        <x-shared.card
            title="Passos com ícone"
            subtitle="Cada passo aceita um ícone Tabler opcional no lugar do número"
        >
            <x-shared.wizard
                :steps="[
                    ['label' => 'Enviar planilha', 'icon' => 'tabler--upload'],
                    ['label' => 'Análise', 'icon' => 'tabler--search'],
                    ['label' => 'Confirmação', 'icon' => 'tabler--checklist'],
                    ['label' => 'Concluído', 'icon' => 'tabler--confetti'],
                ]"
                :current="2"
            />
        </x-shared.card>

        <x-shared.card
            title="Com conteúdo no slot"
            subtitle="O slot é opcional — quando presente, renderiza abaixo do stepper"
        >
            <x-shared.wizard :steps="['Dados', 'Endereço', 'Revisão']" :current="2">
                <div class="border-default-300 bg-light/40 rounded-lg border border-dashed p-6 text-center">
                    <p class="text-body-color text-sm font-medium">Conteúdo da etapa atual</p>
                    <p class="text-default-400 mt-1 text-xs">Em telas reais, o Livewire renderiza aqui a etapa corrente e controla a navegação.</p>
                </div>
            </x-shared.wizard>
        </x-shared.card>

        <x-shared.card
            title="Telas pequenas"
            subtitle="Abaixo do breakpoint sm o stepper vira barra compacta — redimensione a janela para ver"
        >
            <div class="mx-auto max-w-xs">
                <div class="border-default-300 rounded-lg border p-4">
                    {{-- Força o fallback mobile num container estreito apenas para demonstração --}}
                    <div class="sm:hidden">
                        <x-shared.wizard :steps="['Enviar', 'Analisar', 'Confirmar', 'Concluído']" :current="2" />
                    </div>
                    <p class="text-default-400 hidden text-center text-xs sm:block">Reduza a largura da janela abaixo de 640px para ver a versão compacta (barra + “Etapa X de N”).</p>
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
