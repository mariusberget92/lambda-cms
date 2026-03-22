<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QueryController extends Controller
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'source'          => ['required', Rule::in(['posts', 'categories', 'tags', 'pages'])],
            'filters'         => ['nullable', 'array'],
            'filters.*.field' => ['nullable', 'string', 'max:50'],
            'filters.*.op'    => ['nullable', 'string', Rule::in(['=', '!=', 'not_empty', 'empty'])],
            'filters.*.value' => ['nullable'],
            'sort'            => ['nullable', 'array'],
            'sort.field'      => ['nullable', 'string', 'max:50'],
            'sort.direction'  => ['nullable', Rule::in(['asc', 'desc'])],
            'limit'           => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset'          => ['nullable', 'integer', 'min:0'],
            'url_params'      => ['nullable', 'array'],
        ]);

        $result = $this->queryBuilder->resolve(
            $validated,
            $validated['url_params'] ?? []
        );

        return response()->json($result);
    }
}
