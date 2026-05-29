<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
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

    // ── Access ────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_calendar(): void
    {
        $this->get('/calendar')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_calendar(): void
    {
        $this->actingAs($this->makeUser())->get('/calendar')->assertOk();
    }

    // ── Data endpoint ─────────────────────────────────────────────────────────

    public function test_data_endpoint_returns_grouped_and_unscheduled_drafts_keys(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk()
            ->assertJsonStructure(['grouped', 'unscheduled_drafts']);
    }

    public function test_data_endpoint_defaults_to_current_month(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data')
            ->assertOk()
            ->assertJsonStructure(['grouped', 'unscheduled_drafts']);
    }

    public function test_data_endpoint_rejects_invalid_month_format(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data?month=not-a-month')
            ->assertStatus(422);
    }

    public function test_data_endpoint_rejects_out_of_range_year(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->getJson('/calendar/data?month=9999-01')
            ->assertStatus(422);

        $this->actingAs($user)
            ->getJson('/calendar/data?month=1999-01')
            ->assertStatus(422);
    }

    // ── Admin sees all posts ──────────────────────────────────────────────────

    public function test_admin_grouped_contains_all_statuses(): void
    {
        $admin = $this->makeAdmin();
        $otherUser = $this->makeUser();

        $month = '2026-03';
        $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->addDays(5);

        Post::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'published',
            'published_at' => $date,
        ]);
        Post::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'scheduled',
            'published_at' => $date->copy()->addDay(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/calendar/data?month={$month}")
            ->assertOk();

        $grouped = $response->json('grouped');
        $allPosts = collect($grouped)->flatten(1);
        $this->assertCount(2, $allPosts);
    }

    public function test_unscheduled_drafts_is_empty_when_none_exist(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk()
            ->assertJson(['unscheduled_drafts' => []]);
    }

    public function test_admin_unscheduled_drafts_includes_all_users_drafts(): void
    {
        $admin = $this->makeAdmin();
        $otherUser = $this->makeUser();

        Post::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk();

        $this->assertCount(1, $response->json('unscheduled_drafts'));
    }

    // ── Regular user scoping ──────────────────────────────────────────────────

    public function test_regular_user_grouped_excludes_other_users_drafts_and_scheduled(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();

        $month = '2026-03';
        $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->addDays(3);

        // Own published post — should appear
        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => $date,
        ]);
        // Other user's draft with published_at — should be excluded
        Post::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'draft',
            'published_at' => $date->copy()->addDay(),
        ]);
        // Other user's scheduled post — should be excluded
        Post::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'scheduled',
            'published_at' => $date->copy()->addDays(2),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/calendar/data?month={$month}")
            ->assertOk();

        $grouped = $response->json('grouped');
        $allPosts = collect($grouped)->flatten(1);
        $this->assertCount(1, $allPosts);
        $this->assertEquals('published', $allPosts->first()['status']);
    }

    public function test_regular_user_unscheduled_drafts_excludes_other_users_drafts(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();

        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        Post::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk();

        $this->assertCount(1, $response->json('unscheduled_drafts'));
    }
}
