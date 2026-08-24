<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dados de Referência — Financeiro (moedas, bancos). Catálogos globais.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moedas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_iso', 3)->unique();
            $table->string('numerico', 3)->nullable();
            $table->string('nome');
            $table->string('simbolo', 10)->nullable();
            $table->unsignedTinyInteger('casas_decimais')->default(2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('bancos', function (Blueprint $table): void {
            $table->id();
            $table->string('ispb', 8)->unique();
            $table->string('codigo_compe', 3)->nullable();
            $table->string('nome');
            $table->string('nome_completo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('codigo_compe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bancos');
        Schema::dropIfExists('moedas');
    }
};
