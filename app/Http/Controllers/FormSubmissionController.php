<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormSubmissionController extends Controller
{
    public function index(): Response
    {
        $submissions = FormSubmission::latest()
            ->paginate(25)
            ->through(fn (FormSubmission $s) => [
                'id'         => $s->id,
                'form_name'  => $s->form_name,
                'page_slug'  => $s->page_slug,
                'data'       => $s->data,
                'ip_address' => $s->ip_address,
                'created_at' => $s->created_at->diffForHumans(),
            ]);

        return Inertia::render('Forms/Index', [
            'submissions' => $submissions,
            'total'       => FormSubmission::count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'form_name' => ['nullable', 'string', 'max:255'],
            'page_slug' => ['nullable', 'string', 'max:255'],
            'data'      => ['required', 'array'],
            'data.*'    => ['nullable', 'string', 'max:5000'],
        ]);

        FormSubmission::create([
            'form_name'  => $request->input('form_name'),
            'page_slug'  => $request->input('page_slug'),
            'data'       => $request->input('data'),
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(FormSubmission $submission): RedirectResponse
    {
        $submission->delete();

        return redirect()->route('form-submissions.index')
            ->with('status', 'Submission deleted.');
    }
}
