<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
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

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    // Task 1: Model structure

    public function test_comment_belongs_to_post(): void
    {
        $post    = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->assertTrue($comment->post->is($post));
    }

    public function test_post_has_many_comments(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory(3)->create(['post_id' => $post->id]);

        $this->assertCount(3, $post->comments);
    }
}
