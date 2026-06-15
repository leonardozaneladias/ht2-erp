<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete (lixeira) para o módulo Exemplo. `deleted_at` é independente do
 * status operacional do registro: move para a lixeira (some da listagem, mas é
 * restaurável), sem se confundir com liga/desliga. Ver App\Livewire\Concerns\ComLixeira.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exemplos', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('exemplos', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
