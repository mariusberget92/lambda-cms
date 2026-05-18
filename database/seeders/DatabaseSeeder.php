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

        $allPermissions = [
            'view posts', 'create posts', 'edit own posts', 'edit any post',
            'delete own posts', 'delete any post', 'publish posts',
            'view pages', 'create pages', 'edit pages', 'delete pages',
            'view templates', 'create templates', 'edit templates', 'delete templates',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view tags', 'create tags', 'edit tags', 'delete tags',
            'view media', 'upload media', 'edit own media', 'edit any media',
            'delete own media', 'delete any media',
            'view comments', 'moderate comments', 'reply to comments', 'delete comments',
            'view users', 'create users', 'edit users', 'delete users', 'ban users',
            'manage roles', 'manage settings', 'manage navigation', 'manage webhooks',
        ];

        foreach ($allPermissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $adminRole       = Role::firstOrCreate(['name' => 'administrator']);
        $editorRole      = Role::firstOrCreate(['name' => 'editor']);
        $authorRole      = Role::firstOrCreate(['name' => 'author']);
        $contributorRole = Role::firstOrCreate(['name' => 'contributor']);
        $userRole        = Role::firstOrCreate(['name' => 'user']);

        // Administrator: everything
        $adminRole->syncPermissions(Permission::all());

        // Editor: all content + media + comments + navigation (no users/roles/settings/webhooks)
        $editorRole->syncPermissions([
            'view posts', 'create posts', 'edit own posts', 'edit any post',
            'delete own posts', 'delete any post', 'publish posts',
            'view pages', 'create pages', 'edit pages', 'delete pages',
            'view templates', 'create templates', 'edit templates', 'delete templates',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view tags', 'create tags', 'edit tags', 'delete tags',
            'view media', 'upload media', 'edit own media', 'edit any media',
            'delete own media', 'delete any media',
            'view comments', 'moderate comments', 'reply to comments', 'delete comments',
            'manage navigation',
        ]);

        // Author: own posts + publish + own media + view taxonomies
        $authorRole->syncPermissions([
            'view posts', 'create posts', 'edit own posts', 'delete own posts', 'publish posts',
            'view categories',
            'view tags',
            'view media', 'upload media', 'edit own media', 'delete own media',
        ]);

        // Contributor: draft-only posts (no publish) + own media + view taxonomies
        $contributorRole->syncPermissions([
            'view posts', 'create posts', 'edit own posts', 'delete own posts',
            'view categories',
            'view tags',
            'view media', 'upload media', 'edit own media', 'delete own media',
        ]);

        // User (legacy/default): equivalent to author with full taxonomy management
        $userRole->syncPermissions([
            'view posts', 'create posts', 'edit own posts', 'delete own posts', 'publish posts',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view tags', 'create tags', 'edit tags', 'delete tags',
            'view media', 'upload media', 'edit own media', 'delete own media',
        ]);

        // ── Admin user (roles must exist first) ──────────────────────────────
        $this->call(AdminSeeder::class);

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
