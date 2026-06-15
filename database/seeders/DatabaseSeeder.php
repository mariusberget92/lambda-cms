<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        // ── Roles & Permissions ──────────────────────────────────────────────
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['manage posts', 'manage categories', 'manage tags', 'manage users'] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions(['manage posts', 'manage categories', 'manage tags']);
    }
}
