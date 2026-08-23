<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dados de Referência — Fiscal (CNAE, CFOP, NCM). Catálogos globais, consulta.
 * Candidatos a virar um pacote ht2ml/dados-fiscais no futuro — por isso os
 * models são autocontidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cnaes', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 7)->unique();
            $table->string('descricao');
            $table->string('secao', 1)->nullable();
            $table->string('divisao', 2)->nullable();
            $table->string('grupo', 3)->nullable();
            $table->string('classe', 5)->nullable();
            $table->timestamps();
        });

        Schema::create('cfops', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 4)->unique();
            $table->string('descricao');
            $table->string('tipo', 10);
            $table->string('aplicacao')->nullable();
            $table->timestamps();
        });

        Schema::create('ncms', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 8)->unique();
            $table->text('descricao'); // algumas descrições da TIPI passam de 1.000 chars
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncms');
        Schema::dropIfExists('cfops');
        Schema::dropIfExists('cnaes');
    }
};
