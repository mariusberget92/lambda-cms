<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmExportTest extends TestCase
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

    public function test_guest_cannot_access_export(): void
    {
        $this->get('/crm/export')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_export(): void
    {
        $this->actingAs($this->makeUserWithout())
            ->get('/crm/export')
            ->assertForbidden();
    }

    public function test_admin_can_access_export_page(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/crm/export')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('CrmExport/Index'));
    }

    public function test_can_export_contacts_csv(): void
    {
        $admin = $this->makeAdmin();
        Contact::factory()->create([
            'user_id' => $admin->id,
            'first_name' => 'Export',
            'last_name' => 'Test',
        ]);

        $response = $this->actingAs($admin)
            ->get('/crm/export/download?entity=contacts');

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_can_export_companies_csv(): void
    {
        $admin = $this->makeAdmin();
        Company::factory()->create(['user_id' => $admin->id, 'name' => 'Export Corp']);

        $response = $this->actingAs($admin)
            ->get('/crm/export/download?entity=companies');

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_export_validates_entity(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/crm/export/download?entity=invalid')
            ->assertSessionHasErrors('entity');
    }
}
