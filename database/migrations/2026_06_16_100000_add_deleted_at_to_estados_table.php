<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lixeira (soft-delete) para o catálogo de estados (UF). Os índices unique de
 * codigo_ibge/sigla seguem CHEIOS (não-parciais) de propósito: o upsert
 * idempotente do `referencia:sync` usa ON CONFLICT, que exige índice não-parcial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estados', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('estados', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
