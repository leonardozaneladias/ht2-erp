<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Settings;

/**
 * Dados coletados pelo Setup Wizard de primeira instalação.
 */
final readonly class SetupDTO
{
    public function __construct(
        public string $nome_cliente,
        public string $razao_social,
        public string $cnpj,
        public string $nome_sistema,
        public string $cor_primaria,
        public string $admin_nome,
        public string $admin_email,
        public string $admin_senha,
    ) {}
}
