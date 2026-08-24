@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.password-strength-meter',
    'description' => 'Medidor visual de força reaproveitado pelo password-input e também disponível de forma standalone.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-2">
        <x-shared.card title="Via password-input" subtitle="Uso preferencial com a prop `with-meter`">
            <x-shared.password-input
                name="password_meter_integrated"
                label="Nova senha"
                hint="Mínimo de 8 caracteres com letras, números e símbolos."
                with-meter
                value="ArTF!nal2026"
            />
        </x-shared.card>

        <x-shared.card title="Standalone" subtitle="Disponível quando o campo de senha vier de outra composição">
            <div class="space-y-4">
                <x-shared.input
                    name="standalone_password_demo"
                    id="standalone-password-demo"
                    label="Senha importada"
                    type="password"
                    value="Portal#2026"
                />

                <x-shared.password-strength-meter field-id="standalone-password-demo" />
            </div>
        </x-shared.card>
    </div>
@endsection
