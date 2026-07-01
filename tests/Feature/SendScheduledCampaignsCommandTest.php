<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendScheduledCampaignsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function createCampaign(array $overrides = []): Campaign
    {
        $user = User::factory()->create()->assignRole('administrator');

        return Campaign::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Scheduled Campaign',
            'subject' => 'Test Subject',
            'body' => '<p>Body</p>',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ], $overrides));
    }

    public function test_dispatches_due_campaigns(): void
    {
        Queue::fake();

        $campaign = $this->createCampaign();

        $this->artisan('campaigns:send-scheduled')
            ->expectsOutputToContain('Dispatched 1 scheduled campaign(s)')
            ->assertExitCode(0);

        $campaign->refresh();
        $this->assertEquals('sending', $campaign->status);

        Queue::assertPushed(SendCampaignJob::class);
    }

    public function test_skips_campaigns_not_yet_due(): void
    {
        Queue::fake();

        $this->createCampaign(['scheduled_at' => now()->addHour()]);

        $this->artisan('campaigns:send-scheduled')
            ->expectsOutputToContain('Dispatched 0 scheduled campaign(s)')
            ->assertExitCode(0);

        Queue::assertNotPushed(SendCampaignJob::class);
    }

    public function test_skips_non_scheduled_campaigns(): void
    {
        Queue::fake();

        $this->createCampaign(['status' => 'draft', 'scheduled_at' => now()->subMinute()]);

        $this->artisan('campaigns:send-scheduled')
            ->expectsOutputToContain('Dispatched 0 scheduled campaign(s)')
            ->assertExitCode(0);

        Queue::assertNotPushed(SendCampaignJob::class);
    }

    public function test_dispatches_multiple_due_campaigns(): void
    {
        Queue::fake();

        $this->createCampaign(['name' => 'Campaign 1']);
        $this->createCampaign(['name' => 'Campaign 2']);

        $this->artisan('campaigns:send-scheduled')
            ->expectsOutputToContain('Dispatched 2 scheduled campaign(s)')
            ->assertExitCode(0);

        Queue::assertPushed(SendCampaignJob::class, 2);
    }
}
