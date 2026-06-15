<?php

declare(strict_types=1);

namespace Database\Seeders\Referencia;

use Illuminate\Database\Seeder;

/**
 * Agregador dos seeders de dados de referência. A ordem do mapa respeita as
 * dependências de FK (ex.: estados antes de municipios). É chamado tanto pelo
 * DatabaseSeeder (dev) quanto pelo comando `referencia:sync` (deploy/prod).
 */
final class DadosReferenciaSeeder extends Seeder
{
    /**
     * conjunto => seeder, na ordem de dependência.
     *
     * @var array<string, class-string<Seeder>>
     */
    public const CONJUNTOS = [
        'paises' => PaisSeeder::class,
        'estados' => EstadoSeeder::class,
        'municipios' => MunicipioSeeder::class,
        'moedas' => MoedaSeeder::class,
        'bancos' => BancoSeeder::class,
        'cargos' => CargoSeeder::class,
        'tipos_logradouro' => TipoLogradouroSeeder::class,
        'cnaes' => CnaeSeeder::class,
        'cfops' => CfopSeeder::class,
        'ncms' => NcmSeeder::class,
    ];

    public function run(): void
    {
        $this->call(array_values(self::CONJUNTOS));
    }
}
