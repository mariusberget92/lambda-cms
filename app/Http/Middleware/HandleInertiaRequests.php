<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = "app";

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            "auth" => [
                "user" => $request->user() ? array_merge(
                    $request->user()->only("id", "name", "email", "avatar_url"),
                    [
                        "role"           => $request->user()->getRoleNames()->first(),
                        "email_verified" => $request->user()->hasVerifiedEmail(),
                    ]
                ) : null,
            ],
            "flash" => [
                "status" => fn () => $request->session()->get("status"),
                "error"  => fn () => $request->session()->get("error"),
            ],
            "currentRoute" => $request->route()?->getName(),
        ]);
    }
}
