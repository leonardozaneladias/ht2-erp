<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete (lixeira) para empresas e filiais. `deleted_at` é independente de
 * `ativo`: `ativo` liga/desliga o operacional (segue visível), `deleted_at` move
 * para a lixeira (some, restaurável). Empresa: guardas impedem excluir a ativa
 * ou a última; a cascata física só ocorre no force-delete. Filial: lixeira gerida
 * dentro de Empresas. Ver HT2ML\Core\Livewire\Concerns\ComLixeira.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('filiais', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('filiais', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
