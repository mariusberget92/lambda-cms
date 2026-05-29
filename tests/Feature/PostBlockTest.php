<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    public function test_edit_response_includes_block_editor_fields(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'use_block_editor' => false,
            'blocks' => null,
        ]);

        $this->actingAs($user)
            ->get("/posts/{$post->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Posts/Edit')
                ->where('post.use_block_editor', false)
                ->where('post.blocks', null)
            );
    }

    public function test_edit_response_includes_blocks_when_block_editor_enabled(): void
    {
        $user = $this->makeUser();
        $blocks = [['id' => 'abc', 'type' => 'heading', 'data' => ['level' => 1, 'text' => 'Hi']]];
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'use_block_editor' => true,
            'blocks' => $blocks,
        ]);

        $this->actingAs($user)
            ->get("/posts/{$post->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('post.use_block_editor', true)
                ->has('post.blocks')
            );
    }

    public function test_store_accepts_use_block_editor_and_blocks(): void
    {
        $user = $this->makeUser();
        $blocks = [['id' => 'x1', 'type' => 'paragraph', 'data' => ['content' => '<p>Hi</p>']]];

        $this->actingAs($user)->post('/posts', [
            'title' => 'Block Post',
            'status' => 'draft',
            'use_block_editor' => true,
            'blocks' => $blocks,
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', ['title' => 'Block Post', 'use_block_editor' => true]);
        $post = Post::where('title', 'Block Post')->first();
        $this->assertIsArray($post->blocks);
    }

    public function test_update_saves_block_editor_fields(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'use_block_editor' => false]);
        $blocks = [['id' => 'y1', 'type' => 'heading', 'data' => ['level' => 2, 'text' => 'Updated']]];

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title' => $post->title,
            'status' => 'draft',
            'use_block_editor' => true,
            'blocks' => $blocks,
        ])->assertRedirect(route('posts.edit', $post));

        $this->assertTrue((bool) $post->fresh()->use_block_editor);
        $this->assertIsArray($post->fresh()->blocks);
    }
}
