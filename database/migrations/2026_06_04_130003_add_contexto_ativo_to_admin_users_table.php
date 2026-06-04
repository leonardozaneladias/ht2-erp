<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->foreignId('empresa_ativa_id')->nullable()->after('perfil_ativo_role_id')
                ->constrained('empresas')->nullOnDelete();
            $table->foreignId('filial_ativa_id')->nullable()->after('empresa_ativa_id')
                ->constrained('filiais')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('empresa_ativa_id');
            $table->dropConstrainedForeignId('filial_ativa_id');
        });
    }
};
