<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
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
        $user->givePermissionTo('manage companies');

        return $user;
    }

    private function makeUserWithout(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_companies(): void
    {
        $this->get('/companies')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_companies(): void
    {
        $this->actingAs($this->makeUserWithout())
            ->get('/companies')
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_companies(): void
    {
        $this->actingAs($this->makeUserWith())
            ->get('/companies')
            ->assertOk();
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_index_shows_companies(): void
    {
        $admin = $this->makeAdmin();
        Company::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get('/companies')
            ->assertInertia(fn ($page) => $page
                ->component('Companies/Index')
                ->has('companies.data', 1)
            );
    }

    public function test_index_search_filters_companies(): void
    {
        $admin = $this->makeAdmin();
        Company::factory()->create(['user_id' => $admin->id, 'name' => 'Acme Corp']);
        Company::factory()->create(['user_id' => $admin->id, 'name' => 'Other Inc']);

        $this->actingAs($admin)
            ->get('/companies?search=Acme')
            ->assertInertia(fn ($page) => $page->has('companies.data', 1));
    }

    // ── Create / Store ───────────────────────────────────────────────────────

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/companies/create')
            ->assertOk();
    }

    public function test_can_store_company(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post('/companies', [
                'name' => 'Test Company',
                'domain' => 'test.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', ['name' => 'Test Company']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/companies', [])
            ->assertSessionHasErrors(['name']);
    }

    // ── Edit / Update ────────────────────────────────────────────────────────

    public function test_can_edit_company(): void
    {
        $admin = $this->makeAdmin();
        $company = Company::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get("/companies/{$company->id}/edit")
            ->assertOk();
    }

    public function test_can_update_company(): void
    {
        $admin = $this->makeAdmin();
        $company = Company::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->put("/companies/{$company->id}", [
                'name' => 'Updated Corp',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Corp',
        ]);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_can_delete_company(): void
    {
        $admin = $this->makeAdmin();
        $company = Company::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->delete("/companies/{$company->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    // ── Bulk Delete ──────────────────────────────────────────────────────────

    public function test_bulk_delete_companies(): void
    {
        $admin = $this->makeAdmin();
        $c1 = Company::factory()->create(['user_id' => $admin->id]);
        $c2 = Company::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post('/companies/bulk', [
                'ids' => [$c1->id, $c2->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('companies', ['id' => $c1->id]);
        $this->assertDatabaseMissing('companies', ['id' => $c2->id]);
    }
}
