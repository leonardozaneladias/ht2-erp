<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Semeia os valores de fábrica de todas as configurações do sistema.
 *
 * Toda propriedade declarada nas classes App\Settings precisa existir aqui,
 * caso contrário o pacote lança MissingSettings ao carregar o grupo.
 * Os caminhos de arquivo de branding/login iniciam vazios: nesse estado o
 * BrandingService usa o fallback estático de config/branding.php.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        // ── Empresa / Cliente ───────────────────────────────────────────────
        $this->migrator->add('general.nome_cliente', '');
        $this->migrator->add('general.razao_social', '');
        $this->migrator->add('general.cnpj', '');
        $this->migrator->add('general.inscricao_estadual', '');
        $this->migrator->add('general.telefone', '');
        $this->migrator->add('general.email_contato', '');
        $this->migrator->add('general.endereco', '');
        $this->migrator->add('general.numero', '');
        $this->migrator->add('general.bairro', '');
        $this->migrator->add('general.cidade', '');
        $this->migrator->add('general.estado', '');
        $this->migrator->add('general.cep', '');
        $this->migrator->add('general.site_url', '');
        $this->migrator->add('general.instalado', false);

        // ── Marca e tema ────────────────────────────────────────────────────
        $this->migrator->add('branding.logo_arquivo', '');
        $this->migrator->add('branding.logo_dark_arquivo', '');
        $this->migrator->add('branding.logo_sm_arquivo', '');
        $this->migrator->add('branding.favicon_arquivo', '');
        $this->migrator->add('branding.nome_sistema', (string) config('app.name', 'ERP'));
        $this->migrator->add('branding.slogan', '');
        $this->migrator->add('branding.cor_primaria', '#1577ce');
        $this->migrator->add('branding.cor_secundaria', '#2b3a67');
        $this->migrator->add('branding.cor_sucesso', '#12b886');
        $this->migrator->add('branding.cor_warning', '#f5a623');
        $this->migrator->add('branding.cor_perigo', '#e5384b');
        $this->migrator->add('branding.cor_info', '#36a8ff');

        // ── Tela de login ───────────────────────────────────────────────────
        $this->migrator->add('login.fundo_imagem_arquivo', '');
        $this->migrator->add('login.titulo', 'Acesse sua conta');
        $this->migrator->add('login.subtitulo', 'Informe suas credenciais para continuar.');
        $this->migrator->add('login.mostrar_logo', true);
        $this->migrator->add('login.mostrar_versao', false);

        // ── E-mail / SMTP ───────────────────────────────────────────────────
        $this->migrator->add('email.smtp_host', '');
        $this->migrator->add('email.smtp_port', 587);
        $this->migrator->addEncrypted('email.smtp_username', '');
        $this->migrator->addEncrypted('email.smtp_password', '');
        $this->migrator->add('email.criptografia', 'tls');
        $this->migrator->add('email.from_email', '');
        $this->migrator->add('email.from_name', '');
        $this->migrator->add('email.habilitar', false);

        // ── Localização ─────────────────────────────────────────────────────
        $this->migrator->add('localizacao.idioma', 'pt_BR');
        $this->migrator->add('localizacao.timezone', 'America/Sao_Paulo');
        $this->migrator->add('localizacao.formato_data', 'd/m/Y');
        $this->migrator->add('localizacao.moeda_simbolo', 'R$');
        $this->migrator->add('localizacao.casas_decimais', 2);

        // ── Segurança ───────────────────────────────────────────────────────
        $this->migrator->add('seguranca.senha_min_caracteres', 8);
        $this->migrator->add('seguranca.senha_exige_maiuscula', true);
        $this->migrator->add('seguranca.senha_exige_numero', true);
        $this->migrator->add('seguranca.senha_exige_especial', false);
        $this->migrator->add('seguranca.sessao_timeout_minutos', 120);
        $this->migrator->add('seguranca.exigir_2fa_admin', false);
        $this->migrator->add('seguranca.dias_retencao_logs', 365);
    }
};
