<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
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

        // ── Admin user (dev only — production installs use the wizard) ───────
        if (app()->environment('local')) {
            $this->call(AdminSeeder::class);
        }

        // ── Default templates ────────────────────────────────────────────────
        $this->call(TemplateSeeder::class);

        // ── Dev fixtures (test users, posts, categories, tags) ───────────────
        if (app()->environment('local')) {
            $this->call(DevSeeder::class);
        }
    }

    /**
     * Seed a default "Hello World" post assigned to the given admin user.
     * Called by InstallController after the admin user has been created.
     */
    public function seedDefaultPost(User $adminUser): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'General'],
            [
                'slug'        => Category::generateSlug('General'),
                'description' => 'General posts and announcements.',
            ],
        );

        $post = Post::create([
            'user_id'      => $adminUser->id,
            'title'        => 'Hello World',
            'slug'         => 'hello-world',
            'excerpt'      => 'Welcome to Lambda CMS — your new content management system is ready.',
            'body'         => '<h2>Welcome to Lambda CMS!</h2><p>You\'ve successfully installed Lambda CMS. This is your first post. You can edit or delete it, then start writing your own content.</p><p>Head over to the <a href="/dashboard">dashboard</a> to get started.</p>',
            'status'       => 'published',
            'published_at' => now(),
        ]);
        $post->categories()->sync([$category->id]);
    }
}
