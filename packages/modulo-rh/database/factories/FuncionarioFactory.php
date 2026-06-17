<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Database\Factories;

use HT2ERP\Rh\Enums\StatusFuncionario;
use HT2ERP\Rh\Models\Funcionario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Funcionario>
 */
final class FuncionarioFactory extends Factory
{
    protected $model = Funcionario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => app(\App\Support\Tenancy\TenantContext::class)->empresaAtivaId() ?? \App\Models\Empresa::factory(),
            'nome' => fake()->words(2, true),
            'cpf' => fake()->numerify('###.###.###-##'),
            'cargo' => fake()->words(2, true),
            'salario' => fake()->numberBetween(1000, 500000),
            'admissao' => fake()->dateTimeBetween('-1 year'),
            'status' => fake()->randomElement(StatusFuncionario::cases()),
        ];
    }

    public function trashed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now()->subDay(),
        ]);
    }
}
