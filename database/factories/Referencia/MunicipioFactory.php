<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use App\Models\Referencia\Estado;
use App\Models\Referencia\Municipio;
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
