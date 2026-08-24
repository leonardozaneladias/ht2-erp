<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Factories\Referencia;

use HT2ML\Core\Models\Referencia\Banco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banco>
 */
final class BancoFactory extends Factory
{
    protected $model = Banco::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ispb' => fake()->unique()->numerify('########'),
            'codigo_compe' => fake()->numerify('###'),
            'nome' => fake()->company(),
            'nome_completo' => fake()->company(),
            'ativo' => true,
        ];
    }
}
