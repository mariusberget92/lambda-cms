<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->latest()
            ->paginate(20)
            ->through(fn ($user) => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'avatar_url'      => $user->avatar_url,
                'role'            => $user->getRoleNames()->first(),
                'email_verified'  => $user->hasVerifiedEmail(),
                'is_online'       => $user->isOnline(),
                'last_seen_at'    => $user->last_seen_at?->diffForHumans(),
                'is_banned'       => $user->isBanned(),
                'ban_reason'      => $user->ban_reason,
                'banned_until'    => $user->banned_until?->toISOString(),
            ]);

        return Inertia::render('Users/Index', [
            'users'      => $users,
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Form', [
            'roles'      => Role::orderBy('name')->pluck('name'),
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function store(Request $request)
    {
        abort_if(! $request->user()->can('create users'), 403);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'  => ['required', 'string', Rule::in(Role::pluck('name')->toArray())],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Str::password(16),
        ]);

        $user->assignRole($validated['role']);
        $user->notify(new WelcomeNotification());

        return redirect()
            ->route('users.index')
            ->with('status', "Invitation sent to {$user->email}.");
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Form', [
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'         => $user->getRoleNames()->first(),
                'is_banned'    => $user->isBanned(),
                'ban_reason'   => $user->ban_reason,
                'banned_until' => $user->banned_until?->toISOString(),
            ],
            'roles'      => Role::orderBy('name')->pluck('name'),
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if(! $request->user()->can('edit users'), 403);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'  => ['required', 'string', Rule::in(Role::pluck('name')->toArray())],
        ]);

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
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles([$validated['role']]);

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
        abort_if(! $request->user()->can('delete users'), 403);

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
}
