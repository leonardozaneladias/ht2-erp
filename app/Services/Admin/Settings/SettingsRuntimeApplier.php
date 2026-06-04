<?php

declare(strict_types=1);

namespace App\Services\Admin\Settings;

use App\Settings\EmailSettings;
use App\Settings\LocalizacaoSettings;
use App\Settings\SegurancaSettings;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Aplica as configurações persistidas (App\Settings) ao config() do Laravel
 * no boot da aplicação: idioma/fuso, servidor de e-mail e tempo de sessão.
 *
 * É deliberadamente tolerante a falhas: durante a primeira instalação (antes
 * da tabela `settings` existir ou ser semeada) ou se o repositório estiver
 * indisponível, mantém os valores de config/.env como fallback em vez de
 * derrubar o boot — sempre registrando o motivo no log.
 */
final class SettingsRuntimeApplier
{
    public function __construct(
        private readonly Application $app,
        private readonly ConfigRepository $config,
    ) {}

    public function apply(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $this->aplicarLocalizacao($this->app->make(LocalizacaoSettings::class));
            $this->aplicarEmail($this->app->make(EmailSettings::class));
            $this->aplicarSeguranca($this->app->make(SegurancaSettings::class));
        } catch (Throwable $e) {
            Log::warning('SettingsRuntimeApplier: mantendo configuração padrão.', [
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function aplicarLocalizacao(LocalizacaoSettings $loc): void
    {
        if ($loc->idioma !== '') {
            $this->app->setLocale($loc->idioma);
            $this->config->set('app.locale', $loc->idioma);
        }

        if ($loc->timezone !== '') {
            $this->config->set('app.timezone', $loc->timezone);
            date_default_timezone_set($loc->timezone);
        }
    }

    private function aplicarEmail(EmailSettings $email): void
    {
        if (! $email->habilitar || $email->smtp_host === '') {
            return;
        }

        $this->config->set('mail.default', 'smtp');
        $this->config->set('mail.mailers.smtp.host', $email->smtp_host);
        $this->config->set('mail.mailers.smtp.port', $email->smtp_port);
        $this->config->set('mail.mailers.smtp.username', $email->smtp_username);
        $this->config->set('mail.mailers.smtp.password', $email->smtp_password);
        $this->config->set('mail.mailers.smtp.scheme', $email->criptografia === 'ssl' ? 'smtps' : 'smtp');

        if ($email->from_email !== '') {
            $this->config->set('mail.from.address', $email->from_email);
        }

        if ($email->from_name !== '') {
            $this->config->set('mail.from.name', $email->from_name);
        }
    }

    private function aplicarSeguranca(SegurancaSettings $seg): void
    {
        if ($seg->sessao_timeout_minutos > 0) {
            $this->config->set('session.lifetime', $seg->sessao_timeout_minutos);
        }
    }
}
