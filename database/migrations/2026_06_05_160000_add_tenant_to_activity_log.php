<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            // Sem FK: o log é append-only e deve sobreviver à exclusão da empresa/filial.
            $table->unsignedBigInteger('empresa_id')->nullable()->after('properties')->index();
            $table->unsignedBigInteger('filial_id')->nullable()->after('empresa_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn(['empresa_id', 'filial_id']);
        });
    }
};
