<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
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

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_users_index(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_users_index(): void
    {
        // Spatie role middleware redirects non-admins to dashboard instead of 403
        $this->actingAs($this->makeUser())->get('/users')->assertRedirect(route('dashboard'));
    }

    public function test_administrator_can_access_users_index(): void
    {
        $this->actingAs($this->makeAdmin())->get('/users')->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_administrator_can_create_a_user(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/users', [
            'name'  => 'New User',
            'email' => 'newuser@example.com',
            'role'  => 'user',
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_regular_user_cannot_create_a_user(): void
    {
        // Spatie role middleware redirects to dashboard rather than returning 403
        $this->actingAs($this->makeUser())->post('/users', [
            'name'  => 'Hacker',
            'email' => 'hacker@example.com',
            'role'  => 'user',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_store_validates_unique_email(): void
    {
        $admin    = $this->makeAdmin();
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)->post('/users', [
            'name'  => 'Another User',
            'email' => 'taken@example.com',
            'role'  => 'user',
        ])->assertSessionHasErrors('email');
    }

    public function test_store_validates_role_value(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/users', [
            'name'  => 'Test',
            'email' => 'test@example.com',
            'role'  => 'superuser',
        ])->assertSessionHasErrors('role');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_administrator_can_update_a_user(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)->put("/users/{$target->id}", [
            'name'  => 'Updated Name',
            'email' => $target->email,
            'role'  => 'user',
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Updated Name']);
    }

    public function test_cannot_remove_last_administrator_role(): void
    {
        $admin = $this->makeAdmin();
        // This is the only admin

        $this->actingAs($admin)->put("/users/{$admin->id}", [
            'name'  => $admin->name,
            'email' => $admin->email,
            'role'  => 'user',
        ])->assertRedirect('/users');

        // Role should NOT have been changed
        $this->assertTrue($admin->fresh()->hasRole('administrator'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_administrator_can_delete_a_user(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser();

        $this->actingAs($admin)->delete("/users/{$target->id}")->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_administrator_cannot_delete_their_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->delete("/users/{$admin->id}")->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_delete_last_administrator(): void
    {
        $admin = $this->makeAdmin();
        // Only one admin exists

        $this->actingAs($admin)->delete("/users/{$admin->id}");
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_regular_user_cannot_delete_a_user(): void
    {
        $user   = $this->makeUser();
        $target = $this->makeUser();

        // Spatie role middleware redirects to dashboard rather than returning 403
        $this->actingAs($user)->delete("/users/{$target->id}")->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
