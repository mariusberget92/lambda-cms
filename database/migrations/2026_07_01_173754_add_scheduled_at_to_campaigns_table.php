<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaigns') && ! Schema::hasColumn('campaigns', 'scheduled_at')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('campaigns', 'scheduled_at')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('scheduled_at');
            });
        }
    }
};
