<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $postTitle;

    public function __construct(
        public readonly Comment $parent,
        public readonly Comment $reply,
    ) {
        $this->postTitle = $parent->post->title;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Someone replied to your comment on "' . $this->postTitle . '"',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.comment-reply',
        );
    }
}
