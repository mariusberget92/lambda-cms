<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TemplateMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $htmlBody,
    ) {}

    public function build(): static
    {
        return $this
            ->subject($this->mailSubject)
            ->view('emails.template', ['body' => $this->htmlBody]);
    }
}
