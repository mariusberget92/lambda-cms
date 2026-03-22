<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
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

        // Seed the default "Hello World" post
        $category = Category::firstOrCreate(
            ['name' => 'General'],
            [
                'slug'        => Category::generateSlug('General'),
                'description' => 'General posts and announcements.',
            ],
        );

        if (! Post::where('slug', 'hello-world')->exists()) {
            $post = Post::create([
                'user_id'      => $user->id,
                'title'        => 'Hello World',
                'slug'         => 'hello-world',
                'excerpt'      => 'Welcome to Lambda CMS — your new content management system is ready.',
                'body'         => '<h2>Welcome to Lambda CMS!</h2><p>You\'ve successfully installed Lambda CMS. This is your first post. You can edit or delete it, then start writing your own content.</p><p>Head over to the <a href="/dashboard">dashboard</a> to get started.</p>',
                'status'       => 'published',
                'published_at' => now(),
            ]);
            $post->categories()->sync([$category->id]);
        }

        // Write the installed lockfile so the app skips the install wizard
        file_put_contents(storage_path('app/installed'), now()->toDateTimeString());
    }
}
