<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->unsignedInteger('nivel')->default(0)->index()->after('guard_name');
            $table->text('descricao')->nullable()->after('nivel');
        });
    }

    public function down(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->dropColumn(['nivel', 'descricao']);
        });
    }
};
