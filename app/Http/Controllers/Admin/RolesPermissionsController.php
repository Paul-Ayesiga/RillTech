<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PermissionsExport;
use App\Exports\PermissionsPdfExport;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        return Inertia::render('admin/RolesPermissions', [
            'roles' => $roles,
            // Defer loading of permissions data for better performance
            'permissions' => Inertia::defer(fn () => Permission::all()),
            'permissionGroups' => Inertia::defer(fn () => PermissionGroup::with(['permissions.roles'])
                ->orderBy('display_order')
                ->orderBy('name')
                ->get()),
            'ungroupedPermissions' => Inertia::defer(fn () => Permission::with('roles')
                ->whereNull('group_id')
                ->get()),
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

    /**
     * Bulk delete permissions.
     */
    public function bulkDeletePermissions(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:permissions,id'],
        ]);

        // Get the permissions
        $permissions = Permission::whereIn('id', $validated['ids'])->get();

        // Delete the permissions
        foreach ($permissions as $permission) {
            $permission->delete();
        }

        return redirect()->back()->with('success', count($validated['ids']) . ' permissions deleted successfully.');
    }

    /**
     * Bulk assign permissions to a group.
     */
    public function bulkAssignGroup(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:permissions,id'],
            'group_id' => ['nullable', 'exists:permission_groups,id'],
        ]);

        // Get the permissions
        $permissions = Permission::whereIn('id', $validated['ids'])->get();

        // Update the group_id for each permission
        foreach ($permissions as $permission) {
            $permission->update([
                'group_id' => $validated['group_id'],
            ]);
        }

        $groupName = $validated['group_id'] ? PermissionGroup::find($validated['group_id'])->name : 'None';
        return redirect()->back()->with('success', count($validated['ids']) . ' permissions assigned to group: ' . $groupName);
    }

    /**
     * Bulk assign permissions to roles.
     */
    public function bulkAssignRoles(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:permissions,id'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        // Get the permissions and roles
        $permissions = Permission::whereIn('id', $validated['ids'])->get();
        $roles = Role::whereIn('id', $validated['roles'])->get();

        // Get all roles
        $allRoles = Role::all();

        // Find super-admin role
        $superAdmin = $allRoles->where('name', 'super-admin')->first();

        // For each permission
        foreach ($permissions as $permission) {
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

        return redirect()->back()->with('success', count($validated['ids']) . ' permissions assigned to selected roles.');
    }

    /**
     * Export permissions to Excel.
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $permissions = null;

        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $permissions = Permission::with(['group', 'roles'])->whereIn('id', $ids)->get();
        }

        return Excel::download(new PermissionsExport($permissions), 'permissions.xlsx');
    }

    /**
     * Export permissions to PDF.
     */
    public function exportPdf(Request $request)
    {
        $permissions = null;

        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $permissions = Permission::with(['group', 'roles'])->whereIn('id', $ids)->get();
        }

        $pdf = new PermissionsPdfExport($permissions);
        $output = $pdf->export();

        return response($output)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="permissions.pdf"');
    }
}
