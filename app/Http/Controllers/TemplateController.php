<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\ActivityLogger;
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
                'is_system'  => $t->is_system,
                'updated_at' => $t->updated_at->toDateString(),
                'creator'    => $t->creator->name,
            ])
            ->groupBy('type');

        return Inertia::render('Templates/Index', ['templates' => $templates]);
    }

    public function create(Request $request)
    {
        $request->validate(['type' => ['required', 'in:blog-index,single-post,archive,search-results,partial']]);

        return Inertia::render('Templates/Create', ['type' => $request->type]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:blog-index,single-post,archive,search-results,partial'],
            'loop_source'      => ['nullable', 'in:posts,categories,tags,pages'],
            'status'           => ['required', 'in:draft,published'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === 'published' && $validated['type'] !== 'partial') {
            Template::where('type', $validated['type'])
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $template = Template::create([...$validated, 'user_id' => auth()->id()]);

        ActivityLogger::log('created', "Created template '{$template->title}' ({$template->type})", 'Template', $template->id);

        return redirect()->route('templates.edit', $template->id)->with('status', 'Template created.');
    }

    public function edit(Request $request, Template $template)
    {
        if ($template->user_id !== $request->user()->id && !$request->user()->hasRole('administrator')) {
            abort(403);
        }

        $autosave = $template->autosave(auth()->id());

        return Inertia::render('Templates/Edit', [
            'template' => [
                'id'               => $template->id,
                'title'            => $template->title,
                'type'             => $template->type,
                'loop_source'      => $template->loop_source ?? 'posts',
                'status'           => $template->status,
                'is_system'        => $template->is_system,
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
        if ($template->user_id !== $request->user()->id && !$request->user()->hasRole('administrator')) {
            abort(403);
        }

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:blog-index,single-post,archive,search-results,partial'],
            'loop_source'      => ['nullable', 'in:posts,categories,tags,pages'],
            'status'           => ['required', 'in:draft,published'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === 'published' && $validated['type'] !== 'partial') {
            Template::where('type', $validated['type'])
                ->where('id', '!=', $template->id)
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $wasPublished = $template->status !== 'published' && $validated['status'] === 'published';

        $template->update($validated);
        $template->saveRevision(auth()->id());
        $template->autosaves()->where('user_id', auth()->id())->delete();

        ActivityLogger::log(
            $wasPublished ? 'published' : 'updated',
            $wasPublished ? "Published template '{$template->title}'" : "Updated template '{$template->title}'",
            'Template', $template->id
        );

        return redirect()->route('templates.edit', $template->id)->with('status', 'Template saved.');
    }

    public function destroy(Request $request, Template $template)
    {
        if ($template->user_id !== $request->user()->id && !$request->user()->hasRole('administrator')) {
            abort(403);
        }

        if ($template->is_system) {
            abort(403, 'System templates cannot be deleted.');
        }

        ActivityLogger::log('deleted', "Deleted template '{$template->title}'", 'Template', $template->id);

        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }
}
