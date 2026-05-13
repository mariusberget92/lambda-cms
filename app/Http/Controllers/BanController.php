<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class BanController extends Controller
{
    private const DURATIONS = [
        '1h'        => ['addHour',  []],
        '6h'        => ['addHours', [6]],
        '24h'       => ['addDay',   []],
        '7d'        => ['addWeek',  []],
        '30d'       => ['addMonth', []],
        'permanent' => null,
    ];

    public function ban(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        // Guard: cannot ban yourself or another admin
        if ($user->id === $request->user()->id || $user->hasRole('administrator')) {
            return redirect()->route('users.index')
                ->with('error', 'This user cannot be banned.');
        }

        $validated = $request->validate([
            'reason'   => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'in:' . implode(',', array_keys(self::DURATIONS))],
        ]);

        $bannedUntil = null;
        if ($validated['duration'] !== 'permanent') {
            [$method, $args] = self::DURATIONS[$validated['duration']];
            $bannedUntil = now()->{$method}(...$args);
        }

        $user->update([
            'banned_at'    => now(),
            'banned_until' => $bannedUntil,
            'ban_reason'   => $validated['reason'],
        ]);

        ActivityLogger::log('banned', "Banned user '{$user->name}' for: {$validated['reason']}", 'User', $user->id);

        return redirect()->route('users.index')
            ->with('status', "{$user->name} has been banned.");
    }

    public function unban(User $user): \Illuminate\Http\RedirectResponse
    {
        $user->update([
            'banned_at'    => null,
            'banned_until' => null,
            'ban_reason'   => null,
        ]);

        ActivityLogger::log('unbanned', "Unbanned user '{$user->name}'", 'User', $user->id);

        return redirect()->route('users.index')
            ->with('status', "{$user->name} has been unbanned.");
    }
}
