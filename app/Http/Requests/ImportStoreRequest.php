<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tmp_path'          => ['required', 'string'],
            'entities'          => ['required', 'array', 'min:1'],
            'entities.*'        => ['in:posts,categories,tags,media,templates'],
            'conflict_strategy' => ['required', 'in:skip,overwrite,duplicate'],
        ];
    }
}
