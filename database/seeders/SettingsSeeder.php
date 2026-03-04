<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Site
            ['group' => 'site', 'key' => 'site.name',  'value' => config('app.name', 'Lambda CMS'), 'type' => 'string'],
            ['group' => 'site', 'key' => 'site.url',   'value' => config('app.url',  'http://localhost'), 'type' => 'string'],

            // Locale
            ['group' => 'locale', 'key' => 'locale.timezone',    'value' => 'UTC',    'type' => 'string'],
            ['group' => 'locale', 'key' => 'locale.date_format', 'value' => 'Y-m-d',  'type' => 'string'],

            // Media
            ['group' => 'media', 'key' => 'media.max_upload_mb',    'value' => '10',   'type' => 'integer'],
            ['group' => 'media', 'key' => 'media.resize_max_width', 'value' => '1920', 'type' => 'integer'],

            // Mail
            ['group' => 'mail', 'key' => 'mail.driver',       'value' => 'smtp', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.host',         'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.port',         'value' => '587',  'type' => 'integer'],
            ['group' => 'mail', 'key' => 'mail.username',     'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.password',     'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.from_address', 'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.from_name',    'value' => config('app.name', 'Lambda CMS'), 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.encryption',   'value' => 'tls',  'type' => 'string'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->insertOrIgnore($setting + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
