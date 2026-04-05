<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        // ── Test users (password: "password") ────────────────────────────────
        $adminRole = Role::findByName('administrator');
        $userRole  = Role::findByName('user');

        $users = collect();

        // 2 extra admins
        User::factory()->count(2)->create()->each(function ($u) use ($adminRole, &$users) {
            $u->assignRole($adminRole);
            $users->push($u);
        });

        // 5 regular users
        User::factory()->count(5)->create()->each(function ($u) use ($userRole, &$users) {
            $u->assignRole($userRole);
            $users->push($u);
        });

        // ── Categories ────────────────────────────────────────────────────────
        $colors = ['#88c0d0', '#81a1c1', '#a3be8c', '#ebcb8b', '#bf616a', '#b48ead', '#8fbcbb'];

        $categories = Category::factory()->count(10)->make()->each(function ($cat, $i) use ($colors) {
            $cat->color = $colors[$i % count($colors)];
            $cat->save();
        });

        // ── Tags ──────────────────────────────────────────────────────────────
        $tags = Tag::factory()->count(20)->create();

        // ── Posts ─────────────────────────────────────────────────────────────
        $allUsers = $users->merge(User::where('email', 'admin@example.com')->get());

        // 60 published
        Post::factory()->count(60)->published()->make()->each(function ($post) use ($allUsers, $categories, $tags) {
            $post->user_id = $allUsers->random()->id;
            $post->featured = rand(0, 10) === 0; // ~10% featured
            $post->save();
            $post->categories()->sync($categories->random(rand(1, 3))->pluck('id'));
            $post->tags()->sync($tags->random(rand(1, 5))->pluck('id'));
        });

        // 25 drafts
        Post::factory()->count(25)->draft()->make()->each(function ($post) use ($allUsers, $categories, $tags) {
            $post->user_id = $allUsers->random()->id;
            $post->save();
            $post->categories()->sync($categories->random(rand(1, 2))->pluck('id'));
            $post->tags()->sync($tags->random(rand(0, 3))->pluck('id'));
        });

        // 15 scheduled
        Post::factory()->count(15)->state([
            'status'       => 'scheduled',
            'published_at' => now()->addDays(rand(1, 60)),
        ])->make()->each(function ($post) use ($allUsers, $categories, $tags) {
            $post->user_id = $allUsers->random()->id;
            $post->save();
            $post->categories()->sync($categories->random(rand(1, 2))->pluck('id'));
            $post->tags()->sync($tags->random(rand(1, 4))->pluck('id'));
        });
    }
}
