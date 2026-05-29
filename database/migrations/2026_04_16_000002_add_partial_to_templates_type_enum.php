<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the constrained 'type' enum column (missing 'partial') with a
     * plain string so that 'partial' templates can be stored.
     *
     * SQLite does not support ALTER COLUMN, so the approach is:
     *   1. Drop the composite index on (type, status) — blocks the column drop
     *   2. Swap the column via a temp column
     *   3. Recreate the index
     *
     * Validation remains enforced at the application layer.
     */
    public function up(): void
    {
        // Drop the composite index only if it still exists
        $indexes = DB::select('PRAGMA index_list("templates")');
        $indexNames = array_column($indexes, 'name');

        if (in_array('templates_type_status_index', $indexNames)) {
            Schema::table('templates', function (Blueprint $table) {
                $table->dropIndex(['type', 'status']);
            });
        }

        // Only swap if type column is still an enum (check constraint present)
        // We detect this by checking if type_new already exists (partial run)
        $columns = array_column(DB::select('PRAGMA table_info("templates")'), 'name');

        if (! in_array('type_new', $columns)) {
            // Add temp column, copy, drop old, rename
            Schema::table('templates', function (Blueprint $table) {
                $table->string('type_new', 64)->nullable()->after('type');
            });

            DB::statement('UPDATE "templates" SET "type_new" = "type"');

            Schema::table('templates', function (Blueprint $table) {
                $table->dropColumn('type');
            });

            Schema::table('templates', function (Blueprint $table) {
                $table->renameColumn('type_new', 'type');
            });
        }

        // Recreate the composite index if it's missing
        $indexes = DB::select('PRAGMA index_list("templates")');
        $indexNames = array_column($indexes, 'name');

        if (! in_array('templates_type_status_index', $indexNames)) {
            Schema::table('templates', function (Blueprint $table) {
                $table->index(['type', 'status']);
            });
        }
    }

    public function down(): void
    {
        // Restore the enum constraint — not critical for rollback in SQLite dev
        // The column remains a plain string; the app layer enforces values.
    }
};
