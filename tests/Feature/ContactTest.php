<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
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
        $user->givePermissionTo('manage contacts');

        return $user;
    }

    private function makeUserWithout(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_contacts(): void
    {
        $this->get('/contacts')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_contacts(): void
    {
        $this->actingAs($this->makeUserWithout())
            ->get('/contacts')
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_contacts(): void
    {
        $this->actingAs($this->makeUserWith())
            ->get('/contacts')
            ->assertOk();
    }

    public function test_admin_can_access_contacts(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/contacts')
            ->assertOk();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_index_shows_contacts(): void
    {
        $admin = $this->makeAdmin();
        Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get('/contacts')
            ->assertInertia(fn ($page) => $page
                ->component('Contacts/Index')
                ->has('contacts.data', 1)
            );
    }

    public function test_index_search_filters_contacts(): void
    {
        $admin = $this->makeAdmin();
        Contact::factory()->create(['user_id' => $admin->id, 'first_name' => 'Alice']);
        Contact::factory()->create(['user_id' => $admin->id, 'first_name' => 'Bob']);

        $this->actingAs($admin)
            ->get('/contacts?search=Alice')
            ->assertInertia(fn ($page) => $page->has('contacts.data', 1));
    }

    // ── Create / Store ───────────────────────────────────────────────────────

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/contacts/create')
            ->assertOk();
    }

    public function test_can_store_contact(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/contacts', [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/contacts', [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'status']);
    }

    public function test_can_store_contact_with_company(): void
    {
        $admin = $this->makeAdmin();
        $company = Company::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/contacts', [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'company_id' => $company->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'company_id' => $company->id,
        ]);
    }

    // ── Edit / Update ────────────────────────────────────────────────────────

    public function test_can_edit_contact(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get("/contacts/{$contact->id}/edit")
            ->assertOk();
    }

    public function test_can_update_contact(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->put("/contacts/{$contact->id}", [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => 'Updated',
            'status' => 'inactive',
        ]);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_can_delete_contact(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->delete("/contacts/{$contact->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    // ── Bulk Delete ──────────────────────────────────────────────────────────

    public function test_bulk_delete_contacts(): void
    {
        $admin = $this->makeAdmin();
        $c1 = Contact::factory()->create(['user_id' => $admin->id]);
        $c2 = Contact::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/contacts/bulk', [
                'ids' => [$c1->id, $c2->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('contacts', ['id' => $c1->id]);
        $this->assertDatabaseMissing('contacts', ['id' => $c2->id]);
    }

    // ── Model ────────────────────────────────────────────────────────────────

    public function test_full_name_attribute(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
        ]);

        $this->assertEquals('Alice Wonderland', $contact->full_name);
    }

    public function test_search_scope(): void
    {
        $admin = $this->makeAdmin();
        Contact::factory()->create(['user_id' => $admin->id, 'email' => 'findme@test.com']);
        Contact::factory()->create(['user_id' => $admin->id, 'email' => 'nope@test.com']);

        $results = Contact::search('findme')->get();
        $this->assertCount(1, $results);
    }
}
