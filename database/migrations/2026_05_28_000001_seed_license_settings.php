<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['group' => 'license', 'key' => 'license.key',          'value' => '',         'type' => 'string'],
            ['group' => 'license', 'key' => 'license.status',       'value' => 'inactive', 'type' => 'string'],
            ['group' => 'license', 'key' => 'license.activated_at', 'value' => '',         'type' => 'string'],
        ];

        foreach ($rows as $row) {
            DB::table('settings')->insertOrIgnore($row + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'license.key',
            'license.status',
            'license.activated_at',
        ])->delete();
    }
};
