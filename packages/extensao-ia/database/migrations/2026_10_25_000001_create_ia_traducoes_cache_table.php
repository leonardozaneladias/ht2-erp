<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ia_traducoes_cache', function (Blueprint $table): void {
            $table->id();
            // Referência, sem FK nem scope — ver TraducaoEmCache.
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->char('hash_conteudo', 64)->comment('sha256 do texto original');
            $table->string('idioma_origem', 5);
            $table->string('idioma_destino', 5);
            $table->mediumText('texto_traduzido');
            // Sem default: quem traduziu é sempre declarado pelo provedor.
            $table->string('provedor', 40);
            $table->timestamps();

            $table->unique(['hash_conteudo', 'idioma_destino']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ia_traducoes_cache');
    }
};
