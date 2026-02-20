<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (file_exists(storage_path("app/installed"))) {
            return redirect("/");
        }

        return $next($request);
    }
}
