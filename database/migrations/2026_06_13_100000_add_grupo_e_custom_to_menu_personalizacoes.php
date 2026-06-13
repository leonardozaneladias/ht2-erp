<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_personalizacoes', function (Blueprint $table): void {
            // Item dentro de grupo (se preenchida, a seção vem do grupo).
            $table->string('grupo_key', 100)->nullable()->after('secao_key');

            // Seções e grupos criados pela tela (sem âncora no config —
            // nunca são tratados como personalização órfã).
            $table->boolean('e_custom')->default(false)->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('menu_personalizacoes', function (Blueprint $table): void {
            $table->dropColumn(['grupo_key', 'e_custom']);
        });
    }
};
