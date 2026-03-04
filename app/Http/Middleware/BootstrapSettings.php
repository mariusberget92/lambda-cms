<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class BootstrapSettings
{
    /**
     * Apply the persisted timezone setting on every web request.
     *
     * Must run before HandleInertiaRequests so that the correct timezone is
     * active when Inertia builds its shared props.
     *
     * The try/catch guard is intentional: on a fresh checkout before
     * `php artisan migrate` has been run, the `settings` table does not yet
     * exist. Catching Throwable prevents a crash during installation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timezone = Setting::get('locale.timezone', 'UTC');
            date_default_timezone_set($timezone);
            Config::set('app.timezone', $timezone);
        } catch (\Throwable) {
            // Settings table doesn't exist yet (pre-migration). Silently skip.
        }

        return $next($request);
    }
}
