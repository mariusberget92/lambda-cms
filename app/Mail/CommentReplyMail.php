<?php

namespace App\Mail;

use App\Models\Comment;
use App\Models\Setting;
use App\Services\TemplateMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommentReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Comment $parent,
        public readonly Comment $reply,
    ) {}

    public function build(): static
    {
        $template = app(TemplateMailer::class)->build('comment-reply', [
            'post_title' => $this->parent->post->title,
            'original_comment' => $this->parent->body,
            'reply_author' => $this->reply->author_name,
            'reply_body' => $this->reply->body,
            'site_name' => Setting::get('site.name', config('app.name')),
        ]);

        return $this
            ->subject($template->mailSubject)
            ->view('emails.template', ['body' => $template->htmlBody]);
    }
}
