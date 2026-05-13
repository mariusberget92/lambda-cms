<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $action = $request->input('action');

        $logs = ActivityLog::with('user:id,name')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->latest()
            ->paginate(50)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => [
                'id'          => $log->id,
                'user'        => $log->user ? [
                    'name' => $log->user->name,
                    'role' => $log->user->getRoleNames()->first() ?? 'user',
                ] : null,
                'action'      => $log->action,
                'model_type'  => $log->model_type,
                'description' => $log->description,
                'metadata'    => $log->metadata,
                'ip_address'  => $log->ip_address,
                'created_at'  => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('ActivityLog/Index', [
            'logs'          => $logs,
            'activeAction'  => $action,
        ]);
    }
}
