@extends ('admin.dev.components.shell', [
    'title' => 'Preview • x-shared.checkbox',
    'description' => 'Checkbox para opções independentes e seleção múltipla, separado do uso semântico de toggle on/off.',
])

@section ('preview')
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <x-shared.card title="Casos básicos" subtitle="Seleção avulsa e grupos simples">
            <div class="space-y-4">
                <x-shared.checkbox name="remember" label="Lembrar-me neste dispositivo" checked />
                <x-shared.checkbox
                    name="aceite_termos"
                    label="Aceito receber comunicações da turma"
                    hint="Preferência usada para notificações não transacionais."
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Caso de domínio" subtitle="Matriz ACL resumida">
            <div class="border-default-300 bg-light/40 space-y-3 rounded-2xl border p-4">
                @foreach ([
                    ['label' => 'Instituições • Visualizar', 'checked' => true],
                    ['label' => 'Instituições • Criar', 'checked' => true],
                    ['label' => 'Instituições • Editar', 'checked' => false],
                    ['label' => 'Instituições • Excluir', 'checked' => false],
                ] as $permission)
                    <x-shared.checkbox
                        :name="\Illuminate\Support\Str::slug($permission['label'], '_')"
                        :label="$permission['label']"
                        :checked="$permission['checked']"
                    />
                @endforeach
            </div>
        </x-shared.card>
    </div>
@endsection
