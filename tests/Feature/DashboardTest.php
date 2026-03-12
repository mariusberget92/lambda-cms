<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_includes_scheduled_count(): void
    {
        $user = $this->makeUser();
        Post::factory()->create(['user_id' => $user->id, 'status' => 'scheduled', 'published_at' => now()->addDay()]);
        Post::factory()->create(['user_id' => $user->id, 'status' => 'scheduled', 'published_at' => now()->addDay()]);
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Index')
                ->where('stats.scheduled', 2)
            );
    }

    public function test_dashboard_upcoming_scheduled_posts_ordered_by_publish_date(): void
    {
        $user   = $this->makeUser();
        $later  = Post::factory()->create(['user_id' => $user->id, 'status' => 'scheduled', 'published_at' => now()->addDays(3)]);
        $sooner = Post::factory()->create(['user_id' => $user->id, 'status' => 'scheduled', 'published_at' => now()->addDay()]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('upcoming_scheduled', 2)
                ->where('upcoming_scheduled.0.id', $sooner->id)
                ->where('upcoming_scheduled.1.id', $later->id)
            );
    }

    public function test_dashboard_upcoming_scheduled_posts_limited_to_five(): void
    {
        $user = $this->makeUser();
        Post::factory()->count(7)->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('upcoming_scheduled', 5));
    }

    public function test_dashboard_upcoming_scheduled_excludes_past_scheduled(): void
    {
        $user = $this->makeUser();
        Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->subHour(),
        ]);
        $future = Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('upcoming_scheduled', 1)
                ->where('upcoming_scheduled.0.id', $future->id)
            );
    }

    public function test_dashboard_recent_posts_ordered_by_updated_at(): void
    {
        $user  = $this->makeUser();
        $older = Post::factory()->create([
            'user_id'    => $user->id,
            'updated_at' => now()->subMinutes(10),
        ]);
        $newer = Post::factory()->create([
            'user_id'    => $user->id,
            'updated_at' => now()->subMinutes(5),
        ]);
        // Touch $older so its updated_at is now the most recent
        $older->update(['title' => 'Recently touched post', 'updated_at' => now()]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('recent_posts.0.id', $older->id)
                ->where('recent_posts.1.id', $newer->id)
            );
    }

    public function test_dashboard_recent_posts_limited_to_five(): void
    {
        $user = $this->makeUser();
        Post::factory()->count(7)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('recent_posts', 5));
    }

    public function test_dashboard_empty_upcoming_and_recent_when_no_posts(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('upcoming_scheduled', 0)
                ->has('recent_posts', 0)
            );
    }
}
