<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lixeira (soft-delete) para o catálogo de moedas (ISO 4217). O índice unique de
 * codigo_iso segue CHEIO (não-parcial) de propósito: o upsert idempotente do
 * `referencia:sync` usa ON CONFLICT, que exige índice não-parcial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moedas', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('moedas', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
