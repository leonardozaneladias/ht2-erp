<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Settings;

final readonly class EmailSettingsDTO
{
    public function __construct(
        public string $smtp_host,
        public int $smtp_port,
        public string $smtp_username,
        public string $smtp_password,
        public string $criptografia,
        public string $from_email,
        public string $from_name,
        public bool $habilitar,
    ) {}
}
