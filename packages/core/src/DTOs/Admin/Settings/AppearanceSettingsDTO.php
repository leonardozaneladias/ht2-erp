<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Settings;

final readonly class AppearanceSettingsDTO
{
    public function __construct(
        public string $tema_padrao,
        public string $skin,
        public string $topbar_color,
        public string $menu_color,
        public string $layout_width,
        public string $sidenav_size_padrao,
        public bool $sidenav_user,
        public bool $permitir_preferencia_usuario,
    ) {}
}
