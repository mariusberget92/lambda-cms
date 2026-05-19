<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const DEFAULT_ROLES = ['administrator', 'editor', 'author', 'contributor', 'user'];

    public function index()
    {
        $roles = Role::withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'is_system'   => $role->name === 'administrator',
                'deletable'   => ! in_array($role->name, self::DEFAULT_ROLES),
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
                'users_count' => $role->users_count,
            ]);

        return Inertia::render('Roles/Index', ['roles' => $roles]);
    }

    public function create()
    {
        return Inertia::render('Roles/Form', [
            'permissions' => $this->groupedPermissions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => strtolower(trim($validated['name']))]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('roles.index')
            ->with('status', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role)
    {
        return Inertia::render('Roles/Form', [
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'is_system'   => $role->name === 'administrator',
                'permissions' => $role->permissions->pluck('name')->values(),
            ],
            'permissions' => $this->groupedPermissions(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'administrator') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'The administrator role cannot be modified.');
        }

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => strtolower(trim($validated['name']))]);
        $role->syncPermissions($validated['permissions'] ?? []);

        // Flush the Spatie permission cache so changes take effect immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('status', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::DEFAULT_ROLES)) {
            return redirect()
                ->route('roles.index')
                ->with('error', "The \"{$role->name}\" role is a default role and cannot be deleted.");
        }

        $role->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('status', 'Role deleted.');
    }

    private function groupedPermissions(): array
    {
        return [
            'Posts'      => ['view posts', 'create posts', 'edit own posts', 'edit any post', 'delete own posts', 'delete any post', 'publish posts'],
            'Pages'      => ['view pages', 'create pages', 'edit pages', 'delete pages'],
            'Templates'  => ['view templates', 'create templates', 'edit templates', 'delete templates'],
            'Categories' => ['view categories', 'create categories', 'edit categories', 'delete categories'],
            'Tags'       => ['view tags', 'create tags', 'edit tags', 'delete tags'],
            'Media'      => ['view media', 'upload media', 'edit own media', 'edit any media', 'delete own media', 'delete any media'],
            'Comments'   => ['view comments', 'moderate comments', 'reply to comments', 'delete comments'],
            'Users'      => ['view users', 'create users', 'edit users', 'delete users', 'ban users'],
            'System'     => ['manage roles', 'manage settings', 'manage navigation', 'manage webhooks'],
        ];
    }
}
