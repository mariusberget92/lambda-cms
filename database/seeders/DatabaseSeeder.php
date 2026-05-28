<?php

namespace Database\Seeders;

use App\Models\User;
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

        // ── Admin user (roles must exist first) ──────────────────────────────
        $this->call(AdminSeeder::class);

        // ── Default templates ────────────────────────────────────────────────
        $this->call(TemplateSeeder::class);

        // ── Lambda CMS showcase content (all environments) ───────────────────
        $this->call(LambdaContentSeeder::class);

        // ── Dev fixtures (extra test users, posts, categories, tags) ─────────
        if (app()->environment('local')) {
            $this->call(DevSeeder::class);
        }
    }

    /**
     * Seed real Lambda CMS showcase posts.
     * Called by InstallController after the admin user has been created.
     */
    public function seedDefaultPost(User $adminUser): void
    {
        app(LambdaContentSeeder::class)->run();
    }
}
