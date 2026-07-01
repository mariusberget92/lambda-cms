<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'manage email']);

        $admin = Role::firstOrCreate(['name' => 'administrator']);
        if (! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'manage email')->delete();
    }
};
