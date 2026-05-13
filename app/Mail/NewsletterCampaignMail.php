<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\BlockEmailRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedHtml;

    public function __construct(
        public NewsletterCampaign $campaign,
        public NewsletterSubscriber $subscriber,
    ) {
        $unsubUrl = url('/newsletter/unsubscribe/' . $subscriber->token);

        // Prefer blocks (block editor content) over plain-text body
        if (!empty($campaign->blocks)) {
            $renderer = new BlockEmailRenderer();
            $this->renderedHtml = $renderer->render($campaign->blocks, $unsubUrl);
        } else {
            // Fallback: render plain body with unsubscribe footer
            $escapedBody = nl2br(htmlspecialchars($campaign->body ?? '', ENT_QUOTES));
            $this->renderedHtml = view('emails.newsletter.campaign', [
                'campaign'   => $campaign,
                'subscriber' => $subscriber,
            ])->render();
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderedHtml);
    }
}
