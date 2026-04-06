<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        // ── Seed media (featured images) ──────────────────────────────────────
        $seedImages = $this->seedFeaturedImages($users);

        // ── Posts ─────────────────────────────────────────────────────────────
        $allUsers = $users->merge(User::where('email', 'admin@example.com')->get());

        // 60 published — ~40% get a featured image
        Post::factory()->count(60)->published()->make()->each(function ($post) use ($allUsers, $categories, $tags, $seedImages) {
            $post->user_id = $allUsers->random()->id;
            $post->featured = rand(0, 10) === 0; // ~10% featured

            if ($seedImages->isNotEmpty() && rand(0, 9) < 4) {
                $post->featured_image_id = $seedImages->random()->id;
            }

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

    /**
     * Download placeholder images from picsum.photos and create Media records.
     * Returns a collection of Media models. Gracefully returns an empty collection
     * if the network is unavailable.
     */
    private function seedFeaturedImages(\Illuminate\Support\Collection $users): \Illuminate\Support\Collection
    {
        $admin  = User::where('email', 'admin@example.com')->first() ?? $users->first();
        $images = collect();

        // 10 deterministic placeholder images (800×500, unique seeds)
        $seeds = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
        $folder = 'media/' . now()->format('Y/m');

        foreach ($seeds as $seed) {
            try {
                $response = Http::timeout(10)->get("https://picsum.photos/seed/{$seed}/1200/630");

                if (! $response->successful()) {
                    continue;
                }

                $uuid     = Str::uuid()->toString();
                $filename = "{$uuid}.jpg";
                $path     = "{$folder}/{$filename}";

                Storage::disk('public')->put($path, $response->body());

                $media = Media::create([
                    'user_id'           => $admin->id,
                    'filename'          => $filename,
                    'original_filename' => "placeholder-{$seed}.jpg",
                    'disk'              => 'public',
                    'path'              => $path,
                    'mime_type'         => 'image/jpeg',
                    'type'              => 'image',
                    'size'              => Storage::disk('public')->size($path),
                    'width'             => 1200,
                    'height'            => 630,
                    'alt'               => "Placeholder image {$seed}",
                ]);

                $images->push($media);
            } catch (\Throwable) {
                // Network unavailable — skip silently, posts just won't have featured images
            }
        }

        return $images;
    }
}
