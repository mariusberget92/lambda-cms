<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $mailSubject,
        protected string $htmlBody,
        protected Subscriber $subscriber,
    ) {}

    public function build(): static
    {
        $unsubscribeUrl = url('/unsubscribe/'.$this->subscriber->token);
        $footer = '<p style="margin-top:32px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">You received this because you are subscribed. <a href="'.$unsubscribeUrl.'" style="color:#6b7280;">Unsubscribe</a></p>';

        return $this
            ->subject($this->mailSubject)
            ->view('emails.template', ['body' => $this->htmlBody.$footer]);
    }
}
