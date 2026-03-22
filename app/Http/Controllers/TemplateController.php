<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::with('creator:id,name')
            ->latest()
            ->get()
            ->map(fn (Template $t) => [
                'id'         => $t->id,
                'title'      => $t->title,
                'type'       => $t->type,
                'status'     => $t->status,
                'updated_at' => $t->updated_at->toDateString(),
                'creator'    => $t->creator->name,
            ])
            ->groupBy('type');

        return Inertia::render('Templates/Index', ['templates' => $templates]);
    }

    public function create(Request $request)
    {
        $request->validate(['type' => ['required', 'in:blog-index,single-post,archive,search-results']]);

        return Inertia::render('Templates/Create', ['type' => $request->type]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:blog-index,single-post,archive,search-results'],
            'status'           => ['required', 'in:draft,published'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === 'published') {
            Template::where('type', $validated['type'])
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $template = Template::create([...$validated, 'user_id' => auth()->id()]);
        $template->saveRevision(auth()->id());

        return redirect()->route('templates.index')->with('status', 'Template created.');
    }

    public function edit(Request $request, Template $template)
    {
        $autosave = $template->autosave(auth()->id());

        return Inertia::render('Templates/Edit', [
            'template' => [
                'id'               => $template->id,
                'title'            => $template->title,
                'type'             => $template->type,
                'status'           => $template->status,
                'blocks'           => $template->blocks ?? [],
                'meta_title'       => $template->meta_title,
                'meta_description' => $template->meta_description,
                'meta_keywords'    => $template->meta_keywords,
            ],
            'autosave' => $autosave ? [
                'payload'    => $autosave->payload,
                'updated_at' => $autosave->updated_at->diffForHumans(),
            ] : null,
        ]);
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:blog-index,single-post,archive,search-results'],
            'status'           => ['required', 'in:draft,published'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === 'published') {
            Template::where('type', $validated['type'])
                ->where('id', '!=', $template->id)
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $template->saveRevision(auth()->id());
        $template->update($validated);
        $template->autosaves()->where('user_id', auth()->id())->delete();

        return redirect()->route('templates.index')->with('status', 'Template saved.');
    }

    public function destroy(Template $template)
    {
        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }
}
