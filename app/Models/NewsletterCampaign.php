<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    protected $fillable = [
        'title',
        'subject',
        'body',
        'blocks',
        'sent_at',
        'scheduled_at',
        'recipients_count',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'scheduled_at' => 'datetime',
        'blocks'       => 'array',
    ];

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null && $this->sent_at === null;
    }
}
