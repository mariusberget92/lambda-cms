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
        $items = NavItem::with('page:id,slug,title')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'id'       => $item->id,
                'type'     => $item->type,
                'label'    => $item->label,
                'url'      => $item->url,
                'page_id'  => $item->page_id,
                'page_slug' => $item->page?->slug,
            ]);

        $pages = Page::published()->orderBy('title')
            ->get(['id', 'title', 'slug']);

        return Inertia::render('Navigation/Index', [
            'items' => $items,
            'pages' => $pages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'    => ['required', 'in:page,custom'],
            'label'   => ['required', 'string', 'max:100'],
            'url'     => ['nullable', 'required_if:type,custom', 'string', 'max:500'],
            'page_id' => ['nullable', 'required_if:type,page', 'exists:pages,id'],
        ]);

        $maxOrder = NavItem::max('sort_order') ?? -1;

        NavItem::create([
            'type'       => $validated['type'],
            'label'      => $validated['label'],
            'url'        => $validated['type'] === 'custom' ? $validated['url'] : null,
            'page_id'    => $validated['type'] === 'page' ? $validated['page_id'] : null,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('status', 'Navigation item added.');
    }

    public function update(Request $request, NavItem $navItem)
    {
        $validated = $request->validate([
            'type'    => ['required', 'in:page,custom'],
            'label'   => ['required', 'string', 'max:100'],
            'url'     => ['nullable', 'string', 'max:500'],
            'page_id' => ['nullable', 'exists:pages,id'],
        ]);

        $navItem->update([
            'type'    => $validated['type'],
            'label'   => $validated['label'],
            'url'     => $validated['type'] === 'custom' ? $validated['url'] : null,
            'page_id' => $validated['type'] === 'page' ? $validated['page_id'] : null,
        ]);

        return back()->with('status', 'Navigation item updated.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:nav_items,id'],
        ]);

        foreach ($request->ids as $order => $id) {
            NavItem::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->noContent();
    }

    public function destroy(NavItem $navItem)
    {
        $navItem->delete();
        return back()->with('status', 'Navigation item deleted.');
    }
}
