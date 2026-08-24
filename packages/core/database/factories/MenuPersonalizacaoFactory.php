<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Factories;

use HT2ML\Core\Enums\TipoPersonalizacaoMenu;
use HT2ML\Core\Models\MenuPersonalizacao;
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
            'grupo_key' => null,
            'ordem' => null,
            'ativo' => true,
            'e_custom' => false,
        ];
    }

    public function grupo(string $secaoKey): self
    {
        return $this->state(fn (): array => [
            'tipo' => TipoPersonalizacaoMenu::Grupo,
            'key' => 'grupo-' . fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'icone' => 'tabler--folder',
            'secao_key' => $secaoKey,
            'e_custom' => true,
        ]);
    }

    public function secaoCustom(): self
    {
        return $this->state(fn (): array => [
            'tipo' => TipoPersonalizacaoMenu::Secao,
            'key' => 'secao-' . fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'e_custom' => true,
        ]);
    }
}
