<?php

use App\Http\Controllers\Admin\Settings\PasswordController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use App\Http\Controllers\Admin\Settings\SessionController;
use App\Http\Controllers\Admin\RolesPermissionsController;
use App\Http\Controllers\Admin\PermissionGroupController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\NewsletterSubscriptionController;
use App\Http\Controllers\Admin\ContactSubmissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemoRequestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('admin')->middleware(['auth', 'verified', 'user.status', 'role:admin|super-admin'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

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
    Route::get('notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('admin.notifications.read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('admin.notifications.mark-all-read');
    Route::delete('notifications/{id}', [NotificationController::class, 'delete'])->name('admin.notifications.delete');
    Route::delete('notifications', [NotificationController::class, 'deleteAll'])->name('admin.notifications.delete-all');

    // User Management - Specific routes MUST come before resource routes
    Route::get('users/export-excel', [UserController::class, 'exportExcel'])->name('admin.users.export-excel');
    Route::get('users/export-pdf', [UserController::class, 'exportPdf'])->name('admin.users.export-pdf');
    Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('admin.users.bulk-delete');
    Route::post('users/bulk-assign-roles', [UserController::class, 'bulkAssignRoles'])->name('admin.users.bulk-assign-roles');
    Route::post('users/bulk-email', [UserController::class, 'bulkEmail'])->name('admin.users.bulk-email');
    Route::put('users/{user}/roles-permissions', [UserController::class, 'updateRolesPermissions'])->name('admin.users.update-roles-permissions');
    Route::patch('users/{user}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
    Route::patch('users/{user}/ban', [UserController::class, 'ban'])->name('admin.users.ban');
    Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('admin.users.activate');

    // User resource routes
    Route::resource('users', UserController::class)->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);

    // Newsletter Subscription Management
    Route::resource('newsletter-subscriptions', NewsletterSubscriptionController::class)->names([
        'index' => 'admin.newsletter-subscriptions.index',
        'destroy' => 'admin.newsletter-subscriptions.destroy',
    ])->only(['index', 'destroy']);

    // Newsletter bulk operations
    Route::post('newsletter-subscriptions/bulk-delete', [NewsletterSubscriptionController::class, 'bulkDelete'])->name('admin.newsletter-subscriptions.bulk-delete');
    Route::post('newsletter-subscriptions/bulk-email', [NewsletterSubscriptionController::class, 'sendBulkEmail'])->name('admin.newsletter-subscriptions.bulk-email');
    Route::get('newsletter-subscriptions/export-csv', [NewsletterSubscriptionController::class, 'exportCsv'])->name('admin.newsletter-subscriptions.export-csv');

    // Contact Submission Management
    Route::resource('contact-submissions', ContactSubmissionController::class)->names([
        'index' => 'admin.contact-submissions.index',
        'update' => 'admin.contact-submissions.update',
        'destroy' => 'admin.contact-submissions.destroy',
    ])->only(['index', 'update', 'destroy']);

    // Contact submission email reply
    Route::post('contact-submissions/{contactSubmission}/reply', [ContactSubmissionController::class, 'sendReply'])->name('admin.contact-submissions.reply');

    // Contact bulk operations
    Route::post('contact-submissions/bulk-delete', [ContactSubmissionController::class, 'bulkDelete'])->name('admin.contact-submissions.bulk-delete');
    Route::get('contact-submissions/export-csv', [ContactSubmissionController::class, 'exportCsv'])->name('admin.contact-submissions.export-csv');

    // Demo Request Management
    Route::get('demo-requests', [DemoRequestController::class, 'index'])->name('admin.demo-requests.index');
    Route::get('demo-requests/{demoRequest}', [DemoRequestController::class, 'show'])->name('admin.demo-requests.show');
    Route::put('demo-requests/{demoRequest}/status', [DemoRequestController::class, 'updateStatus'])->name('admin.demo-requests.update-status');
    Route::post('demo-requests/bulk-update', [DemoRequestController::class, 'bulkUpdate'])->name('admin.demo-requests.bulk-update');
});
