<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dados de Referência — RH/Pessoas (cargos/CBO, tipos de logradouro). Globais.
 * `cargos` é o único catálogo de referência com cadastro editável (CBO + cargos
 * próprios do cliente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_cbo', 6)->unique();
            $table->string('descricao');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_logradouro', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 60)->unique();
            $table->string('codigo', 10)->nullable();
            $table->string('abrev', 15)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_logradouro');
        Schema::dropIfExists('cargos');
    }
};
