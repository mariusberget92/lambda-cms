<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles & Permissions ──────────────────────────────────────────────
        foreach (['manage posts', 'manage categories', 'manage tags', 'manage users'] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator']);
        $userRole  = Role::firstOrCreate(['name' => 'user']);

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions(['manage posts', 'manage categories', 'manage tags']);

        // ── Admin user ───────────────────────────────────────────────────────
        $admin = User::factory()->create([
            'name'              => 'Admin',
            'email'             => 'admin@lambda.test',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('administrator');

        // ── Categories ───────────────────────────────────────────────────────
        $categories = collect([
            ['name' => 'Technology',  'description' => 'Articles about software, hardware and the digital world.'],
            ['name' => 'Design',      'description' => 'UI/UX, visual design and creative process.'],
            ['name' => 'Business',    'description' => 'Entrepreneurship, strategy and growth.'],
            ['name' => 'Tutorials',   'description' => 'Step-by-step guides and how-tos.'],
        ])->map(fn ($data) => Category::factory()->create($data));

        // ── Tags ─────────────────────────────────────────────────────────────
        $tags = collect(['Laravel', 'Vue.js', 'Tailwind CSS', 'JavaScript', 'PHP', 'Open Source', 'Productivity', 'Career'])
            ->map(fn ($name) => Tag::factory()->create(['name' => $name]));

        // ── Posts ────────────────────────────────────────────────────────────
        Post::factory(5)->published()->create(['user_id' => $admin->id])
            ->each(function (Post $post) use ($categories, $tags) {
                $post->update(['category_id' => $categories->random()->id]);
                $post->tags()->sync($tags->random(rand(1, 3))->pluck('id'));
            });

        Post::factory(3)->draft()->create(['user_id' => $admin->id])
            ->each(function (Post $post) use ($categories, $tags) {
                $post->update(['category_id' => $categories->random()->id]);
                $post->tags()->sync($tags->random(rand(1, 2))->pluck('id'));
            });
    }
}
