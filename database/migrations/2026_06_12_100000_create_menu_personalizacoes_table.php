<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_personalizacoes', function (Blueprint $table): void {
            $table->id();

            $table->string('tipo', 10);
            $table->string('key', 100);

            // Campos null = herdam o padrão do config/admin-menu.php.
            $table->string('label', 80)->nullable();
            $table->string('icone', 60)->nullable();
            $table->string('secao_key', 100)->nullable();
            $table->unsignedInteger('ordem')->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['tipo', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_personalizacoes');
    }
};
