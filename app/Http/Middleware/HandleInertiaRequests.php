<?php

namespace App\Http\Middleware;

use App\Models\Comment;
use App\Models\NavItem;
use App\Models\Setting;
use App\Models\Template;
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
                        "role"               => $request->user()->getRoleNames()->first(),
                        "email_verified"     => $request->user()->hasVerifiedEmail(),
                        "two_factor_enabled" => $request->user()->hasTwoFactorEnabled(),
                    ]
                ) : null,
            ],
            "flash" => [
                "status"       => fn () => $request->session()->get("status"),
                "error"        => fn () => $request->session()->get("error"),
                "mail_status"  => fn () => $request->session()->get("mail_status"),
                "mail_error"   => fn () => $request->session()->get("mail_error"),
            ],
            "currentRoute"         => $request->route()?->getName(),
            "pendingCommentsCount" => fn () => $request->user()?->hasRole('administrator')
                ? Comment::pending()->count()
                : null,
            'accentColor' => fn () => Setting::get('site.accent_color') ?: null,
            'sharedTemplates' => fn () => Template::published()
                ->get(['id', 'title', 'type', 'blocks'])
                ->toArray(),
            'headerBlocks' => fn () => Template::activeFor('header')?->blocks ?? [],
            'footerBlocks' => fn () => Template::activeFor('footer')?->blocks ?? [],
            'navItems' => fn () => NavItem::with('page:id,slug')
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($item) => [
                    'label' => $item->label,
                    'url'   => $item->getResolvedUrl(),
                ])
                ->filter(fn ($item) => $item['url'] !== null)
                ->values()
                ->toArray(),
        ]);
    }
}
