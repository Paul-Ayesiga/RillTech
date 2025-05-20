<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the role and permission seeders
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // Create a super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign the super-admin role
        $superAdmin->assignRole('super-admin');

        // Create a test client user
        $client = User::factory()->create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
        ]);

        // Assign the client role
        $client->assignRole('client');
    }
}
