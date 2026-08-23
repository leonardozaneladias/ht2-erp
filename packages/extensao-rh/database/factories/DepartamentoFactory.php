<?php

declare(strict_types=1);

namespace HT2ML\Rh\Database\Factories;

use HT2ML\Rh\Enums\StatusDepartamento;
use HT2ML\Rh\Models\Departamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Departamento>
 */
final class DepartamentoFactory extends Factory
{
    protected $model = Departamento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => app(\HT2ML\Core\Support\Tenancy\TenantContext::class)->empresaAtivaId() ?? \HT2ML\Core\Models\Empresa::factory(),
            'nome' => fake()->words(2, true),
            'sigla' => fake()->words(2, true),
            'status' => fake()->randomElement(StatusDepartamento::cases()),
        ];
    }

    public function trashed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now()->subDay(),
        ]);
    }
}
