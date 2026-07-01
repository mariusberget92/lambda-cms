<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('EmailTemplates/Index', [
            'templates' => EmailTemplate::orderBy('name')->get(),
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        return Inertia::render('EmailTemplates/Edit', [
            'template' => $emailTemplate,
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update($request->validated());

        return back()->with('status', 'Email template updated.');
    }

    public function reset(EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->resetToDefault();

        return back()->with('status', 'Template reset to default.');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate): string
    {
        $sampleData = [];
        foreach ($emailTemplate->merge_tags ?? [] as $tag) {
            $tagKey = str_replace(['{{', '}}'], '', $tag['tag']);
            $sampleData[$tagKey] = match ($tagKey) {
                'user_name' => 'John Doe',
                'reset_url', 'verification_url', 'post_url' => '#',
                'site_name' => 'Lambda CMS',
                'post_title' => 'Example Blog Post',
                'comment_author', 'reply_author' => 'Jane Smith',
                'comment_body', 'reply_body', 'original_comment' => 'This is a sample comment.',
                default => "[$tagKey]",
            };
        }

        $rendered = $emailTemplate->render($sampleData);

        return view('emails.template', ['body' => $rendered['body']])->render();
    }
}
