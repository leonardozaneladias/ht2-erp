<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', static function (Blueprint $table): void {
            $table->string('modulo')->nullable()->index()->after('guard_name');
            $table->string('label')->nullable()->after('modulo');
            $table->text('descricao')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', static function (Blueprint $table): void {
            $table->dropColumn(['modulo', 'label', 'descricao']);
        });
    }
};
