<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use HT2ML\Core\Models\Referencia\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cargo>
 */
final class CargoFactory extends Factory
{
    protected $model = Cargo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_cbo' => (string) fake()->unique()->numerify('######'),
            'descricao' => fake()->jobTitle(),
            'ativo' => true,
        ];
    }
}
