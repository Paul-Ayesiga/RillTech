<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PermissionGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $groups = PermissionGroup::withCount('permissions')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/PermissionGroups', [
            'groups' => $groups,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permission_groups,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        PermissionGroup::create($validated);

        return redirect()->back()->with('success', 'Permission group created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PermissionGroup $permissionGroup)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permission_groups', 'name')->ignore($permissionGroup->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $permissionGroup->update($validated);

        return redirect()->back()->with('success', 'Permission group updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PermissionGroup $permissionGroup)
    {
        // Check if the group has permissions
        if ($permissionGroup->permissions()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete a group that has permissions. Please reassign or delete the permissions first.');
        }

        $permissionGroup->delete();

        return redirect()->back()->with('success', 'Permission group deleted successfully.');
    }
}
