<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    // ── Access ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_profile(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $this->actingAs($this->makeUser())->get('/profile')->assertOk();
    }

    // ── Info update ───────────────────────────────────────────────────────────

    public function test_user_can_update_name(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/profile/info', [
            'name' => 'New Name',
            'email' => $user->email,
        ])->assertRedirect('/profile');

        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_changing_email_nulls_email_verified_at(): void
    {
        $user = $this->makeUser();
        $this->assertNotNull($user->email_verified_at);

        $this->actingAs($user)->post('/profile/info', [
            'name' => $user->name,
            'email' => 'newemail@example.com',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_keeping_same_email_preserves_verification(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/profile/info', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_info_update_validates_unique_email(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $this->actingAs($user)->post('/profile/info', [
            'name' => $user->name,
            'email' => $other->email,
        ])->assertSessionHasErrors('email');
    }

    // ── Password update ───────────────────────────────────────────────────────

    public function test_user_can_update_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertRedirect('/profile');
    }

    public function test_password_update_requires_correct_current_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_password_update_requires_confirmation(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword1',
            'password_confirmation' => 'doesnotmatch',
        ])->assertSessionHasErrors('password');
    }

    // ── Avatar ────────────────────────────────────────────────────────────────

    public function test_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        // Use create() with mime type — avoids GD requirement of image()
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => $file,
        ])->assertRedirect('/profile');

        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_avatar_upload_replaces_previous_avatar(): void
    {
        Storage::fake('public');

        // Create two users so the avatars get different filenames (based on user ID)
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();

        // Upload avatar for user1 then manually set it as user2's avatar
        // so user2 has an "old" avatar with a different path than their own ID
        $firstFile = UploadedFile::fake()->create('old.jpg', 100, 'image/jpeg');
        $oldPath = $firstFile->storeAs('avatars', $user2->id.'_old.jpg', 'public');
        $user2->forceFill(['avatar' => $oldPath])->save();
        Storage::disk('public')->assertExists($oldPath);

        // Now upload a new avatar for user2 — old file should be cleaned up
        $newFile = UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg');
        $this->actingAs($user2)->post('/profile/avatar', ['avatar' => $newFile]);

        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNotNull($user2->fresh()->avatar);
    }

    public function test_avatar_upload_validates_image_type(): void
    {
        $user = $this->makeUser();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => $file,
        ])->assertSessionHasErrors('avatar');
    }

    public function test_user_can_delete_avatar(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $this->actingAs($user)->post('/profile/avatar', ['avatar' => $file]);
        $avatarPath = $user->fresh()->avatar;

        $this->actingAs($user)->delete('/profile/avatar')->assertRedirect('/profile');

        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing($avatarPath);
    }
}
