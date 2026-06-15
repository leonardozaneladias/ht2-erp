<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete (lixeira) para admin_users. O login do usuário excluído é
 * automaticamente bloqueado pelo SoftDeletingScope. O índice unique de e-mail
 * vira PARCIAL (só ativos): libera o e-mail de um usuário na lixeira para novo
 * cadastro (D3); a restauração checa colisão com um ativo. Ver ComLixeira.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->softDeletes();
            $table->dropUnique('admin_users_email_unique');
        });

        DB::statement('CREATE UNIQUE INDEX admin_users_email_unique ON admin_users (email) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS admin_users_email_unique');

        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->unique('email', 'admin_users_email_unique');
        });
    }
};
