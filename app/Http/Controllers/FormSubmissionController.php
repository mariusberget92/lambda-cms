<?php
namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FormSubmissionController extends Controller
{
    // Admin: list submissions
    public function index(Form $form)
    {
        $submissions = $form->submissions()->paginate(25);
        return Inertia::render('Forms/Submissions', [
            'form'        => $form,
            'submissions' => $submissions,
        ]);
    }

    // Admin: delete submission
    public function destroy(Form $form, FormSubmission $submission)
    {
        $submission->delete();
        return back()->with('success', 'Submission deleted.');
    }

    // Public API: list forms (for block editor dropdown)
    public function apiList()
    {
        return response()->json(
            Form::orderBy('name')->get(['id', 'name', 'slug'])
        );
    }

    // Public API: get form with fields (for FormBlock frontend rendering)
    public function apiShow(Form $form)
    {
        return response()->json([
            'id'     => $form->id,
            'name'   => $form->name,
            'slug'   => $form->slug,
            'fields' => $form->fields->map(fn ($f) => [
                'id'            => $f->id,
                'type'          => $f->type,
                'label'         => $f->label,
                'name'          => $f->name,
                'placeholder'   => $f->placeholder,
                'help_text'     => $f->help_text,
                'required'      => $f->required,
                'options'       => $f->options,
                'default_value' => $f->default_value,
                'width'         => $f->width,
            ])->values(),
        ]);
    }

    // Public: submit a form
    public function submit(Request $request, Form $form)
    {
        $rules = [];
        foreach ($form->fields as $field) {
            if ($field->type === 'hidden') continue;
            $fieldRules = $field->required ? ['required'] : ['nullable'];
            if ($field->type === 'email')  $fieldRules[] = 'email';
            if ($field->type === 'number') $fieldRules[] = 'numeric';
            if ($field->type === 'url')    $fieldRules[] = 'url';
            if (in_array($field->type, ['checkboxes', 'checkbox'])) $fieldRules[] = 'array';
            $rules[$field->name] = $fieldRules;
        }

        $validated = $request->validate($rules);

        if ($form->store_submissions) {
            FormSubmission::create([
                'form_id'    => $form->id,
                'data'       => $validated,
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $form->success_message ?? 'Thank you! Your submission has been received.',
        ]);
    }
}
