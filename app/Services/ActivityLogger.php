<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    public static function log(
        string $action,
        string $description,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $metadata = null
    ): void {
        $request = app(Request::class);

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'model_type' => $modelType,
            'model_id'   => $modelId,
            'description' => $description,
            'metadata'   => $metadata,
            'ip_address' => $request->ip(),
        ]);
    }
}
