<?php

declare(strict_types=1);

namespace App\DTOs\Admin\Settings;

/**
 * Os campos *_arquivo são opcionais: null significa "manter o arquivo atual"
 * (nenhum upload novo nesta gravação).
 */
final readonly class BrandingSettingsDTO
{
    public function __construct(
        public string $nome_sistema,
        public string $slogan,
        public string $cor_primaria,
        public string $cor_secundaria,
        public string $cor_sucesso,
        public string $cor_warning,
        public string $cor_perigo,
        public string $cor_info,
        public ?string $logo_arquivo = null,
        public ?string $logo_dark_arquivo = null,
        public ?string $logo_sm_arquivo = null,
        public ?string $favicon_arquivo = null,
    ) {}
}
