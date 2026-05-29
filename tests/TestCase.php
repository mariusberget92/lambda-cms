<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Simulate an installed application by creating the lock file.
     */
    protected function markAsInstalled(): void
    {
        file_put_contents(storage_path('app/installed'), 'installed');
    }

    /**
     * Remove the installed lock file (revert to not-installed state).
     */
    protected function markAsNotInstalled(): void
    {
        $path = storage_path('app/installed');
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Seed roles & permissions required by Spatie permission checks.
     */
    protected function seedRolesAndPermissions(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['manage posts', 'manage categories', 'manage tags', 'manage users'] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $admin = Role::firstOrCreate(['name' => 'administrator']);
        $user = Role::firstOrCreate(['name' => 'user']);

        $admin->syncPermissions(Permission::all());
        $user->syncPermissions(['manage posts', 'manage categories', 'manage tags']);
    }

    protected function tearDown(): void
    {
        $this->markAsNotInstalled();
        parent::tearDown();
    }
}
