<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Setting;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public readonly Campaign $campaign) {}

    public function handle(): void
    {
        $this->applyMailConfig();

        $subscribers = Subscriber::active()->get();

        foreach ($subscribers as $subscriber) {
            CampaignRecipient::firstOrCreate([
                'campaign_id' => $this->campaign->id,
                'subscriber_id' => $subscriber->id,
            ]);
        }

        $recipients = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->where('status', 'pending')
            ->with('subscriber')
            ->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->subscriber->email)
                    ->send(new CampaignMail(
                        $this->campaign->subject,
                        $this->campaign->body,
                        $recipient->subscriber,
                    ));

                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $sentCount++;
            } catch (\Throwable $e) {
                $recipient->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;

                Log::warning('Campaign email failed', [
                    'campaign_id' => $this->campaign->id,
                    'subscriber_id' => $recipient->subscriber_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->campaign->update([
            'status' => $failedCount === $recipients->count() ? 'failed' : 'sent',
            'sent_at' => now(),
        ]);
    }

    protected function applyMailConfig(): void
    {
        $driver = Setting::get('mail.driver');
        if ($driver) {
            Config::set('mail.default', $driver);
        }

        $host = Setting::get('mail.host');
        if ($host) {
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', Setting::get('mail.port', 587));
            Config::set('mail.mailers.smtp.username', Setting::get('mail.username'));
            Config::set('mail.mailers.smtp.password', Setting::get('mail.password'));
            Config::set('mail.mailers.smtp.encryption', Setting::get('mail.encryption', 'tls'));
        }

        $fromAddress = Setting::get('mail.from_address');
        if ($fromAddress) {
            Config::set('mail.from.address', $fromAddress);
            Config::set('mail.from.name', Setting::get('mail.from_name', config('app.name')));
        }
    }
}
