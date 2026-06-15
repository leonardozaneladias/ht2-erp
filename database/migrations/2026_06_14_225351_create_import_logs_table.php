<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->string('arquivo_original', 255);
            $table->integer('total_linhas')->default(0);
            $table->integer('linhas_importadas')->default(0);
            $table->integer('linhas_com_erro')->default(0);
            $table->string('status', 20)->default('pendente');
            $table->json('erros')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'tipo']);
            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
