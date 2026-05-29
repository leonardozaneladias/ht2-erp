@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.input',
    'description' => 'Campo base para texto, e-mail, número, busca e demais entradas simples do admin e do portal.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <x-shared.card title="Variações principais" subtitle="Label, hint, ícone e tipos de input">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.input name="nome" label="Nome completo" placeholder="Ex.: Ana Carolina Vieira" required />
                <x-shared.input
                    name="email"
                    label="E-mail"
                    type="email"
                    icon="tabler--mail"
                    placeholder="financeiro@exemplo.com.br"
                />
                <x-shared.input
                    name="meta"
                    label="Meta de usuários"
                    type="number"
                    hint="Campo opcional para acompanhamento comercial"
                />
                <x-shared.input
                    name="slug"
                    label="Slug público"
                    icon="tabler--link"
                    placeholder="campanha-2026"
                    value="campanha-2026"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Cadastro de empresa">
            <div class="grid gap-4 md:grid-cols-2">
                <x-shared.input name="razao_social" label="Razão Social" value="Empresa Exemplo LTDA" required />
                <x-shared.input name="nome_fantasia" label="Nome Fantasia" value="Sistema Admin" required />
                <x-shared.input
                    name="email_financeiro"
                    label="E-mail Financeiro"
                    type="email"
                    icon="tabler--mail"
                    value="financeiro@exemplo.com.br"
                />
                <x-shared.input
                    name="site"
                    label="Site"
                    type="url"
                    icon="tabler--world-www"
                    value="https://exemplo.com.br"
                />
            </div>
        </x-shared.card>
    </div>
@endsection
