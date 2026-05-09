<?php
namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FormController extends Controller
{
    public function index()
    {
        $forms = Form::withCount(['fields', 'submissions'])->latest()->paginate(15);
        return Inertia::render('Forms/Index', ['forms' => $forms]);
    }

    public function create()
    {
        return Inertia::render('Forms/Edit', ['form' => null, 'fields' => []]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', 'unique:forms,slug', 'regex:/^[a-z0-9\-]+$/'],
            'description'       => ['nullable', 'string'],
            'success_message'   => ['nullable', 'string'],
            'notify_email'      => ['nullable', 'email'],
            'store_submissions' => ['boolean'],
            'fields'            => ['array'],
            'fields.*.type'          => ['required', 'string'],
            'fields.*.label'         => ['required', 'string', 'max:255'],
            'fields.*.name'          => ['required', 'string', 'max:255'],
            'fields.*.placeholder'   => ['nullable', 'string'],
            'fields.*.help_text'     => ['nullable', 'string'],
            'fields.*.required'      => ['boolean'],
            'fields.*.options'       => ['nullable', 'array'],
            'fields.*.default_value' => ['nullable', 'string'],
            'fields.*.width'         => ['nullable', 'string'],
        ]);

        $form = Form::create([
            'name'              => $validated['name'],
            'slug'              => $validated['slug'],
            'description'       => $validated['description'] ?? null,
            'success_message'   => $validated['success_message'] ?? 'Thank you! Your submission has been received.',
            'notify_email'      => $validated['notify_email'] ?? null,
            'store_submissions' => $validated['store_submissions'] ?? true,
        ]);

        foreach ($validated['fields'] ?? [] as $i => $fieldData) {
            $form->fields()->create(array_merge($fieldData, ['order' => $i]));
        }

        return redirect()->route('forms.edit', $form)->with('success', 'Form created.');
    }

    public function edit(Form $form)
    {
        return Inertia::render('Forms/Edit', [
            'form'   => $form,
            'fields' => $form->fields()->get(),
        ]);
    }

    public function update(Request $request, Form $form)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', Rule::unique('forms', 'slug')->ignore($form->id), 'regex:/^[a-z0-9\-]+$/'],
            'description'       => ['nullable', 'string'],
            'success_message'   => ['nullable', 'string'],
            'notify_email'      => ['nullable', 'email'],
            'store_submissions' => ['boolean'],
            'fields'            => ['array'],
            'fields.*.id'            => ['nullable', 'integer'],
            'fields.*.type'          => ['required', 'string'],
            'fields.*.label'         => ['required', 'string', 'max:255'],
            'fields.*.name'          => ['required', 'string', 'max:255'],
            'fields.*.placeholder'   => ['nullable', 'string'],
            'fields.*.help_text'     => ['nullable', 'string'],
            'fields.*.required'      => ['boolean'],
            'fields.*.options'       => ['nullable', 'array'],
            'fields.*.default_value' => ['nullable', 'string'],
            'fields.*.width'         => ['nullable', 'string'],
        ]);

        $form->update([
            'name'              => $validated['name'],
            'slug'              => $validated['slug'],
            'description'       => $validated['description'] ?? null,
            'success_message'   => $validated['success_message'] ?? 'Thank you! Your submission has been received.',
            'notify_email'      => $validated['notify_email'] ?? null,
            'store_submissions' => $validated['store_submissions'] ?? true,
        ]);

        $incomingIds = collect($validated['fields'] ?? [])->pluck('id')->filter()->toArray();
        $form->fields()->whereNotIn('id', $incomingIds)->delete();

        foreach ($validated['fields'] ?? [] as $i => $fieldData) {
            $id = $fieldData['id'] ?? null;
            $payload = array_merge($fieldData, ['order' => $i]);
            unset($payload['id']);
            if ($id) {
                FormField::where('id', $id)->where('form_id', $form->id)->update($payload);
            } else {
                $form->fields()->create($payload);
            }
        }

        return redirect()->route('forms.edit', $form)->with('success', 'Form saved.');
    }

    public function destroy(Form $form)
    {
        $form->delete();
        return redirect()->route('forms.index')->with('success', 'Form deleted.');
    }
}
