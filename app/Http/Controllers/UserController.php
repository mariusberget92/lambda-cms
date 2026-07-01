<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->input('search'), function ($q, $term) {
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'role' => $user->getRoleNames()->first(),
                'email_verified' => $user->hasVerifiedEmail(),
                'is_online' => $user->isOnline(),
                'last_seen_at' => $user->last_seen_at?->diffForHumans(),
                'is_banned' => $user->isBanned(),
                'ban_reason' => $user->ban_reason,
                'banned_until' => $user->banned_until?->toISOString(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'adminCount' => $this->adminCount(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Form', [
            'roles' => Role::orderBy('name')->pluck('name'),
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::password(16),
        ]);

        $user->assignRole($validated['role']);

        if ($validated['role'] === 'user' && ! empty($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        $user->notify(new WelcomeNotification);

        return redirect()
            ->route('users.index')
            ->with('status', "Invitation sent to {$user->email}.");
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'permissions' => $user->getDirectPermissions()->pluck('name')->values(),
                'is_banned' => $user->isBanned(),
                'ban_reason' => $user->ban_reason,
                'banned_until' => $user->banned_until?->toISOString(),
            ],
            'roles' => Role::orderBy('name')->pluck('name'),
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (
            $user->hasRole('administrator') &&
            $validated['role'] !== 'administrator' &&
            $this->adminCount() <= 1
        ) {
            return redirect()
                ->route('users.index')
                ->with('error', 'There must always be at least one administrator.');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles([$validated['role']]);

        if ($validated['role'] === 'user') {
            $user->syncPermissions($validated['permissions'] ?? []);
        } else {
            $user->syncPermissions([]);
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'User updated.');
    }

    private function adminCount(): int
    {
        return User::role('administrator')->count();
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('administrator') && $this->adminCount() <= 1) {
            return redirect()
                ->route('users.index')
                ->with('error', 'There must always be at least one administrator.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'User deleted.');
    }

    public function bulk(BulkUserRequest $request)
    {
        $validated = $request->validated();
        $currentUserId = $request->user()->id;
        $users = User::whereIn('id', $validated['ids'])
            ->where('id', '!=', $currentUserId)
            ->get();

        $adminCount = $this->adminCount();
        $users = $users->reject(function (User $user) use (&$adminCount) {
            if ($user->hasRole('administrator') && $adminCount <= 1) {
                return true;
            }
            if ($user->hasRole('administrator')) {
                $adminCount--;
            }

            return false;
        });

        match ($validated['action']) {
            'delete' => $users->each->delete(),
        };

        $count = $users->count();

        return redirect()->back()->with('status', "{$count} user".($count === 1 ? '' : 's').' deleted.');
    }
}
