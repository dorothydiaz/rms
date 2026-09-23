<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultUsers = [
            [
                'username'  => 'peter',
                'full_name' => 'System Administrator',
                'email'     => 'admin@rms.local',
                'password'  => Hash::make('Admin@12345'),
                'role'      => 'Admin',
                'status'    => 'Active',
            ],
            [
                'username'  => 'dorothy',
                'full_name' => 'Dorothy Diaz',
                'email'     => 'dorothy@rms.local',
                'password'  => Hash::make('Manager@12345'),
                'role'      => 'Manager',
                'status'    => 'Active',
            ],
            [
                'username'  => 'cashier',
                'full_name' => 'John Cashier',
                'email'     => 'cashier@rms.local',
                'password'  => Hash::make('Cashier@12345'),
                'role'      => 'Cashier',
                'status'    => 'Active',
            ],
            [
                'username'  => 'staff',
                'full_name' => 'Sarah Staff',
                'email'     => 'staff@rms.local',
                'password'  => Hash::make('Staff@12345'),
                'role'      => 'Staff',
                'status'    => 'Active',
            ],
            [
                'username'  => 'kitchen',
                'full_name' => 'Chef Marco',
                'email'     => 'kitchen@rms.local',
                'password'  => Hash::make('Kitchen@12345'),
                'role'      => 'Kitchen',
                'status'    => 'Active',
            ],
        ];

        foreach ($defaultUsers as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );
        }
    }
}
