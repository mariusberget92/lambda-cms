<?php

namespace App\Services;

use App\Mail\TemplateMailable;
use App\Models\EmailTemplate;

class TemplateMailer
{
    public function build(string $key, array $data = []): TemplateMailable
    {
        $template = EmailTemplate::where('key', $key)->firstOrFail();
        $rendered = $template->render($data);

        return new TemplateMailable($rendered['subject'], $rendered['body']);
    }

    public function preview(string $key, array $data = []): string
    {
        $template = EmailTemplate::where('key', $key)->firstOrFail();
        $rendered = $template->render($data);

        return view('emails.template', ['body' => $rendered['body']])->render();
    }
}
