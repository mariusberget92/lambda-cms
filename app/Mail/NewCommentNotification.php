<?php

namespace App\Mail;

use App\Models\Comment;
use App\Models\Setting;
use App\Services\TemplateMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewCommentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Comment $comment) {}

    public function build(): static
    {
        $template = app(TemplateMailer::class)->build('new-comment', [
            'post_title' => $this->comment->post->title,
            'comment_author' => $this->comment->author_name,
            'comment_body' => $this->comment->body,
            'post_url' => route('blog.show', $this->comment->post->slug),
            'site_name' => Setting::get('site.name', config('app.name')),
        ]);

        return $this
            ->subject($template->mailSubject)
            ->view('emails.template', ['body' => $template->htmlBody]);
    }
}
