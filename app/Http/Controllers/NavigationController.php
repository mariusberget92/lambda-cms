<?php

namespace App\Http\Controllers;

use App\Models\NavItem;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NavigationController extends Controller
{
    public function index()
    {
        $items = NavItem::with('page')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'id'           => $item->id,
                'type'         => $item->type,
                'label'        => $item->label,
                'url'          => $item->url,
                'page_id'      => $item->page_id,
                'sort_order'   => $item->sort_order,
                'resolved_url' => $item->resolvedUrl,
                'page_status'  => $item->page?->status,
            ]);

        $pages = Page::published()
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        return Inertia::render('Navigation/Index', compact('items', 'pages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'    => ['required', 'in:page,custom'],
            'label'   => ['required', 'string', 'max:255'],
            'url'     => ['required_if:type,custom', 'nullable', 'string', 'max:255'],
            'page_id' => ['required_if:type,page', 'nullable', 'exists:pages,id'],
        ]);

        $maxOrder = NavItem::max('sort_order') ?? -1;

        NavItem::create([
            ...$validated,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('navigation.index')->with('status', 'Navigation item added.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer', 'exists:nav_items,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['items'] as $data) {
            NavItem::where('id', $data['id'])->update(['sort_order' => $data['sort_order']]);
        }

        return response()->noContent();
    }

    public function destroy(NavItem $navItem)
    {
        $navItem->delete();

        return redirect()->route('navigation.index')->with('status', 'Navigation item removed.');
    }
}
