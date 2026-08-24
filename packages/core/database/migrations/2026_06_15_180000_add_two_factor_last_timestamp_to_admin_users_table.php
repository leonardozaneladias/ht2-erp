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
            // Contador da janela TOTP do último código aceito. Impede replay do
            // mesmo código dentro da janela de validade (verifyKeyNewer).
            $table->unsignedBigInteger('two_factor_last_timestamp')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropColumn('two_factor_last_timestamp');
        });
    }
};
