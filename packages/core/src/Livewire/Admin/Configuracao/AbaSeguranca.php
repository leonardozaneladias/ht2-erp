<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Configuracao;

use HT2ML\Core\Actions\Admin\Settings\SaveSegurancaSettingsAction;
use HT2ML\Core\DTOs\Admin\Settings\SegurancaSettingsDTO;
use HT2ML\Core\Livewire\Concerns\ConfirmaSegundoFator;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Aba "Segurança": política de senha, tempo de sessão e retenção de logs.
 *
 * A política de senha é consumida por HT2ML\Core\Support\Settings\PasswordPolicy; o
 * timeout de sessão é aplicado pelo SettingsRuntimeApplier. A flag
 * exigir_2fa_admin é imposta pelo middleware EnsureTwoFactorEnabled (opcional,
 * desligada por padrão). Salvar esta aba exige step-up de segundo fator.
 */
class AbaSeguranca extends Component
{
    use ConfirmaSegundoFator;
    use EmiteNotificacoes;

    public int $senha_min_caracteres = 8;

    public bool $senha_exige_maiuscula = true;

    public bool $senha_exige_numero = true;

    public bool $senha_exige_especial = false;

    public int $sessao_timeout_minutos = 120;

    public bool $exigir_2fa_admin = false;

    public bool $permitir_2fa_email = false;

    public int $dias_retencao_logs = 365;

    public int $login_max_tentativas = 5;

    public int $login_janela_minutos = 1;

    public int $lockout_max_falhas = 10;

    public int $lockout_duracao_minutos = 15;

    public bool $alertas_seguranca_habilitados = true;

    public bool $alerta_login_super_admin = false;

    public function mount(SegurancaSettings $settings): void
    {
        $this->senha_min_caracteres = $settings->senha_min_caracteres;
        $this->senha_exige_maiuscula = $settings->senha_exige_maiuscula;
        $this->senha_exige_numero = $settings->senha_exige_numero;
        $this->senha_exige_especial = $settings->senha_exige_especial;
        $this->sessao_timeout_minutos = $settings->sessao_timeout_minutos;
        $this->exigir_2fa_admin = $settings->exigir_2fa_admin;
        $this->permitir_2fa_email = $settings->permitir_2fa_email;
        $this->dias_retencao_logs = $settings->dias_retencao_logs;
        $this->login_max_tentativas = $settings->login_max_tentativas;
        $this->login_janela_minutos = $settings->login_janela_minutos;
        $this->lockout_max_falhas = $settings->lockout_max_falhas;
        $this->lockout_duracao_minutos = $settings->lockout_duracao_minutos;
        $this->alertas_seguranca_habilitados = $settings->alertas_seguranca_habilitados;
        $this->alerta_login_super_admin = $settings->alerta_login_super_admin;
    }

    /**
     * Pedido de salvamento vindo do formulário: valida e dispara o step-up de
     * segurança (2FA, ou senha quando o usuário não tem 2FA) antes de persistir.
     */
    public function solicitarSalvar(): void
    {
        $this->validate();
        $this->iniciarConfirmacao2fa('salvar');
    }

    public function salvar(): void
    {
        $this->ensureSegundoFatorConfirmado();
        $this->validate();

        app(SaveSegurancaSettingsAction::class)->execute(new SegurancaSettingsDTO(
            senha_min_caracteres: $this->senha_min_caracteres,
            senha_exige_maiuscula: $this->senha_exige_maiuscula,
            senha_exige_numero: $this->senha_exige_numero,
            senha_exige_especial: $this->senha_exige_especial,
            sessao_timeout_minutos: $this->sessao_timeout_minutos,
            exigir_2fa_admin: $this->exigir_2fa_admin,
            permitir_2fa_email: $this->permitir_2fa_email,
            dias_retencao_logs: $this->dias_retencao_logs,
            login_max_tentativas: $this->login_max_tentativas,
            login_janela_minutos: $this->login_janela_minutos,
            lockout_max_falhas: $this->lockout_max_falhas,
            lockout_duracao_minutos: $this->lockout_duracao_minutos,
            alertas_seguranca_habilitados: $this->alertas_seguranca_habilitados,
            alerta_login_super_admin: $this->alerta_login_super_admin,
        ));

        $this->notificarSucesso('Configurações de segurança salvas.');
    }

    public function render(): View
    {
        return view('livewire.admin.configuracao.aba-seguranca', [
            'opcoesMinimo' => $this->intervalo([6, 8, 10, 12, 14, 16, 20], ' caracteres'),
            'opcoesTimeout' => [
                ['value' => 15, 'label' => '15 minutos'],
                ['value' => 30, 'label' => '30 minutos'],
                ['value' => 60, 'label' => '1 hora'],
                ['value' => 120, 'label' => '2 horas'],
                ['value' => 240, 'label' => '4 horas'],
                ['value' => 480, 'label' => '8 horas'],
            ],
            'opcoesRetencao' => [
                ['value' => 30, 'label' => '30 dias'],
                ['value' => 90, 'label' => '90 dias'],
                ['value' => 180, 'label' => '180 dias'],
                ['value' => 365, 'label' => '1 ano'],
                ['value' => 730, 'label' => '2 anos'],
            ],
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'senha_min_caracteres' => ['required', 'integer', 'min:4', 'max:64'],
            'senha_exige_maiuscula' => ['boolean'],
            'senha_exige_numero' => ['boolean'],
            'senha_exige_especial' => ['boolean'],
            'sessao_timeout_minutos' => ['required', 'integer', 'min:5', 'max:1440'],
            'exigir_2fa_admin' => ['boolean'],
            'permitir_2fa_email' => ['boolean'],
            'dias_retencao_logs' => ['required', 'integer', 'min:1', 'max:3650'],
            'login_max_tentativas' => ['required', 'integer', 'min:1', 'max:100'],
            'login_janela_minutos' => ['required', 'integer', 'min:1', 'max:60'],
            'lockout_max_falhas' => ['required', 'integer', 'min:1', 'max:100'],
            'lockout_duracao_minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            'alertas_seguranca_habilitados' => ['boolean'],
            'alerta_login_super_admin' => ['boolean'],
        ];
    }

    /**
     * @param  array<int, int>  $valores
     * @return array<int, array{value: int, label: string}>
     */
    private function intervalo(array $valores, string $sufixo): array
    {
        return array_map(static fn (int $v): array => ['value' => $v, 'label' => $v . $sufixo], $valores);
    }
}
