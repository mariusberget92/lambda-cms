<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    // ── Subscribers ───────────────────────────────────────────────────────────

    public function subscribers(Request $request): Response
    {
        $filter = $request->input('filter', 'confirmed');

        $subscribers = NewsletterSubscriber::when(
                $filter === 'confirmed',
                fn ($q) => $q->confirmed()
            )
            ->when(
                $filter === 'pending',
                fn ($q) => $q->whereNull('confirmed_at')
            )
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (NewsletterSubscriber $s) => [
                'id'           => $s->id,
                'email'        => $s->email,
                'name'         => $s->name,
                'confirmed_at' => $s->confirmed_at?->toDateString(),
                'created_at'   => $s->created_at->diffForHumans(),
            ]);

        return Inertia::render('Newsletter/Subscribers', [
            'subscribers'    => $subscribers,
            'filter'         => $filter,
            'totalConfirmed' => NewsletterSubscriber::confirmed()->count(),
            'totalPending'   => NewsletterSubscriber::whereNull('confirmed_at')->count(),
        ]);
    }

    public function destroySubscriber(NewsletterSubscriber $subscriber): RedirectResponse
    {
        ActivityLogger::log('deleted', "Removed newsletter subscriber '{$subscriber->email}'", 'NewsletterSubscriber', $subscriber->id);
        $subscriber->delete();
        return back()->with('status', 'Subscriber removed.');
    }

    public function bulkSubscribers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:newsletter_subscribers,id'],
        ]);

        $count = count($validated['ids']);
        NewsletterSubscriber::whereIn('id', $validated['ids'])->delete();
        ActivityLogger::log('deleted', "Bulk removed {$count} newsletter subscriber(s)", 'NewsletterSubscriber');

        return back()->with('status', "Removed {$count} subscriber(s).");
    }

    public function exportSubscribers(): StreamedResponse
    {
        $subscribers = NewsletterSubscriber::confirmed()->orderBy('email')->get();

        return response()->streamDownload(function () use ($subscribers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'name', 'confirmed_at']);
            foreach ($subscribers as $s) {
                fputcsv($handle, [$s->email, $s->name ?? '', $s->confirmed_at?->toDateString() ?? '']);
            }
            fclose($handle);
        }, 'subscribers-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    // ── Campaigns ─────────────────────────────────────────────────────────────

    public function campaigns(): Response
    {
        $campaigns = NewsletterCampaign::latest()
            ->paginate(20)
            ->through(fn (NewsletterCampaign $c) => [
                'id'               => $c->id,
                'title'            => $c->title,
                'subject'          => $c->subject,
                'sent_at'          => $c->sent_at?->toDateString(),
                'scheduled_at'     => $c->scheduled_at?->toIso8601String(),
                'recipients_count' => $c->recipients_count,
                'created_at'       => $c->created_at->toDateString(),
            ]);

        return Inertia::render('Newsletter/Campaigns', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Create a draft campaign and redirect to the block editor.
     */
    public function storeCampaign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
        ]);

        $campaign = NewsletterCampaign::create([
            'title'   => $validated['title'],
            'subject' => $validated['subject'],
            'body'    => '',
            'blocks'  => [],
        ]);

        ActivityLogger::log('created', "Created newsletter campaign '{$campaign->title}'", 'NewsletterCampaign', $campaign->id);

        return redirect()->route('newsletter.campaigns.edit', $campaign->id)
            ->with('status', 'Campaign created. Design it with the block editor.');
    }

    /**
     * Full-screen block editor for the campaign.
     */
    public function editCampaign(NewsletterCampaign $campaign): Response
    {
        abort_if($campaign->isSent(), 403, 'Sent campaigns cannot be edited.');

        return Inertia::render('Newsletter/CampaignEditor', [
            'campaign' => [
                'id'           => $campaign->id,
                'title'        => $campaign->title,
                'subject'      => $campaign->subject,
                'blocks'       => $campaign->blocks ?? [],
                'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Save blocks (and metadata) from the campaign editor.
     */
    public function updateCampaign(Request $request, NewsletterCampaign $campaign): RedirectResponse
    {
        abort_if($campaign->isSent(), 403, 'Sent campaigns cannot be edited.');

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'subject'      => ['required', 'string', 'max:255'],
            'blocks'       => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $campaign->update($validated);

        ActivityLogger::log('updated', "Updated newsletter campaign '{$campaign->title}'", 'NewsletterCampaign', $campaign->id);

        return back()->with('status', 'Campaign saved.');
    }

    public function sendCampaign(Request $request, NewsletterCampaign $campaign): RedirectResponse
    {
        if ($campaign->isSent()) {
            return back()->with('error', 'This campaign has already been sent.');
        }

        $subscribers = NewsletterSubscriber::confirmed()->get();
        $count       = $subscribers->count();

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)
                ->queue(new NewsletterCampaignMail($campaign, $subscriber));
        }

        $campaign->update(['sent_at' => now(), 'scheduled_at' => null, 'recipients_count' => $count]);

        ActivityLogger::log('updated', "Sent newsletter campaign '{$campaign->title}' to {$count} subscriber(s)", 'NewsletterCampaign', $campaign->id);

        return redirect()->route('newsletter.campaigns')
            ->with('status', "Campaign sent to {$count} subscriber(s).");
    }

    public function destroyCampaign(NewsletterCampaign $campaign): RedirectResponse
    {
        ActivityLogger::log('deleted', "Deleted newsletter campaign '{$campaign->title}'", 'NewsletterCampaign', $campaign->id);
        $campaign->delete();
        return redirect()->route('newsletter.campaigns')->with('status', 'Campaign deleted.');
    }
}
