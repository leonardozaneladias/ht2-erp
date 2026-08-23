<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separa, em cada catálogo de referência, o que veio do `referencia:sync` do que
 * o cliente cadastrou.
 *
 * Antes desta coluna, as duas populações se misturavam e o sync revertia edição
 * manual em silêncio: editar o nome de um município e rodar o comando desfazia a
 * mudança sem aviso. Com a origem explícita, linha `sync` é somente-leitura e o
 * comando nunca encosta em linha `manual` — as duas populações deixam de se cruzar
 * e não há o que reconciliar.
 *
 * Default `sync` porque tudo que existe hoje veio do CSV.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const TABELAS = [
        'paises', 'estados', 'municipios', 'moedas', 'bancos',
        'cargos', 'tipos_logradouro', 'cnaes', 'cfops', 'ncms',
    ];

    public function up(): void
    {
        foreach (self::TABELAS as $tabela) {
            Schema::table($tabela, function (Blueprint $table): void {
                $table->string('origem', 10)
                    ->default('sync')
                    ->index()
                    ->comment('sync = mantido por referencia:sync (somente leitura); manual = cadastrado nesta instalação');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABELAS as $tabela) {
            Schema::table($tabela, function (Blueprint $table): void {
                $table->dropColumn('origem');
            });
        }
    }
};
