<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Database\Factories;

use HT2ML\FiscalBr\Models\Cnae;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cnae>
 */
final class CnaeFactory extends Factory
{
    protected $model = Cnae::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => (string) fake()->unique()->numerify('#######'),
            'descricao' => fake()->sentence(),
            'secao' => mb_strtoupper(fake()->randomLetter()),
            'divisao' => (string) fake()->numerify('##'),
            'grupo' => (string) fake()->numerify('###'),
            'classe' => (string) fake()->numerify('#####'),
        ];
    }
}
