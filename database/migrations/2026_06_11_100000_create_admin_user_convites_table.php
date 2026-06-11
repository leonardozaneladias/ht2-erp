<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_user_convites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            // SHA-256 do token; o token em claro só existe no e-mail enviado.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expira_em');
            $table->timestamp('aceito_em')->nullable();
            $table->foreignId('enviado_por')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['admin_user_id', 'aceito_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_convites');
    }
};
