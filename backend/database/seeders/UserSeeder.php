<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed initial users matching the frontend mock data.
     * All demo passwords: Demo@2026!
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Daniel Okonkwo',
                'email' => 'daniel@casi.org',
                'role' => 'super_admin',
                'department' => 'Administration',
                'phone' => '+234 801 234 5678',
                'status' => 'active',
                'force_password_change' => false,
            ],
            [
                'name' => 'Grace Adeyemi',
                'email' => 'grace@casi.org',
                'role' => 'admin',
                'department' => 'Operations',
                'phone' => '+234 802 345 6789',
                'status' => 'active',
                'force_password_change' => false,
            ],
            [
                'name' => 'Samuel Eze',
                'email' => 'samuel@casi.org',
                'role' => 'manager',
                'department' => 'Programs',
                'phone' => '+234 803 456 7890',
                'status' => 'active',
                'force_password_change' => false,
            ],
            [
                'name' => 'Amina Bello',
                'email' => 'amina@casi.org',
                'role' => 'staff',
                'department' => 'HR',
                'phone' => '+234 804 567 8901',
                'status' => 'active',
                'force_password_change' => false,
            ],
            [
                'name' => 'Chidi Nnamdi',
                'email' => 'chidi@casi.org',
                'role' => 'staff',
                'department' => 'Finance',
                'phone' => '+234 805 678 9012',
                'status' => 'active',
                'force_password_change' => false,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => Hash::make('Demo@2026!'),
                    'email_verified_at' => now(),
                ])
            );
        }

        $this->command->info('✓ Seeded ' . count($users) . ' users (password: Demo@2026!)');
    }
}
