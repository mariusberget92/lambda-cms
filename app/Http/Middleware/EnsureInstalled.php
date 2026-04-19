<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    /**
     * Redirect to the installer if the app has not been installed yet.
     *
     * Installation state is tracked via a lock file at storage/app/installed,
     * which is written by InstallController as the final step of the wizard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! file_exists(storage_path('app/installed'))) {
            return redirect('/install');
        }

        return $next($request);
    }
}
