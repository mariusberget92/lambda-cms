<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    private function makeUserWithout(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    public function test_can_store_activity_on_contact(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/activities', [
                'subject_type' => 'contact',
                'subject_id' => $contact->id,
                'type' => 'call',
                'description' => 'Called about the deal',
                'occurred_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'subject_id' => $contact->id,
            'type' => 'call',
            'description' => 'Called about the deal',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/activities', [])
            ->assertSessionHasErrors(['subject_type', 'subject_id', 'type', 'description', 'occurred_at']);
    }

    public function test_user_without_permission_cannot_store_activity(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($this->makeUserWithout())
            ->post('/activities', [
                'subject_type' => 'contact',
                'subject_id' => $contact->id,
                'type' => 'note',
                'description' => 'A note',
                'occurred_at' => now()->toDateTimeString(),
            ])
            ->assertForbidden();
    }

    public function test_can_delete_activity(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);
        $activity = Activity::create([
            'user_id' => $admin->id,
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'type' => 'note',
            'description' => 'To be deleted',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete("/activities/{$activity->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    public function test_user_without_permission_cannot_delete_activity(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);
        $activity = Activity::create([
            'user_id' => $admin->id,
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'type' => 'note',
            'description' => 'Protected',
            'occurred_at' => now(),
        ]);

        $this->actingAs($this->makeUserWithout())
            ->delete("/activities/{$activity->id}")
            ->assertForbidden();
    }
}
