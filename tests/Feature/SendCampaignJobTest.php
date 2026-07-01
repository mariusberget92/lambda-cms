<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignJob;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendCampaignJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeCampaign(): Campaign
    {
        $user = User::factory()->create()->assignRole('administrator');

        return Campaign::create([
            'user_id' => $user->id,
            'name' => 'Test Campaign',
            'subject' => 'Newsletter #1',
            'body' => '<p>Hello subscribers!</p>',
            'status' => 'sending',
        ]);
    }

    public function test_job_sends_to_all_active_subscribers(): void
    {
        Mail::fake();

        $campaign = $this->makeCampaign();
        $s1 = Subscriber::create(['email' => 'a@test.com', 'status' => 'active', 'subscribed_at' => now()]);
        $s2 = Subscriber::create(['email' => 'b@test.com', 'status' => 'active', 'subscribed_at' => now()]);
        Subscriber::create(['email' => 'unsub@test.com', 'status' => 'unsubscribed', 'subscribed_at' => now()]);

        (new SendCampaignJob($campaign))->handle();

        Mail::assertSent(CampaignMail::class, 2);
        Mail::assertSent(CampaignMail::class, fn ($mail) => $mail->hasTo('a@test.com'));
        Mail::assertSent(CampaignMail::class, fn ($mail) => $mail->hasTo('b@test.com'));
        Mail::assertNotSent(CampaignMail::class, fn ($mail) => $mail->hasTo('unsub@test.com'));
    }

    public function test_job_creates_recipient_records(): void
    {
        Mail::fake();

        $campaign = $this->makeCampaign();
        Subscriber::create(['email' => 'r@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        (new SendCampaignJob($campaign))->handle();

        $this->assertEquals(1, CampaignRecipient::where('campaign_id', $campaign->id)->count());
        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);
    }

    public function test_job_marks_campaign_as_sent(): void
    {
        Mail::fake();

        $campaign = $this->makeCampaign();
        Subscriber::create(['email' => 'done@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        (new SendCampaignJob($campaign))->handle();

        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
        $this->assertNotNull($campaign->sent_at);
    }

    public function test_job_marks_campaign_failed_when_all_fail(): void
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP error'));

        $campaign = $this->makeCampaign();
        Subscriber::create(['email' => 'fail@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        (new SendCampaignJob($campaign))->handle();

        $campaign->refresh();
        $this->assertEquals('failed', $campaign->status);
        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $campaign->id,
            'status' => 'failed',
        ]);
    }

    public function test_job_records_error_on_failed_recipient(): void
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \Exception('Connection refused'));

        $campaign = $this->makeCampaign();
        Subscriber::create(['email' => 'err@test.com', 'status' => 'active', 'subscribed_at' => now()]);

        (new SendCampaignJob($campaign))->handle();

        $recipient = CampaignRecipient::where('campaign_id', $campaign->id)->first();
        $this->assertEquals('failed', $recipient->status);
        $this->assertStringContainsString('Connection refused', $recipient->error);
    }

    public function test_job_handles_empty_subscriber_list(): void
    {
        Mail::fake();

        $campaign = $this->makeCampaign();

        (new SendCampaignJob($campaign))->handle();

        $campaign->refresh();
        Mail::assertNothingSent();
        $this->assertEquals(0, CampaignRecipient::count());
    }
}
