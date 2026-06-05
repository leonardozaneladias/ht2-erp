<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Settings;

final readonly class SegurancaSettingsDTO
{
    public function __construct(
        public int $senha_min_caracteres,
        public bool $senha_exige_maiuscula,
        public bool $senha_exige_numero,
        public bool $senha_exige_especial,
        public int $sessao_timeout_minutos,
        public bool $exigir_2fa_admin,
        public int $dias_retencao_logs,
        public int $login_max_tentativas,
        public int $login_janela_minutos,
        public int $lockout_max_falhas,
        public int $lockout_duracao_minutos,
        public bool $alertas_seguranca_habilitados,
        public bool $alerta_login_super_admin,
    ) {}
}
