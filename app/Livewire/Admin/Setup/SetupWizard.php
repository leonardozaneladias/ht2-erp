<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Setup;

use App\Actions\Admin\Settings\ConcluirSetupAction;
use HT2ML\Core\DTOs\Admin\Settings\SetupDTO;
use HT2ML\Core\Settings\BrandingSettings;
use HT2ML\Core\Settings\GeneralSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Assistente de primeira instalação (público enquanto instalado=false).
 * Coleta empresa, marca e o primeiro administrador, então conclui a instalação.
 */
#[Layout('components.admin.setup-layout')]
#[Title('Configuração inicial')]
class SetupWizard extends Component
{
    public int $passo = 1;

    public string $nome_cliente = '';

    public string $razao_social = '';

    public string $cnpj = '';

    public string $nome_sistema = '';

    public string $cor_primaria = '#1577ce';

    public string $admin_nome = '';

    public string $admin_email = '';

    public string $admin_senha = '';

    public function mount(GeneralSettings $general, BrandingSettings $branding): void
    {
        if ($general->instalado) {
            $this->redirectRoute('admin.login');

            return;
        }

        $this->nome_sistema = $branding->nome_sistema;
    }

    public function proximo(): void
    {
        $this->validate($this->regrasDoPasso($this->passo));
        $this->passo = min(3, $this->passo + 1);
    }

    public function anterior(): void
    {
        $this->passo = max(1, $this->passo - 1);
    }

    public function concluir(ConcluirSetupAction $action): void
    {
        $this->validate(
            $this->regrasDoPasso(1) + $this->regrasDoPasso(2) + $this->regrasDoPasso(3),
        );

        $action->execute(new SetupDTO(
            nome_cliente: $this->nome_cliente,
            razao_social: $this->razao_social,
            cnpj: $this->cnpj,
            nome_sistema: $this->nome_sistema,
            cor_primaria: $this->cor_primaria,
            admin_nome: $this->admin_nome,
            admin_email: $this->admin_email,
            admin_senha: $this->admin_senha,
        ));

        session()->flash('success', 'Instalação concluída! Faça login para começar.');

        $this->redirectRoute('admin.login');
    }

    public function render(): View
    {
        return view('livewire.admin.setup.setup-wizard');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nome_cliente' => 'nome do cliente',
            'razao_social' => 'razão social',
            'nome_sistema' => 'nome do sistema',
            'cor_primaria' => 'cor primária',
            'admin_nome' => 'nome',
            'admin_email' => 'e-mail',
            'admin_senha' => 'senha',
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function regrasDoPasso(int $passo): array
    {
        return match ($passo) {
            1 => [
                'nome_cliente' => ['required', 'string', 'max:160'],
                'razao_social' => ['nullable', 'string', 'max:160'],
                'cnpj' => ['nullable', 'string', 'max:18'],
            ],
            2 => [
                'nome_sistema' => ['required', 'string', 'max:120'],
                'cor_primaria' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ],
            3 => [
                'admin_nome' => ['required', 'string', 'min:3', 'max:120'],
                'admin_email' => ['required', 'email', 'max:191', Rule::unique('admin_users', 'email')->whereNull('deleted_at')],
                'admin_senha' => ['required', 'string', \App\Support\Settings\PasswordPolicy::rule(), 'max:191'],
            ],
            default => [],
        };
    }
}
