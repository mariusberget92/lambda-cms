<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\QueryRequest;
use App\Services\QueryBuilder;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function __invoke(QueryRequest $request)
    {
        $validated = $request->validated();

        $result = $this->queryBuilder->resolve(
            $validated,
            $validated['url_params'] ?? []
        );

        return response()->json($result);
    }
}
