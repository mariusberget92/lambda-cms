<?php

namespace App\Console\Commands;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledNewslettersCommand extends Command
{
    protected $signature   = 'newsletter:send-scheduled';
    protected $description = 'Send newsletter campaigns whose scheduled time has passed.';

    public function handle(): void
    {
        $campaigns = NewsletterCampaign::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->get();

        if ($campaigns->isEmpty()) {
            return;
        }

        $subscribers = NewsletterSubscriber::confirmed()->get();
        $count       = $subscribers->count();

        foreach ($campaigns as $campaign) {
            foreach ($subscribers as $subscriber) {
                Mail::to($subscriber->email)
                    ->queue(new NewsletterCampaignMail($campaign, $subscriber));
            }

            $campaign->update(['sent_at' => now(), 'recipients_count' => $count]);

            $this->info("Sent campaign '{$campaign->title}' to {$count} subscriber(s).");
        }
    }
}
