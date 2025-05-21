<?php

use App\Http\Controllers\Admin\Settings\PasswordController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use App\Http\Controllers\Admin\Settings\SessionController;
use App\Http\Controllers\Admin\RolesPermissionsController;
use App\Http\Controllers\Admin\PermissionGroupController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('admin')->middleware(['auth', 'verified', 'role:super-admin'])->group(function () {

    Route::get('dashboard', function () {
        return Inertia::render('admin/Dashboard');
    })->name('admin.dashboard');

    // Roles and Permissions Management
    Route::get('roles-permissions', [RolesPermissionsController::class, 'index'])->name('admin.roles-permissions');

    // Role routes
    Route::post('roles', [RolesPermissionsController::class, 'storeRole'])->name('admin.roles.store');
    Route::put('roles/{role}', [RolesPermissionsController::class, 'updateRole'])->name('admin.roles.update');
    Route::delete('roles/{role}', [RolesPermissionsController::class, 'destroyRole'])->name('admin.roles.destroy');

    // Permission routes
    Route::post('permissions', [RolesPermissionsController::class, 'storePermission'])->name('admin.permissions.store');
    Route::put('permissions/{permission}', [RolesPermissionsController::class, 'updatePermission'])->name('admin.permissions.update');
    Route::delete('permissions/{permission}', [RolesPermissionsController::class, 'destroyPermission'])->name('admin.permissions.destroy');

    // Permission bulk action routes
    Route::post('permissions/bulk-delete', [RolesPermissionsController::class, 'bulkDeletePermissions'])->name('admin.permissions.bulk-delete');
    Route::post('permissions/bulk-assign-group', [RolesPermissionsController::class, 'bulkAssignGroup'])->name('admin.permissions.bulk-assign-group');
    Route::post('permissions/bulk-assign-roles', [RolesPermissionsController::class, 'bulkAssignRoles'])->name('admin.permissions.bulk-assign-roles');
    Route::get('permissions/export-excel', [RolesPermissionsController::class, 'exportExcel'])->name('admin.permissions.export-excel');
    Route::get('permissions/export-pdf', [RolesPermissionsController::class, 'exportPdf'])->name('admin.permissions.export-pdf');

    // Permission Group routes
    Route::get('permission-groups', [PermissionGroupController::class, 'index'])->name('admin.permission-groups.index');
    Route::post('permission-groups', [PermissionGroupController::class, 'store'])->name('admin.permission-groups.store');
    Route::put('permission-groups/{permissionGroup}', [PermissionGroupController::class, 'update'])->name('admin.permission-groups.update');
    Route::delete('permission-groups/{permissionGroup}', [PermissionGroupController::class, 'destroy'])->name('admin.permission-groups.destroy');


    Route::redirect('settings', 'admin/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('admin.profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('admin.password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('admin.password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('admin/settings/Appearance');
    })->name('admin.appearance');

    Route::get('settings/session',[SessionController::class, 'index'])->name('admin.session.index');
    Route::delete('settings/session/{id}', [SessionController::class, 'destroy'])->name('admin.session.destroy');
    Route::post('settings/session/logout-others', [SessionController::class, 'logoutOthers'])->name('admin.session.logout.others');

    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'delete']);
    Route::delete('notifications', [NotificationController::class, 'deleteAll']);

    // User Management
    Route::resource('users', UserController::class)->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
});
