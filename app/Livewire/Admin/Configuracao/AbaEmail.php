<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuracao;

use App\Actions\Admin\Settings\SaveEmailSettingsAction;
use App\DTOs\Admin\Settings\EmailSettingsDTO;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Services\Admin\Settings\SmtpTester;
use App\Settings\EmailSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

/**
 * Aba "E-mail": servidor SMTP usado pela aplicação.
 *
 * A senha não é pré-carregada no formulário; deixá-la em branco mantém a senha
 * atual. O botão "enviar teste" aplica os valores informados em runtime.
 */
class AbaEmail extends Component
{
    use EmiteNotificacoes;

    public string $smtp_host = '';

    public int $smtp_port = 587;

    public string $smtp_username = '';

    public string $smtp_password = '';

    public string $criptografia = 'tls';

    public string $from_email = '';

    public string $from_name = '';

    public bool $habilitar = false;

    public string $emailTeste = '';

    public function mount(EmailSettings $settings): void
    {
        $this->smtp_host = $settings->smtp_host;
        $this->smtp_port = $settings->smtp_port;
        $this->smtp_username = $settings->smtp_username;
        $this->criptografia = $settings->criptografia;
        $this->from_email = $settings->from_email;
        $this->from_name = $settings->from_name;
        $this->habilitar = $settings->habilitar;
        $this->emailTeste = (string) auth('admin')->user()->email;
    }

    public function salvar(SaveEmailSettingsAction $action, EmailSettings $settings): void
    {
        $this->validate();

        $action->execute($this->paraDTO($settings));

        $this->notificarSucesso('Configurações de e-mail salvas.');
    }

    public function enviarTeste(SmtpTester $tester, EmailSettings $settings): void
    {
        $this->validate($this->rules() + ['emailTeste' => ['required', 'email']]);

        try {
            $tester->enviar($this->paraDTO($settings), $this->emailTeste);
        } catch (Throwable $e) {
            $this->notificarErro('Falha ao enviar: ' . $e->getMessage());

            return;
        }

        $this->notificarSucesso("E-mail de teste enviado para {$this->emailTeste}.");
    }

    public function render(): View
    {
        return view('livewire.admin.configuracao.aba-email');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'smtp_host' => ['nullable', 'string', 'max:191'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:191'],
            'smtp_password' => ['nullable', 'string', 'max:191'],
            'criptografia' => ['required', 'in:tls,ssl,none'],
            'from_email' => ['nullable', 'email', 'max:191'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'habilitar' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'smtp_host' => 'servidor',
            'smtp_port' => 'porta',
            'smtp_username' => 'usuário',
            'smtp_password' => 'senha',
            'from_email' => 'e-mail remetente',
            'from_name' => 'nome remetente',
            'emailTeste' => 'e-mail de teste',
        ];
    }

    private function paraDTO(EmailSettings $settings): EmailSettingsDTO
    {
        return new EmailSettingsDTO(
            smtp_host: $this->smtp_host,
            smtp_port: $this->smtp_port,
            smtp_username: $this->smtp_username,
            // Em branco mantém a senha atual (não é exposta no formulário).
            smtp_password: $this->smtp_password !== '' ? $this->smtp_password : $settings->smtp_password,
            criptografia: $this->criptografia,
            from_email: $this->from_email,
            from_name: $this->from_name,
            habilitar: $this->habilitar,
        );
    }
}
