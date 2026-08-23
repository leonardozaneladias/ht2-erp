<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Database\Factories;

use HT2ML\FiscalBr\Models\Ncm;
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
