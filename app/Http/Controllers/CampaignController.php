<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('manage email'), 403);

        $query = Campaign::with('creator:id,name')
            ->withCount('recipients')
            ->search($request->input('search'))
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('manage email'), 403);

        return Inertia::render('Campaigns/Form', [
            'campaign' => null,
            'subscriberCount' => Subscriber::active()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage email'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $campaign = Campaign::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('campaigns.edit', $campaign)
            ->with('status', 'Campaign created.');
    }

    public function edit(Request $request, Campaign $campaign): Response
    {
        abort_unless($request->user()->can('manage email'), 403);
        abort_if(! in_array($campaign->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled campaigns can be edited.');

        return Inertia::render('Campaigns/Form', [
            'campaign' => $campaign,
            'subscriberCount' => Subscriber::active()->count(),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($request->user()->can('manage email'), 403);
        abort_if(! in_array($campaign->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled campaigns can be edited.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $campaign->update([
            ...$validated,
            'status' => 'draft',
            'scheduled_at' => null,
        ]);

        return back()->with('status', 'Campaign updated.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($request->user()->can('manage email'), 403);
        abort_if($campaign->status === 'sending', 403, 'Cannot delete a campaign that is currently sending.');

        $campaign->delete();

        return redirect()
            ->route('campaigns.index')
            ->with('status', 'Campaign deleted.');
    }

    public function send(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($request->user()->can('manage email'), 403);
        abort_if(! in_array($campaign->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled campaigns can be sent.');

        $activeSubscribers = Subscriber::active()->count();
        abort_if($activeSubscribers === 0, 422, 'No active subscribers to send to.');

        $campaign->update(['status' => 'sending', 'scheduled_at' => null]);

        SendCampaignJob::dispatch($campaign);

        return redirect()
            ->route('campaigns.report', $campaign)
            ->with('status', 'Campaign is being sent to '.$activeSubscribers.' subscriber(s).');
    }

    public function schedule(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($request->user()->can('manage email'), 403);
        abort_if(! in_array($campaign->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled campaigns can be scheduled.');

        $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $activeSubscribers = Subscriber::active()->count();
        abort_if($activeSubscribers === 0, 422, 'No active subscribers to send to.');

        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $request->input('scheduled_at'),
        ]);

        return redirect()
            ->route('campaigns.index')
            ->with('status', 'Campaign scheduled for '.$campaign->scheduled_at->format('M j, Y g:i A').'.');
    }

    public function unschedule(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($request->user()->can('manage email'), 403);
        abort_if($campaign->status !== 'scheduled', 403, 'Only scheduled campaigns can be unscheduled.');

        $campaign->update([
            'status' => 'draft',
            'scheduled_at' => null,
        ]);

        return back()->with('status', 'Campaign unscheduled and moved back to draft.');
    }

    public function report(Request $request, Campaign $campaign): Response
    {
        abort_unless($request->user()->can('manage email'), 403);

        $campaign->loadCount([
            'recipients as total_count',
            'recipients as sent_count' => fn ($q) => $q->where('status', 'sent'),
            'recipients as failed_count' => fn ($q) => $q->where('status', 'failed'),
            'recipients as pending_count' => fn ($q) => $q->where('status', 'pending'),
        ]);

        $recipients = $campaign->recipients()
            ->with('subscriber:id,email,name')
            ->orderByDesc('sent_at')
            ->paginate(50);

        return Inertia::render('Campaigns/Report', [
            'campaign' => $campaign,
            'recipients' => $recipients,
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage email'), 403);

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:campaigns,id'],
            'action' => ['required', 'in:delete'],
        ]);

        Campaign::whereIn('id', $request->input('ids'))
            ->where('status', '!=', 'sending')
            ->delete();

        return back()->with('status', count($request->input('ids')).' campaign(s) deleted.');
    }
}
