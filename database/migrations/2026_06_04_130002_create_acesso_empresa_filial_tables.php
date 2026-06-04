<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Acesso do usuário à empresa. `todas_filiais` = acesso a todas as filiais
        // da empresa; quando false, o acesso é restrito às filiais em admin_user_filial.
        Schema::create('admin_user_empresa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->boolean('todas_filiais')->default(true);
            $table->timestamps();

            $table->unique(['admin_user_id', 'empresa_id']);
        });

        // Filiais específicas que o usuário acessa (quando todas_filiais = false).
        Schema::create('admin_user_filial', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('filial_id')->constrained('filiais')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_user_id', 'filial_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_filial');
        Schema::dropIfExists('admin_user_empresa');
    }
};
