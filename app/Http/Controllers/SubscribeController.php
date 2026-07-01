<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = Subscriber::where('email', $request->input('email'))->first();

        if ($existing) {
            if ($existing->status === 'unsubscribed') {
                $existing->update([
                    'status' => 'active',
                    'name' => $request->input('name') ?: $existing->name,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);

                return back()->with('subscribe_status', 'Welcome back! You have been re-subscribed.');
            }

            return back()->with('subscribe_status', 'You are already subscribed!');
        }

        Subscriber::create([
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'subscribed_at' => now(),
        ]);

        return back()->with('subscribe_status', 'Thanks for subscribing!');
    }

    public function unsubscribe(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        if ($subscriber->status === 'active') {
            $subscriber->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);
        }

        return view('unsubscribe', ['subscriber' => $subscriber]);
    }
}
