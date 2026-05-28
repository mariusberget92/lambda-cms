<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    public function index(): Response
    {
        if (! app(LicenseService::class)->isPro()) {
            return Inertia::render('License/Upgrade', ['feature' => 'webhooks']);
        }

        return Inertia::render('Settings/Webhooks', [
            'webhooks' => Webhook::orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (! app(LicenseService::class)->isPro()) {
            abort(403, 'Pro license required.');
        }

        $validated = $request->validate([
            'url'       => ['required', 'url', 'max:500'],
            'secret'    => ['nullable', 'string', 'max:255'],
            'events'    => ['required', 'array', 'min:1'],
            'events.*'  => ['string', 'in:post.published,post.updated,post.deleted,page.published,page.updated,page.deleted'],
            'is_active' => ['boolean'],
        ]);

        Webhook::create($validated);

        return back()->with('status', 'Webhook created.');
    }

    public function update(Request $request, Webhook $webhook)
    {
        if (! app(LicenseService::class)->isPro()) {
            abort(403, 'Pro license required.');
        }

        $validated = $request->validate([
            'url'       => ['required', 'url', 'max:500'],
            'secret'    => ['nullable', 'string', 'max:255'],
            'events'    => ['required', 'array', 'min:1'],
            'events.*'  => ['string', 'in:post.published,post.updated,post.deleted,page.published,page.updated,page.deleted'],
            'is_active' => ['boolean'],
        ]);

        $webhook->update($validated);

        return back()->with('status', 'Webhook updated.');
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return back()->with('status', 'Webhook deleted.');
    }
}
