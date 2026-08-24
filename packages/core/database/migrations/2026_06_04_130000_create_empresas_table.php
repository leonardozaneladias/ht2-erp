<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table): void {
            $table->id();

            // Identificação
            $table->string('nome');
            $table->string('razao_social')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('inscricao_estadual', 30)->nullable();

            // Contato / endereço
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('endereco')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('site_url')->nullable();

            // Branding por empresa (aplicado no painel quando esta empresa está ativa)
            $table->string('logo_arquivo')->nullable();
            $table->string('logo_dark_arquivo')->nullable();
            $table->string('favicon_arquivo')->nullable();
            $table->string('cor_primaria', 7)->nullable();
            $table->string('cor_secundaria', 7)->nullable();
            $table->string('cor_sucesso', 7)->nullable();
            $table->string('cor_warning', 7)->nullable();
            $table->string('cor_perigo', 7)->nullable();
            $table->string('cor_info', 7)->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
