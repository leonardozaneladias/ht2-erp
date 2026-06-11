<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use App\Actions\Admin\Convites\AceitarConviteAction;
use App\Exceptions\AccessException;
use App\Models\AdminUserConvite;
use App\Services\Admin\Security\LimiteTentativas;
use App\Support\Settings\PasswordPolicy;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.admin.auth-layout')]
#[Title('Ativar conta')]
final class AceitarConvite extends Component
{
    #[Locked]
    public string $token = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $conviteValido = false;

    public string $emailConvidado = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $convite = AdminUserConvite::localizarPorToken($token);
        $usuario = $convite?->adminUser;

        if ($convite !== null && $convite->estaUtilizavel() && $usuario !== null && $usuario->ativo) {
            $this->conviteValido = true;
            $this->emailConvidado = $usuario->email;
        }
    }

    public function aceitar(AceitarConviteAction $action, LimiteTentativas $limite): void
    {
        $chave = 'convite:' . request()->ip();

        if ($limite->excedido($chave)) {
            $this->addError('password', 'Muitas tentativas. Tente novamente em alguns minutos.');

            return;
        }

        $this->validate();

        try {
            $action->execute($this->token, $this->password);
        } catch (AccessException $e) {
            $limite->registrar($chave);
            $this->addError('password', $e->getMessage());

            return;
        }

        $limite->limpar($chave);

        session()->flash('success', 'Conta ativada. Entre com seu e-mail e a senha definida.');
        $this->redirect(route('admin.login'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.auth.aceitar-convite');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
            'password_confirmation' => ['required'],
        ];
    }
}
