# Account Settings (Admin)

**Categoria:** Page / Utility
**Origem Inspinia:** `resources/views/pages/account-settings.blade.php`
**Plugins JS:** Nenhum (Livewire)

---

## Descrição

Página de configurações da conta do **admin logado** — editar perfil, alterar senha, preferências de notificação. Layout com sidebar de tabs (list-group) + conteúdo à direita. Uso: cada admin edita seu próprio perfil sem passar pelo CRUD completo de usuários.

---

## Padrão do Inspinia

Inspinia mostra um layout de settings com **sidebar vertical** de seções (Profile, Security, Notifications, Billing, etc.) e **conteúdo à direita** para a seção selecionada.

---

## View Proposta

### Estrutura de rotas

```php
// routes/admin.php
Route::middleware(['auth:admin'])->prefix('admin/conta')->name('admin.conta.')->group(function () {
    Route::get('/', [ContaController::class, 'edit'])->name('edit');
    Route::get('/senha', [ContaController::class, 'senha'])->name('senha');
    Route::get('/notificacoes', [ContaController::class, 'notificacoes'])->name('notificacoes');
});
```

### Layout

```blade
{{-- resources/views/admin/conta/edit.blade.php --}}
<x-admin.layout title="Configurações da Conta" subtitle="Meu Perfil">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Nav lateral --}}
        <div class="lg:col-span-1">
            <x-shared.card>
                <x-shared.list-group>
                    <x-shared.list-group-item
                        :href="route('admin.conta.edit')"
                        :active="request()->routeIs('admin.conta.edit')"
                    >
                        <i class="iconify tabler--user me-2"></i> Perfil
                    </x-shared.list-group-item>

                    <x-shared.list-group-item
                        :href="route('admin.conta.senha')"
                        :active="request()->routeIs('admin.conta.senha')"
                    >
                        <i class="iconify tabler--lock me-2"></i> Senha
                    </x-shared.list-group-item>

                    <x-shared.list-group-item
                        :href="route('admin.conta.notificacoes')"
                        :active="request()->routeIs('admin.conta.notificacoes')"
                    >
                        <i class="iconify tabler--bell me-2"></i> Notificações
                    </x-shared.list-group-item>
                </x-shared.list-group>
            </x-shared.card>
        </div>

        {{-- Conteúdo --}}
        <div class="lg:col-span-3">
            <x-shared.card title="Perfil">
                <livewire:admin.conta.form-perfil />
            </x-shared.card>
        </div>
    </div>
</x-admin.layout>
```

### Livewire component — Form Perfil

```php
// app/Livewire/Admin/Conta/FormPerfil.php
class FormPerfil extends Component
{
    use WithFileUploads;

    public string $nome = '';
    public string $email = '';
    public string $telefone = '';
    public $avatar = null;

    public function mount(): void
    {
        $user = auth('admin')->user();
        $this->nome = $user->nome;
        $this->email = $user->email;
        $this->telefone = $user->telefone ?? '';
    }

    protected function rules(): array
    {
        $user = auth('admin')->user();
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:admin_users,email,' . $user->id],
            'telefone' => ['nullable', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function salvar(): void
    {
        $this->validate();

        $user = auth('admin')->user();
        $user->update([
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
        ]);

        if ($this->avatar) {
            $path = $this->avatar->store('admin/avatars', 'public');
            $user->update(['avatar_path' => $path]);
        }

        $this->dispatch('toast', variant: 'success', message: 'Perfil atualizado com sucesso.');
    }

    public function render()
    {
        return view('livewire.admin.conta.form-perfil');
    }
}
```

```blade
{{-- resources/views/livewire/admin/conta/form-perfil.blade.php --}}
<form wire:submit="salvar">
    <x-shared.file-upload
        name="avatar"
        label="Foto de Perfil"
        :preview="auth('admin')->user()->avatar_url"
        wire:model="avatar"
    />

    <x-shared.input name="nome" label="Nome Completo" wire:model="nome" required />
    <x-shared.input name="email" label="E-mail" type="email" wire:model="email" required />
    <x-shared.phone-input name="telefone" label="Telefone" wire:model="telefone" />

    <div class="flex justify-end">
        <x-shared.loading-button variant="primary" type="submit" target="salvar">
            Salvar Alterações
        </x-shared.loading-button>
    </div>
</form>
```

---

## Classificação

| Critério         | Valor               |
| ---------------- | ------------------- |
| **Vai usar**     | 🟢 Sim              |
| **Complexidade** | Média (3 sub-forms) |
| **Status**       | 🔴 Não iniciado     |

---

## Notas de Adaptação

1. **Nav lateral com list-group** — alternativa ao tabs, melhor para configurações densas
2. **3 rotas separadas** — Perfil / Senha / Notificações. Melhor que 1 page com tudo (URL share-friendly)
3. **Livewire por form** — cada um independente, reseta ao navegar
4. **Alteração de senha:** form separado com campos "senha atual + nova + confirmação"
5. **Notificações:** array de checkboxes (qual evento dispara email para este admin) — integra com Laravel Notifications
6. **Não confundir com o CRUD de usuários** — o CRUD gerencia outros admins; este é "meu próprio perfil"
