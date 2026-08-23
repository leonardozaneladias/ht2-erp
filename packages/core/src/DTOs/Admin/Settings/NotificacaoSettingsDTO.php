<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Settings;

final readonly class NotificacaoSettingsDTO
{
    public function __construct(
        public string $toast_posicao,
        public string $toast_duracao,
        public string $toast_estilo,
        public int $toast_maximo,
        public string $confirmacao_posicao,
    ) {}
}
