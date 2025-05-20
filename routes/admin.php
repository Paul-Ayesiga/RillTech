<?php

use App\Http\Controllers\Admin\Settings\PasswordController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use App\Http\Controllers\Admin\RolesPermissionsController;
use App\Http\Controllers\Admin\PermissionGroupController;
use App\Http\Controllers\Admin\NotificationController;
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

    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'delete']);
    Route::delete('notifications', [NotificationController::class, 'deleteAll']);
});
