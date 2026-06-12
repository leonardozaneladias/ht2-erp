<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuPersonalizacao>
 */
final class MenuPersonalizacaoFactory extends Factory
{
    protected $model = MenuPersonalizacao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => TipoPersonalizacaoMenu::Item,
            'key' => fake()->unique()->slug(2),
            'label' => null,
            'icone' => null,
            'secao_key' => null,
            'ordem' => null,
            'ativo' => true,
        ];
    }
}
