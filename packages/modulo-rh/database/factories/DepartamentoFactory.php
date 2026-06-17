<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Database\Factories;

use HT2ERP\Rh\Enums\StatusDepartamento;
use HT2ERP\Rh\Models\Departamento;
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
            'empresa_id' => app(\App\Support\Tenancy\TenantContext::class)->empresaAtivaId() ?? \App\Models\Empresa::factory(),
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
