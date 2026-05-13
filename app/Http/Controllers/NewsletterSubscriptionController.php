<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterSubscriptionController extends Controller
{
    public function subscribe(Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name'  => ['nullable', 'string', 'max:100'],
        ]);

        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing && $existing->isConfirmed()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'You are already subscribed.']);
            }
            return back()->with('newsletter_status', 'You are already subscribed.');
        }

        $subscriber = $existing ?? NewsletterSubscriber::create([
            'email'      => $validated['email'],
            'name'       => $validated['name'] ?? null,
            'token'      => Str::random(64),
            'ip_address' => $request->ip(),
        ]);

        Mail::to($subscriber->email)->queue(new NewsletterConfirmMail($subscriber));

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Please check your email to confirm your subscription.']);
        }
        return back()->with('newsletter_status', 'Please check your email to confirm your subscription.');
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        if (! $subscriber->isConfirmed()) {
            $subscriber->update(['confirmed_at' => now()]);
        }

        return redirect('/')->with('newsletter_status', 'Your subscription has been confirmed. Thank you!');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $subscriber->delete();

        return redirect('/')->with('newsletter_status', 'You have been unsubscribed successfully.');
    }
}
