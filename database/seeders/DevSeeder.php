<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::findByName('administrator');
        $userRole  = Role::findByName('user');

        // 2 extra admin accounts for local testing (password: "password")
        User::factory()->count(2)->create()->each(fn ($u) => $u->assignRole($adminRole));

        // 5 regular user accounts
        User::factory()->count(5)->create()->each(fn ($u) => $u->assignRole($userRole));
    }
}
