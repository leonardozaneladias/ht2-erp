<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete técnico para anexos (D4): a remoção passa a ser soft, preservando
 * o registro e o arquivo físico para retenção/auditoria. O binário só é apagado
 * no force-delete (evento forceDeleted do model) ou em um GC futuro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anexos', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('anexos', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
