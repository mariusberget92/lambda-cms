<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:blog-index,single-post,archive,search-results,partial,header,footer'],
            'loop_source' => ['nullable', 'in:posts,categories,tags,pages'],
            'status' => ['required', 'in:draft,published'],
            'blocks' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }
}
