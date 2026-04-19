<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BanTest extends TestCase
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

    // ── Ban ───────────────────────────────────────────────────────────────────

    public function test_admin_can_ban_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)->post("/users/{$user->id}/ban", [
            'reason'   => 'Spamming',
            'duration' => '24h',
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->banned_at);
        $this->assertEquals('Spamming', $user->ban_reason);
        $this->assertNotNull($user->banned_until);
    }

    public function test_admin_can_permanently_ban_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)->post("/users/{$user->id}/ban", [
            'reason'   => 'Repeated violations',
            'duration' => 'permanent',
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->banned_at);
        $this->assertNull($user->banned_until);
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeAdmin();

        $this->actingAs($admin)->post("/users/{$target->id}/ban", [
            'reason'   => 'Test',
            'duration' => '24h',
        ])->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertNull($target->banned_at);
    }

    public function test_admin_cannot_ban_themselves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post("/users/{$admin->id}/ban", [
            'reason'   => 'Test',
            'duration' => '24h',
        ])->assertRedirect(route('users.index'));

        $admin->refresh();
        $this->assertNull($admin->banned_at);
    }

    public function test_regular_user_cannot_ban(): void
    {
        $user   = $this->makeUser();
        $target = $this->makeUser();

        $this->actingAs($user)->post("/users/{$target->id}/ban", [
            'reason'   => 'Test',
            'duration' => '24h',
        ])->assertRedirect(route('dashboard'));
    }

    // ── Unban ─────────────────────────────────────────────────────────────────

    public function test_admin_can_unban_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $user->update(['banned_at' => now(), 'banned_until' => null, 'ban_reason' => 'Spam']);

        $this->actingAs($admin)->delete("/users/{$user->id}/ban")->assertRedirect();

        $user->refresh();
        $this->assertNull($user->banned_at);
        $this->assertNull($user->ban_reason);
    }

    public function test_regular_user_cannot_unban(): void
    {
        $user   = $this->makeUser();
        $target = $this->makeUser();
        $target->update(['banned_at' => now(), 'banned_until' => null, 'ban_reason' => 'Spam']);

        $this->actingAs($user)->delete("/users/{$target->id}/ban")->assertRedirect(route('dashboard'));

        $target->refresh();
        $this->assertNotNull($target->banned_at);
    }

    public function test_ban_validates_required_fields(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)->post("/users/{$user->id}/ban", [])
            ->assertSessionHasErrors(['reason', 'duration']);
    }
}
