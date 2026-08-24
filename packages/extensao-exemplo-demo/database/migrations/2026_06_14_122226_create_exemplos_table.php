<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exemplos', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('site')->nullable();
            $table->text('descricao')->nullable();
            $table->string('email');
            $table->string('telefone', 20)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('cpf', 14)->nullable();
            $table->integer('preco');
            $table->decimal('custo', 10, 2);
            $table->integer('quantidade');
            $table->string('cor', 9)->nullable();
            $table->string('categoria');
            $table->json('tags')->nullable();
            $table->boolean('destaque')->default(false);
            $table->date('data_inicio');
            $table->timestamp('publicado_em')->nullable();
            $table->string('status')->default('rascunho');

            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index('data_inicio');
            $table->index('publicado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemplos');
    }
};
