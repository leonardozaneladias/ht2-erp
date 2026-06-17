<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete (lixeira) para os módulos do RH: departamentos e funcionarios.
 * Ver App\Livewire\Concerns\ComLixeira.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departamentos', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('funcionarios', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('funcionarios', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
