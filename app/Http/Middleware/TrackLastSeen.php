<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeen
{
    /**
     * Update the authenticated user's last_seen_at timestamp on every request.
     * Uses a 60-second throttle to avoid a DB write on every single request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = $request->user();

            $throttle = 60; // seconds between DB writes

            if (
                is_null($user->last_seen_at) ||
                $user->last_seen_at->diffInSeconds(now()) >= $throttle
            ) {
                // updateQuietly skips model events and timestamps (updated_at)
                $user->updateQuietly(['last_seen_at' => now()]);
            }
        }

        return $next($request);
    }
}
