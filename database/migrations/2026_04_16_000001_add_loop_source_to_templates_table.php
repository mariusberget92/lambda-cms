<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            // Determines which loop source's fields appear in the block editor
            // binding dropdowns when editing this template (design-time only).
            $table->string('loop_source', 32)->nullable()->default('posts')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('loop_source');
        });
    }
};
