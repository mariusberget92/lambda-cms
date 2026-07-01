<?php

namespace Tests\Feature;

use App\Models\CallList;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallListTest extends TestCase
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

    private function makeUserWith(): User
    {
        $user = User::factory()->create()->assignRole('user');
        $user->givePermissionTo('manage call lists');

        return $user;
    }

    private function makeUserWithout(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_call_lists(): void
    {
        $this->get('/call-lists')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_call_lists(): void
    {
        $this->actingAs($this->makeUserWithout())
            ->get('/call-lists')
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_call_lists(): void
    {
        $this->actingAs($this->makeUserWith())
            ->get('/call-lists')
            ->assertOk();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_index_shows_call_lists(): void
    {
        $admin = $this->makeAdmin();
        CallList::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get('/call-lists')
            ->assertInertia(fn ($page) => $page
                ->component('CallLists/Index')
                ->has('callLists.data', 1)
            );
    }

    // ── Create / Store ───────────────────────────────────────────────────────

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/call-lists/create')
            ->assertOk();
    }

    public function test_can_store_call_list(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/call-lists', [
                'name' => 'Q3 Outreach',
                'description' => 'Quarterly outreach list',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('call_lists', ['name' => 'Q3 Outreach']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/call-lists', [])
            ->assertSessionHasErrors(['name', 'status']);
    }

    // ── Edit / Update ────────────────────────────────────────────────────────

    public function test_can_edit_call_list(): void
    {
        $admin = $this->makeAdmin();
        $list = CallList::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get("/call-lists/{$list->id}/edit")
            ->assertOk();
    }

    public function test_can_update_call_list(): void
    {
        $admin = $this->makeAdmin();
        $list = CallList::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->put("/call-lists/{$list->id}", [
                'name' => 'Updated List',
                'status' => 'completed',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('call_lists', [
            'id' => $list->id,
            'name' => 'Updated List',
            'status' => 'completed',
        ]);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_can_delete_call_list(): void
    {
        $admin = $this->makeAdmin();
        $list = CallList::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->delete("/call-lists/{$list->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('call_lists', ['id' => $list->id]);
    }

    // ── Bulk Delete ──────────────────────────────────────────────────────────

    public function test_bulk_delete_call_lists(): void
    {
        $admin = $this->makeAdmin();
        $l1 = CallList::factory()->create(['user_id' => $admin->id]);
        $l2 = CallList::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/call-lists/bulk', [
                'ids' => [$l1->id, $l2->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('call_lists', ['id' => $l1->id]);
    }

    // ── Work Mode ────────────────────────────────────────────────────────────

    public function test_can_access_work_mode(): void
    {
        $admin = $this->makeAdmin();
        $list = CallList::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get("/call-lists/{$list->id}/work")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CallLists/Work'));
    }

    // ── Add / Remove Contacts ────────────────────────────────────────────────

    public function test_can_add_contacts_to_list(): void
    {
        $admin = $this->makeAdmin();
        $list = CallList::factory()->create(['user_id' => $admin->id]);
        $contact = Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post("/call-lists/{$list->id}/contacts", [
                'contact_ids' => [$contact->id],
            ])
            ->assertRedirect();

        $this->assertTrue($list->contacts()->where('contacts.id', $contact->id)->exists());
    }

    public function test_can_remove_contacts_from_list(): void
    {
        $admin = $this->makeAdmin();
        $list = CallList::factory()->create(['user_id' => $admin->id]);
        $contact = Contact::factory()->create(['user_id' => $admin->id]);
        $list->contacts()->attach($contact->id, ['sort_order' => 1]);

        $this->actingAs($admin)
            ->post("/call-lists/{$list->id}/contacts/remove", [
                'contact_ids' => [$contact->id],
            ])
            ->assertRedirect();

        $this->assertFalse($list->contacts()->where('contacts.id', $contact->id)->exists());
    }

    // ── Update Contact Status ────────────────────────────────────────────────

    public function test_can_update_contact_call_status(): void
    {
        $admin = $this->makeAdmin();
        $list = CallList::factory()->create(['user_id' => $admin->id]);
        $contact = Contact::factory()->create(['user_id' => $admin->id]);
        $list->contacts()->attach($contact->id, ['sort_order' => 1]);

        $this->actingAs($admin)
            ->put("/call-lists/{$list->id}/contacts/{$contact->id}/status", [
                'call_status' => 'completed',
                'notes' => 'Great conversation',
            ])
            ->assertRedirect();

        $pivot = $list->contacts()->where('contacts.id', $contact->id)->first()->pivot;
        $this->assertEquals('completed', $pivot->call_status);
        $this->assertEquals('Great conversation', $pivot->notes);
        $this->assertNotNull($pivot->called_at);
    }
}
