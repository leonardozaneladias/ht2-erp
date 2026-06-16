<?php

declare(strict_types=1);

namespace Database\Factories\Referencia;

use App\Models\Referencia\Ncm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ncm>
 */
final class NcmFactory extends Factory
{
    protected $model = Ncm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => (string) fake()->unique()->numerify('########'),
            'descricao' => fake()->paragraph(),
        ];
    }
}
