<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source'          => ['required', Rule::in(['posts', 'categories', 'tags', 'pages'])],
            'filters'         => ['nullable', 'array'],
            'filters.*.field' => ['nullable', 'string', 'max:50'],
            'filters.*.op'    => ['nullable', 'string', Rule::in(['=', '!=', 'contains', 'not_empty', 'empty'])],
            'filters.*.value' => ['nullable'],
            'sort'            => ['nullable', 'array'],
            'sort.field'      => ['nullable', 'string', 'max:50'],
            'sort.direction'  => ['nullable', Rule::in(['asc', 'desc'])],
            'limit'           => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset'          => ['nullable', 'integer', 'min:0'],
            'url_params'      => ['nullable', 'array'],
        ];
    }
}
