<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Settings;

use HT2ML\Core\DTOs\Admin\Settings\EmailSettingsDTO;
use HT2ML\Core\Settings\EmailSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveEmailSettingsAction
{
    public function execute(EmailSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(EmailSettings::class);

            $settings->smtp_host = $dto->smtp_host;
            $settings->smtp_port = $dto->smtp_port;
            $settings->smtp_username = $dto->smtp_username;
            $settings->smtp_password = $dto->smtp_password;
            $settings->criptografia = $dto->criptografia;
            $settings->from_email = $dto->from_email;
            $settings->from_name = $dto->from_name;
            $settings->habilitar = $dto->habilitar;
            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties(['smtp_host' => $dto->smtp_host, 'habilitar' => $dto->habilitar])
                ->event('updated')
                ->log('Configurações de e-mail atualizadas');
        });
    }
}
