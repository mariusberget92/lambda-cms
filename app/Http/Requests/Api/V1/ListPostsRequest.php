<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ListPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['integer', 'min:1', 'max:100'],
            'category' => ['nullable', 'string'],
            'tag'      => ['nullable', 'string'],
            'search'   => ['nullable', 'string', 'max:100'],
        ];
    }
}
