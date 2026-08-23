<?php

declare(strict_types=1);

namespace HT2ML\Core\Settings;

use HT2ML\Core\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Dados do cliente dono desta instalação (single-tenant).
 *
 * `instalado` é a flag consumida pelo Setup Wizard: quando false, o primeiro
 * acesso é redirecionado para o assistente de configuração inicial.
 */
final class GeneralSettings extends Settings
{
    public string $nome_cliente;

    public string $razao_social;

    public string $cnpj;

    public string $inscricao_estadual;

    public string $telefone;

    public string $email_contato;

    public string $endereco;

    public string $numero;

    public string $bairro;

    public string $cidade;

    public string $estado;

    public string $cep;

    public string $site_url;

    public bool $instalado;

    public static function group(): string
    {
        return SettingsGroup::GERAL->value;
    }
}
