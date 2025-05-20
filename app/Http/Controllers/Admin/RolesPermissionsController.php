<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class RolesPermissionsController extends Controller
{
    /**
     * Display the roles and permissions management page.
     */
    public function index()
    {
        // Get all roles with their permissions
        $roles = Role::with('permissions')->get();

        // Find super-admin role
        $superAdmin = $roles->where('name', 'super-admin')->first();

        // If super-admin exists, ensure it has all permissions
        if ($superAdmin) {
            // Get all permissions
            $allPermissions = Permission::all();

            // Give all permissions to super-admin
            foreach ($allPermissions as $permission) {
                if (!$superAdmin->hasPermissionTo($permission)) {
                    $superAdmin->givePermissionTo($permission);
                }
            }

            // Reload roles with updated permissions
            $roles = Role::with('permissions')->get();
        }

        // Get permission groups with their permissions and roles
        $permissionGroups = PermissionGroup::with(['permissions.roles'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        // Get permissions without a group, including their roles
        $ungroupedPermissions = Permission::whereNull('group_id')->get();

        // Get all permissions for the role form
        $allPermissions = Permission::all();

        // Debug: Log the first permission with its roles
        if ($ungroupedPermissions->count() > 0) {
            Log::info('First ungrouped permission: ' . $ungroupedPermissions->first()->name);
            Log::info('Roles count: ' . $ungroupedPermissions->first()->roles->count());
            Log::info('Roles: ' . json_encode($ungroupedPermissions->first()->roles->pluck('name')));
        }

        return Inertia::render('admin/RolesPermissions', [
            'roles' => $roles,
            'permissions' => $allPermissions,
            'permissionGroups' => $permissionGroups,
            'ungroupedPermissions' => $ungroupedPermissions,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    /**
     * Update the specified role.
     */
    public function updateRole(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroyRole(Role $role)
    {
        // Prevent deleting super-admin role
        if ($role->name === 'super-admin') {
            return redirect()->back()->with('error', 'Cannot delete the super-admin role.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }

    /**
     * Store a newly created permission.
     */
    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'guard_name' => ['required', 'string', 'max:255'],
            'group_id' => ['nullable', 'exists:permission_groups,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        // Create the permission
        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'],
            'group_id' => $validated['group_id'] ?? null,
        ]);

        // Find super-admin role and always give it the permission
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        // Attach roles if provided
        if (isset($validated['roles']) && !empty($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();
            foreach ($roles as $role) {
                if ($role->name !== 'super-admin') { // Skip super-admin as we already gave it the permission
                    $role->givePermissionTo($permission);
                }
            }
        }

        return redirect()->back()->with('success', 'Permission created successfully.');
    }

    /**
     * Update the specified permission.
     */
    public function updatePermission(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'guard_name' => ['required', 'string', 'max:255'],
            'group_id' => ['nullable', 'exists:permission_groups,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        // Update the permission
        $permission->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'],
            'group_id' => $validated['group_id'] ?? null,
        ]);

        // Sync roles if provided
        if (isset($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();

            // Get all roles
            $allRoles = Role::all();

            // Find super-admin role
            $superAdmin = $allRoles->where('name', 'super-admin')->first();

            // Remove permission from all non-super-admin roles first
            foreach ($allRoles as $role) {
                if ($role->name !== 'super-admin' && $role->hasPermissionTo($permission)) {
                    $role->revokePermissionTo($permission);
                }
            }

            // Attach permission to selected roles
            foreach ($roles as $role) {
                if ($role->name !== 'super-admin') { // Skip super-admin as we'll handle it separately
                    $role->givePermissionTo($permission);
                }
            }

            // Always ensure super-admin has the permission
            if ($superAdmin && !$superAdmin->hasPermissionTo($permission)) {
                $superAdmin->givePermissionTo($permission);
            }
        }

        return redirect()->back()->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified permission.
     */
    public function destroyPermission(Permission $permission)
    {
        $permission->delete();

        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }
}
