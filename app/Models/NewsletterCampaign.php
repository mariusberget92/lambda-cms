<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    protected $fillable = [
        'title',
        'subject',
        'body',
        'sent_at',
        'recipients_count',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }
}
