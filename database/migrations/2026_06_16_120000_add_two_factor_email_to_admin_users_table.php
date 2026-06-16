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
            // 2FA por e-mail: método alternativo de segundo fator. A preferência
            // é durável (coluna); o código enviado é efêmero (cache com TTL).
            $table->boolean('two_factor_email_enabled')->default(false)->after('two_factor_last_timestamp');
            $table->timestamp('two_factor_email_enabled_at')->nullable()->after('two_factor_email_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_email_enabled', 'two_factor_email_enabled_at']);
        });
    }
};
