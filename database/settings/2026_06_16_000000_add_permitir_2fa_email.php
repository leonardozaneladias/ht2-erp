<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Switch global do 2FA por e-mail. Quando desligado, nenhum usuário pode usar o
 * e-mail como segundo fator, mesmo com a preferência individual ligada —
 * AdminUser::emailDoisFatoresDisponivel() checa esta flag em runtime.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seguranca.permitir_2fa_email', false);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('seguranca.permitir_2fa_email');
    }
};
