<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');
        $role = $request->input('role', '');

        $query = User::with('roles')->orderBy('created_at', 'desc');

        // Search by name or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->paginate($perPage);

        return Inertia::render('admin/Users', [
            'users' => $users,
            'roles' => Role::all(),
            'filters' => [
                'search' => $search,
                'role' => $role,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return Inertia::render('admin/UserForm', [
            'roles' => Role::all(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $roles = Role::whereIn('id', $validated['roles'])->get();
        $user->syncRoles($roles);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['roles.permissions', 'permissions'])->findOrFail($id);
        $allRoles = Role::with('permissions')->get();
        $allPermissions = Permission::all();

        return Inertia::render('admin/UserDetail', [
            'user' => $user,
            'allRoles' => $allRoles,
            'allPermissions' => $allPermissions,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);

        return Inertia::render('admin/UserForm', [
            'user' => $user,
            'roles' => Role::all(),
            'userRoles' => $user->roles->pluck('id')->toArray(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', Password::defaults()],
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $roles = Role::whereIn('id', $validated['roles'])->get();
        $user->syncRoles($roles);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Update user roles and permissions.
     */
    public function updateRolesPermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Sync roles
        $roles = Role::whereIn('id', $validated['roles'])->get();
        $user->syncRoles($roles);

        // Sync direct permissions (if any)
        if (isset($validated['permissions']) && !empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $user->syncPermissions($permissions);
        }

        return redirect()->back()->with('success', 'User roles and permissions updated successfully.');
    }

    /**
     * Bulk delete users.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $validated['user_ids'];

        // Prevent self-deletion
        if (in_array(auth()->id(), $userIds)) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Get users to check for super-admins
        $usersToDelete = User::with('roles')->whereIn('id', $userIds)->get();

        // Check if any users are super-admins
        $superAdminUsers = $usersToDelete->filter(function ($user) {
            return $user->hasRole('super-admin');
        });

        if ($superAdminUsers->count() > 0) {
            $superAdminNames = $superAdminUsers->pluck('name')->join(', ');
            return back()->withErrors(['error' => "Cannot delete super-admin accounts: {$superAdminNames}"]);
        }

        $deletedCount = User::whereIn('id', $userIds)->delete();

        return redirect()->back()->with('success', "Successfully deleted {$deletedCount} users.");
    }

    /**
     * Bulk assign roles to users.
     */
    public function bulkAssignRoles(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();
        $roles = Role::whereIn('id', $validated['role_ids'])->get();

        $assignedCount = 0;

        foreach ($users as $user) {
            foreach ($roles as $role) {
                // Only assign role if user doesn't already have it
                if (!$user->hasRole($role)) {
                    $user->assignRole($role);
                    $assignedCount++;
                }
            }
        }

        if ($assignedCount > 0) {
            return redirect()->back()->with('success', "Successfully assigned {$assignedCount} new role assignments to {$users->count()} users.");
        } else {
            return redirect()->back()->with('info', "No new roles were assigned. All selected users already have the selected roles.");
        }
    }

    /**
     * Send bulk email to users.
     */
    public function bulkEmail(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();

        // Dispatch job for each user
        foreach ($users as $user) {
            \App\Jobs\SendBulkEmailJob::dispatch($user, $validated['subject'], $validated['message']);
        }

        return redirect()->back()->with('success', "Email queued for {$users->count()} users.");
    }

    /**
     * Export users to Excel.
     */
    public function exportExcel(Request $request)
    {
        $users = null;

        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $users = User::with('roles')->whereIn('id', $ids)->get();
        }

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\UsersExport($users), 'users.xlsx');
    }

    /**
     * Export users to PDF.
     */
    public function exportPdf(Request $request)
    {
        $users = null;

        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $users = User::with('roles')->whereIn('id', $ids)->get();
        }

        $pdf = new \App\Exports\UsersPdfExport($users);
        $output = $pdf->export();

        return response($output)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="users.pdf"');
    }

    /**
     * Suspend a user account.
     */
    public function suspend(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot suspend your own account.']);
        }

        if ($user->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Super-admin accounts cannot be suspended.']);
        }

        $user->update(['status' => 'suspended']);

        return redirect()->back()->with('success', "User {$user->name} has been suspended.");
    }

    /**
     * Ban a user account.
     */
    public function ban(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot ban your own account.']);
        }

        if ($user->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'Super-admin accounts cannot be banned.']);
        }

        $user->update(['status' => 'banned']);

        return redirect()->back()->with('success', "User {$user->name} has been banned.");
    }

    /**
     * Activate a user account.
     */
    public function activate(User $user)
    {
        $user->update(['status' => 'active']);

        return redirect()->back()->with('success', "User {$user->name} has been activated.");
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting yourself
            if (auth()->id() === $user->id) {
                return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
            }

            // Prevent deleting super-admins
            if ($user->hasRole('super-admin')) {
                return redirect()->back()->withErrors(['error' => 'Super-admin accounts cannot be deleted.']);
            }

            $user->delete();

            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to delete user: ' . $e->getMessage()]);
        }
    }
}
