<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lixeira (soft-delete) para o catálogo de municípios IBGE. O índice unique de
 * codigo_ibge segue CHEIO (não-parcial) de propósito: o upsert idempotente do
 * `referencia:sync` usa ON CONFLICT, que exige índice não-parcial. A FK estado_id
 * não é tocada aqui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipios', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('municipios', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
