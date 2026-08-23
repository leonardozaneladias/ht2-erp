<?php

declare(strict_types=1);

namespace HT2ML\Core\DTOs\Admin\Settings;

/**
 * Conjunto curado de cores + chrome aplicável em 1 clique no Centro de Aparência.
 * Não inclui logos (identidade fica a cargo do usuário).
 */
final readonly class ThemePresetDTO
{
    public function __construct(
        public string $cor_primaria,
        public string $cor_secundaria,
        public string $cor_sucesso,
        public string $cor_warning,
        public string $cor_perigo,
        public string $cor_info,
        public string $skin,
        public string $topbar_color,
        public string $menu_color,
        public string $tema_padrao,
    ) {}

    /**
     * @return array<string, string>
     */
    public function cores(): array
    {
        return [
            'cor_primaria' => $this->cor_primaria,
            'cor_secundaria' => $this->cor_secundaria,
            'cor_sucesso' => $this->cor_sucesso,
            'cor_warning' => $this->cor_warning,
            'cor_perigo' => $this->cor_perigo,
            'cor_info' => $this->cor_info,
        ];
    }
}
