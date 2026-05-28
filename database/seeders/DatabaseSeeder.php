<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        // ── Roles & Permissions ──────────────────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['manage posts', 'manage categories', 'manage tags', 'manage users'] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator']);
        $userRole  = Role::firstOrCreate(['name' => 'user']);

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions(['manage posts', 'manage categories', 'manage tags']);

        // ── Default templates ────────────────────────────────────────────────
        $this->call(TemplateSeeder::class);

        // ── Dev fixtures + showcase content (local only) ─────────────────────
        // LambdaContentSeeder requires an administrator to exist; DevSeeder
        // creates those users, so it must run first. In production the
        // installer calls LambdaContentSeeder directly after creating the
        // admin account (see InstallController::mail).
        if (app()->environment('local')) {
            $this->call(DevSeeder::class);
            $this->call(LambdaContentSeeder::class);
        }
    }

}
