<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seguranca.login_max_tentativas', 5);
        $this->migrator->add('seguranca.login_janela_minutos', 1);
        $this->migrator->add('seguranca.lockout_max_falhas', 10);
        $this->migrator->add('seguranca.lockout_duracao_minutos', 15);
        $this->migrator->add('seguranca.alertas_seguranca_habilitados', true);
        $this->migrator->add('seguranca.alerta_login_super_admin', false);
    }
};
