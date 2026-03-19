<?php

namespace App\Http\Middleware;

use App\Models\Comment;
use App\Models\NavItem;
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
            "appName" => config('app.name', 'Lambda CMS'),
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
            "currentRoute"         => $request->route()?->getName(),
            "pendingCommentsCount" => fn () => $request->user()?->hasRole('administrator')
                ? Comment::pending()->count()
                : null,
            'navItems' => fn () => NavItem::with('page')
                ->orderBy('sort_order')
                ->get()
                ->filter(fn ($item) =>
                    $item->type === 'custom' ||
                    ($item->page && $item->page->status === 'published')
                )
                ->map(fn ($item) => [
                    'label' => $item->label,
                    'url'   => $item->resolvedUrl,
                ])
                ->values(),
        ]);
    }
}
