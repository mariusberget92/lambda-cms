<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'mariusberget92@protonmail.com'],
            [
                'name'              => 'Administrator',
                'password'          => Hash::make('lambdacms'),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('administrator')) {
            $user->assignRole('administrator');
        }

        // Seed a regular test user
        $testUser = User::firstOrCreate(
            ['email' => 'test@lambdacms.test'],
            [
                'name'              => 'Test User',
                'password'          => Hash::make('lambdacms'),
                'email_verified_at' => now(),
            ]
        );

        if (! $testUser->hasRole('user')) {
            $testUser->assignRole('user');
        }

        // Write the installed lockfile so the app skips the install wizard
        file_put_contents(storage_path('app/installed'), now()->toDateTimeString());
    }
}
