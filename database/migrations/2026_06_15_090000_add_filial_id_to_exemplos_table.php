<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona a dimensão filial ao módulo Exemplo (demo de referência do filtro
 * multi-empresa/filial). A filial é opcional e pertence à mesma empresa do
 * registro; ao remover a filial, os registros ficam sem filial (nullOnDelete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exemplos', function (Blueprint $table): void {
            $table->foreignId('filial_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('filiais')
                ->nullOnDelete();

            $table->index('filial_id');
        });
    }

    public function down(): void
    {
        Schema::table('exemplos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('filial_id');
        });
    }
};
