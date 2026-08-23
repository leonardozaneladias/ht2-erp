<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Settings;

/**
 * fundo_imagem_arquivo null = manter a imagem atual (sem upload novo).
 */
final readonly class LoginSettingsDTO
{
    public function __construct(
        public string $titulo,
        public string $subtitulo,
        public bool $mostrar_logo,
        public bool $mostrar_versao,
        public ?string $fundo_imagem_arquivo = null,
    ) {}
}
