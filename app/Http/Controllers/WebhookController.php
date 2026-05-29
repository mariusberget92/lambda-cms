<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Webhooks', [
            'webhooks' => Webhook::orderByDesc('created_at')->get(),
        ]);
    }

    public function store(StoreWebhookRequest $request)
    {
        $validated = $request->validated();

        Webhook::create($validated);

        return back()->with('status', 'Webhook created.');
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook)
    {
        $validated = $request->validated();

        $webhook->update($validated);

        return back()->with('status', 'Webhook updated.');
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return back()->with('status', 'Webhook deleted.');
    }
}
