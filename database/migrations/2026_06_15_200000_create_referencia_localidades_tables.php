<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dados de Referência — Localidades. Catálogos GLOBAIS (sem empresa_id): a chave
 * natural é o código (índice único); FKs têm índice. Populados por CSV versionado
 * via seeders idempotentes (ver database/seeders/Referencia e o comando
 * `referencia:sync`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_iso2', 2)->unique();
            $table->string('codigo_iso3', 3)->nullable();
            $table->string('codigo_numerico', 3)->nullable();
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('estados', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_ibge', 2)->unique();
            $table->string('sigla', 2)->unique();
            $table->string('nome');
            $table->string('regiao', 20);
            $table->timestamps();
        });

        Schema::create('municipios', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_ibge', 7)->unique();
            $table->string('nome');
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->timestamps();

            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('paises');
    }
};
