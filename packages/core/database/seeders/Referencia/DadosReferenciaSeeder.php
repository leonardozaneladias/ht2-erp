<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Seeders\Referencia;

use HT2ML\Core\Support\Modules\ModuleRegistry;
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
    ];

    /**
     * Catálogos do core mais os declarados por extensões.
     *
     * @return array<string, class-string<Seeder>>
     */
    public static function conjuntos(): array
    {
        return [...self::CONJUNTOS, ...ModuleRegistry::catalogosDeReferencia()];
    }

    public function run(): void
    {
        $this->call(array_values(self::conjuntos()));
    }
}
