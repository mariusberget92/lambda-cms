<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
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

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    private function createTemplate(array $overrides = []): EmailTemplate
    {
        $attributes = array_merge([
            'key' => 'test-template',
            'name' => 'Test Template',
            'description' => 'A test template',
            'subject' => 'Hello {{user_name}}',
            'body' => '<p>Welcome, {{user_name}}!</p>',
            'default_subject' => 'Hello {{user_name}}',
            'default_body' => '<p>Welcome, {{user_name}}!</p>',
            'merge_tags' => [
                ['tag' => '{{user_name}}', 'description' => 'User name'],
            ],
        ], $overrides);

        $template = new EmailTemplate;
        foreach ($attributes as $k => $v) {
            $template->{$k} = $v;
        }
        $template->save();

        return $template->fresh();
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_email_templates(): void
    {
        $this->get('/email-templates')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_email_templates(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->get('/email-templates');

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_admin_can_view_email_templates(): void
    {
        $this->createTemplate();

        $this->actingAs($this->makeAdmin())
            ->get('/email-templates')
            ->assertOk();
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_form(): void
    {
        $template = $this->createTemplate();

        $this->actingAs($this->makeAdmin())
            ->get("/email-templates/{$template->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('EmailTemplates/Edit')
                ->has('template')
            );
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_admin_can_update_template(): void
    {
        $template = $this->createTemplate();

        $this->actingAs($this->makeAdmin())
            ->put("/email-templates/{$template->id}", [
                'subject' => 'Updated Subject',
                'body' => '<p>Updated body</p>',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'subject' => 'Updated Subject',
            'body' => '<p>Updated body</p>',
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $template = $this->createTemplate();

        $this->actingAs($this->makeAdmin())
            ->put("/email-templates/{$template->id}", [])
            ->assertSessionHasErrors(['subject', 'body']);
    }

    // ── Reset ────────────────────────────────────────────────────────────────

    public function test_admin_can_reset_template_to_default(): void
    {
        $template = $this->createTemplate();
        $template->update(['subject' => 'Custom subject', 'body' => '<p>Custom</p>']);

        $this->actingAs($this->makeAdmin())
            ->post("/email-templates/{$template->id}/reset")
            ->assertRedirect();

        $template->refresh();
        $this->assertEquals($template->default_subject, $template->subject);
        $this->assertEquals($template->default_body, $template->body);
    }

    // ── Preview ──────────────────────────────────────────────────────────────

    public function test_admin_can_preview_template(): void
    {
        $template = $this->createTemplate();

        $response = $this->actingAs($this->makeAdmin())
            ->post("/email-templates/{$template->id}/preview");

        $response->assertOk();
        $response->assertSee('Welcome,');
    }

    // ── Model ────────────────────────────────────────────────────────────────

    public function test_render_replaces_merge_tags(): void
    {
        $template = $this->createTemplate();
        $result = $template->render(['user_name' => 'Alice']);

        $this->assertEquals('Hello Alice', $result['subject']);
        $this->assertEquals('<p>Welcome, Alice!</p>', $result['body']);
    }

    public function test_render_leaves_unknown_tags_untouched(): void
    {
        $template = $this->createTemplate();
        $result = $template->render([]);

        $this->assertEquals('Hello {{user_name}}', $result['subject']);
    }
}
