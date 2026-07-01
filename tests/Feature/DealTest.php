<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealTest extends TestCase
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
        $user->givePermissionTo('manage deals');

        return $user;
    }

    private function makeUserWithout(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_deals(): void
    {
        $this->get('/deals')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_deals(): void
    {
        $this->actingAs($this->makeUserWithout())
            ->get('/deals')
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_deals(): void
    {
        $this->actingAs($this->makeUserWith())
            ->get('/deals')
            ->assertOk();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_index_shows_deals(): void
    {
        $admin = $this->makeAdmin();
        Deal::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get('/deals')
            ->assertInertia(fn ($page) => $page
                ->component('Deals/Index')
                ->has('deals.data', 1)
            );
    }

    public function test_index_filters_by_stage(): void
    {
        $admin = $this->makeAdmin();
        Deal::factory()->create(['user_id' => $admin->id, 'stage' => 'lead']);
        Deal::factory()->create(['user_id' => $admin->id, 'stage' => 'won', 'closed_at' => now()]);

        $this->actingAs($admin)
            ->get('/deals?stage=lead')
            ->assertInertia(fn ($page) => $page->has('deals.data', 1));
    }

    // ── Create / Store ───────────────────────────────────────────────────────

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/deals/create')
            ->assertOk();
    }

    public function test_can_store_deal(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/deals', [
                'name' => 'Big Deal',
                'value' => 5000.00,
                'stage' => 'lead',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deals', [
            'name' => 'Big Deal',
            'stage' => 'lead',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/deals', [])
            ->assertSessionHasErrors(['name', 'stage']);
    }

    public function test_store_with_contact_and_company(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::factory()->create(['user_id' => $admin->id]);
        $company = Company::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/deals', [
                'name' => 'Linked Deal',
                'stage' => 'qualified',
                'contact_id' => $contact->id,
                'company_id' => $company->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deals', [
            'name' => 'Linked Deal',
            'contact_id' => $contact->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_storing_won_deal_sets_closed_at(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/deals', [
                'name' => 'Won Deal',
                'stage' => 'won',
                'value' => 1000,
            ])
            ->assertRedirect();

        $deal = Deal::where('name', 'Won Deal')->first();
        $this->assertNotNull($deal->closed_at);
    }

    // ── Edit / Update ────────────────────────────────────────────────────────

    public function test_can_edit_deal(): void
    {
        $admin = $this->makeAdmin();
        $deal = Deal::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get("/deals/{$deal->id}/edit")
            ->assertOk();
    }

    public function test_can_update_deal(): void
    {
        $admin = $this->makeAdmin();
        $deal = Deal::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->put("/deals/{$deal->id}", [
                'name' => 'Updated Deal',
                'stage' => 'proposal',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'name' => 'Updated Deal',
            'stage' => 'proposal',
        ]);
    }

    public function test_updating_to_won_sets_closed_at(): void
    {
        $admin = $this->makeAdmin();
        $deal = Deal::factory()->create(['user_id' => $admin->id, 'stage' => 'proposal']);

        $this->actingAs($admin)
            ->put("/deals/{$deal->id}", [
                'name' => $deal->name,
                'stage' => 'won',
            ])
            ->assertRedirect();

        $deal->refresh();
        $this->assertNotNull($deal->closed_at);
    }

    public function test_updating_from_won_clears_closed_at(): void
    {
        $admin = $this->makeAdmin();
        $deal = Deal::factory()->won()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->put("/deals/{$deal->id}", [
                'name' => $deal->name,
                'stage' => 'lead',
            ])
            ->assertRedirect();

        $deal->refresh();
        $this->assertNull($deal->closed_at);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_can_delete_deal(): void
    {
        $admin = $this->makeAdmin();
        $deal = Deal::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->delete("/deals/{$deal->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
    }

    // ── Bulk Delete ──────────────────────────────────────────────────────────

    public function test_bulk_delete_deals(): void
    {
        $admin = $this->makeAdmin();
        $d1 = Deal::factory()->create(['user_id' => $admin->id]);
        $d2 = Deal::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/deals/bulk', [
                'ids' => [$d1->id, $d2->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('deals', ['id' => $d1->id]);
    }
}
