<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\TemplateTypeRequest;
use App\Http\Requests\UpdateTemplateRequest;
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
                'id' => $t->id,
                'title' => $t->title,
                'type' => $t->type,
                'status' => $t->status,
                'is_system' => $t->is_system,
                'updated_at' => $t->updated_at->toDateString(),
                'creator' => $t->creator->name,
            ])
            ->groupBy('type');

        return Inertia::render('Templates/Index', ['templates' => $templates]);
    }

    public function create(TemplateTypeRequest $request)
    {
        $request->validated();

        return Inertia::render('Templates/Create', ['type' => $request->type]);
    }

    public function store(StoreTemplateRequest $request)
    {
        $validated = $request->validated();

        if ($validated['status'] === 'published' && $validated['type'] !== 'partial') {
            Template::where('type', $validated['type'])
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $template = Template::create([...$validated, 'user_id' => auth()->id()]);

        return redirect()->route('templates.edit', $template->id)->with('status', 'Template created.');
    }

    public function edit(Request $request, Template $template)
    {
        if ($template->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $autosave = $template->autosave(auth()->id());

        return Inertia::render('Templates/Edit', [
            'template' => [
                'id' => $template->id,
                'title' => $template->title,
                'type' => $template->type,
                'loop_source' => $template->loop_source ?? 'posts',
                'status' => $template->status,
                'is_system' => $template->is_system,
                'blocks' => $template->blocks ?? [],
                'meta_title' => $template->meta_title,
                'meta_description' => $template->meta_description,
                'meta_keywords' => $template->meta_keywords,
            ],
            'autosave' => $autosave ? [
                'payload' => $autosave->payload,
                'updated_at' => $autosave->updated_at->diffForHumans(),
            ] : null,
        ]);
    }

    public function update(UpdateTemplateRequest $request, Template $template)
    {
        if ($template->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $validated = $request->validated();

        if ($validated['status'] === 'published' && $validated['type'] !== 'partial') {
            Template::where('type', $validated['type'])
                ->where('id', '!=', $template->id)
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $template->update($validated);
        $template->saveRevision(auth()->id());
        $template->autosaves()->where('user_id', auth()->id())->delete();

        return redirect()->route('templates.edit', $template->id)->with('status', 'Template saved.');
    }

    public function destroy(Request $request, Template $template)
    {
        if ($template->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        if ($template->is_system) {
            abort(403, 'System templates cannot be deleted.');
        }

        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }
}
