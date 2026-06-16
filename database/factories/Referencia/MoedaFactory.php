<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use App\Models\Referencia\Moeda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Moeda>
 */
final class MoedaFactory extends Factory
{
    protected $model = Moeda::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_iso' => mb_strtoupper(fake()->unique()->lexify('???')),
            'numerico' => fake()->numerify('###'),
            'nome' => fake()->currencyCode() . ' ' . fake()->word(),
            'simbolo' => '$',
            'casas_decimais' => 2,
            'ativo' => true,
        ];
    }
}
