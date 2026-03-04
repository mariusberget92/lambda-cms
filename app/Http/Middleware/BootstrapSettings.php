<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class BootstrapSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timezone = \App\Models\Setting::get('locale.timezone', 'UTC');
            date_default_timezone_set($timezone);
            Config::set('app.timezone', $timezone);
        } catch (\Throwable) {
            // Settings table doesn't exist yet (pre-migration). Silently skip.
        }

        return $next($request);
    }
}
