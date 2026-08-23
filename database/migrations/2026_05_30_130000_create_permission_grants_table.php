<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_grants', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();

            $table->string('type'); // HT2ML\Core\Enums\TipoConcessao: grant | deny
            $table->timestamp('expires_at')->nullable()->index();

            $table->foreignId('granted_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('reason'); // motivo obrigatório (auditoria)
            $table->timestamp('revoked_at')->nullable();

            // Gancho para escopos futuros (filial/unidade); global por enquanto.
            $table->string('contexto_tipo')->nullable();
            $table->unsignedBigInteger('contexto_id')->nullable();

            $table->timestamps();

            $table->index(['admin_user_id', 'type']);
            $table->index(['admin_user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_grants');
    }
};
