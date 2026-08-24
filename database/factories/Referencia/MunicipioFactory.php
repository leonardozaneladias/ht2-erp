<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use HT2ML\Core\Models\Referencia\Estado;
use HT2ML\Core\Models\Referencia\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Municipio>
 */
final class MunicipioFactory extends Factory
{
    protected $model = Municipio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_ibge' => (string) fake()->unique()->numerify('#######'),
            'nome' => fake()->unique()->city(),
            'estado_id' => Estado::factory(),
        ];
    }
}
