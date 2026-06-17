<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionarios', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('nome');
            $table->string('cpf', 14);
            $table->string('cargo');
            $table->integer('salario');
            $table->date('admissao');
            $table->string('status')->default('ativo');

            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index('admissao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};
