<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', static function (Blueprint $table): void {
            $table->foreignId('perfil_ativo_role_id')
                ->nullable()
                ->after('ativo')
                ->constrained('roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('perfil_ativo_role_id');
        });
    }
};
