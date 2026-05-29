<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entities'            => ['required', 'array', 'min:1'],
            'entities.*'          => ['in:posts,categories,tags,media,templates'],
            'include_media_files' => ['nullable', 'boolean'],
        ];
    }
}
