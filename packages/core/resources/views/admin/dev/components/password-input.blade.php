@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.password-input',
    'description' => 'Campo de senha com toggle de visibilidade e medidor opcional de força, sem dependência estrutural de Alpine no shell atual.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Senha base" subtitle="Toggle visual já integrado ao helper JS do admin">
            <div class="space-y-4">
                <x-shared.password-input
                    name="password_login"
                    label="Senha"
                    hint="Usado no login do admin e do portal."
                />
                <x-shared.password-input
                    name="password_cadastro"
                    label="Nova senha"
                    hint="Mínimo de 8 caracteres, com letras, números e símbolos."
                    with-meter
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Usuários admin">
            <div class="border-default-300 bg-light/40 space-y-4 rounded-2xl border p-4">
                <x-shared.input name="email_admin" label="E-mail" type="email" value="operacoes@exemplo.com.br" />
                <x-shared.password-input
                    name="senha_admin"
                    label="Senha temporária"
                    with-meter
                    hint="A senha é redefinida no primeiro acesso do usuário."
                    value="ArTF!nal2026"
                />
                <x-shared.password-input name="senha_admin_confirm" label="Confirmar senha" value="ArTF!nal2026" />
            </div>
        </x-shared.card>
    </div>
@endsection
