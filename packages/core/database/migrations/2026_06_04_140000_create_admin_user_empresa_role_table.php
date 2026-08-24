<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Atribuição de papel (role) a um usuário no escopo de uma empresa.
        // Papéis globais (super-admin, plataforma) seguem em model_has_roles (spatie);
        // esta tabela é a dimensão por empresa: gestor na Empresa A, operador na B.
        Schema::create('admin_user_empresa_role', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_user_id', 'empresa_id', 'role_id'], 'admin_user_empresa_role_unique');
            $table->index(['admin_user_id', 'empresa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_empresa_role');
    }
};
