<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Settings;

final readonly class LocalizacaoSettingsDTO
{
    public function __construct(
        public string $idioma,
        public string $timezone,
        public string $formato_data,
        public string $moeda_simbolo,
        public int $casas_decimais,
    ) {}
}
