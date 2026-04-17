<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
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

        // ── Seed media (featured images, generated locally via GD) ───────────
        $seedImages = $this->seedFeaturedImages($users);

        // ── Posts ─────────────────────────────────────────────────────────────
        $allUsers = $users->merge(User::where('email', 'admin@example.com')->get());

        // 60 published — every post gets a featured image (cycling through seed images)
        $imageIndex = 0;
        Post::factory()->count(60)->published()->make()->each(function ($post) use ($allUsers, $categories, $tags, $seedImages, &$imageIndex) {
            $post->user_id = $allUsers->random()->id;
            $post->featured = rand(0, 10) === 0; // ~10% featured

            if ($seedImages->isNotEmpty()) {
                $post->featured_image_id = $seedImages[$imageIndex % $seedImages->count()]->id;
                $imageIndex++;
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
     * Generate 10 placeholder JPEG images using PHP GD (no network required)
     * and create corresponding Media records.
     */
    private function seedFeaturedImages(\Illuminate\Support\Collection $users): \Illuminate\Support\Collection
    {
        $admin  = User::where('email', 'admin@example.com')->first() ?? $users->first();
        $images = collect();
        $folder = 'media/' . now()->format('Y/m');
        $width  = 1200;
        $height = 630;

        // 10 visually distinct background colours (Nord-ish palette)
        $palettes = [
            [46,  52,  64],   // dark navy
            [59,  66,  82],   // slate
            [67,  76,  94],   // steel
            [76,  86, 106],   // indigo-grey
            [136, 192, 208],  // frost blue
            [129, 161, 193],  // muted blue
            [163, 190, 140],  // sage green
            [235, 203, 139],  // warm gold
            [191,  97,  106], // rose
            [180, 142, 173],  // lavender
        ];

        foreach ($palettes as $i => [$r, $g, $b]) {
            $img = imagecreatetruecolor($width, $height);

            // Background
            $bg = imagecolorallocate($img, $r, $g, $b);
            imagefill($img, 0, 0, $bg);

            // Subtle darker band across the lower third (like a horizon)
            $band = imagecolorallocate($img, max(0, $r - 20), max(0, $g - 20), max(0, $b - 20));
            imagefilledrectangle($img, 0, (int) ($height * 0.65), $width, $height, $band);

            // Simple label so images are visually distinct in the media library
            $white = imagecolorallocate($img, 236, 239, 244);
            $label = "Seed image #" . ($i + 1);
            imagestring($img, 5, 24, 24, $label, $white);

            // Write PNG to a temp file, read back, then clean up
            $tmp = tempnam(sys_get_temp_dir(), 'seed_img_');
            imagepng($img, $tmp, 6);
            imagedestroy($img);
            $png = file_get_contents($tmp);
            unlink($tmp);

            $uuid     = Str::uuid()->toString();
            $filename = "{$uuid}.png";
            $path     = "{$folder}/{$filename}";

            Storage::disk('public')->put($path, $png);

            $media = Media::create([
                'user_id'           => $admin->id,
                'filename'          => $filename,
                'original_filename' => "placeholder-" . ($i + 1) . ".png",
                'disk'              => 'public',
                'path'              => $path,
                'mime_type'         => 'image/png',
                'type'              => 'image',
                'size'              => strlen($png),
                'width'             => $width,
                'height'            => $height,
                'alt'               => "Placeholder image " . ($i + 1),
            ]);

            $images->push($media);
        }

        return $images;
    }
}
