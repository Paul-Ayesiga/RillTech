<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        // User management permissions
        Permission::create(['name' => 'view users', 'guard_name' => 'web']);
        Permission::create(['name' => 'create users', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit users', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete users', 'guard_name' => 'web']);

        // Role management permissions
        Permission::create(['name' => 'view roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'create roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete roles', 'guard_name' => 'web']);

        // Permission management
        Permission::create(['name' => 'view permissions', 'guard_name' => 'web']);
        Permission::create(['name' => 'create permissions', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit permissions', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete permissions', 'guard_name' => 'web']);

        // Client permissions
        Permission::create(['name' => 'access dashboard', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit profile', 'guard_name' => 'web']);
        Permission::create(['name' => 'change password', 'guard_name' => 'web']);

        // Assign permissions to client role
        $clientRole = Role::findByName('client', 'web');
        $clientRole->givePermissionTo([
            'access dashboard',
            'edit profile',
            'change password',
        ]);

        // Super admin will get all permissions via a gate in AuthServiceProvider
    }
}
