<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url'       => ['required', 'url', 'max:500', $this->noPrivateUrlRule()],
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
        $validated = $request->validate([
            'url'       => ['required', 'url', 'max:500', $this->noPrivateUrlRule()],
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

    private function noPrivateUrlRule(): \Closure
    {
        return function (string $attr, mixed $value, \Closure $fail): void {
            $host = parse_url($value, PHP_URL_HOST);
            if (! $host) {
                $fail('Invalid webhook URL.');
                return;
            }
            $ip = gethostbyname($host);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $fail('Webhook URL must not point to a private or reserved IP address.');
            }
        };
    }
}
