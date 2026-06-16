<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use App\Models\Referencia\Pais;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pais>
 */
final class PaisFactory extends Factory
{
    protected $model = Pais::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_iso2' => mb_strtoupper(fake()->unique()->lexify('??')),
            'codigo_iso3' => mb_strtoupper(fake()->lexify('???')),
            'codigo_numerico' => fake()->numerify('###'),
            'nome' => fake()->country(),
            'ativo' => true,
        ];
    }
}
