<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;

class SendScheduledCampaignsCommand extends Command
{
    protected $signature = 'campaigns:send-scheduled';

    protected $description = 'Dispatch scheduled campaigns whose send time has arrived.';

    public function handle(): void
    {
        $campaigns = Campaign::scheduledAndDue()->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => 'sending']);
            SendCampaignJob::dispatch($campaign);
        }

        $this->info("Dispatched {$campaigns->count()} scheduled campaign(s).");
    }
}
