<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip if column already exists
        if (Schema::hasColumn('templates', 'is_system')) {
            return;
        }

        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('loop_source');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('templates', 'is_system')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
