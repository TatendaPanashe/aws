<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'admin',
                'email' => 'admin@admin.com',
                'email_verified_at' => null,
                'password' => Hash::make('Test123!'),
                'role_id' => 1,
                'siteid' => 1,
                'networkid' => 1, 
                'remember_token' => null,
                'created_at' => '2025-01-15 22:43:23',
                'updated_at' => '2025-01-15 22:43:23'
            ],
            [
                'id' => 2,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified_at' => '2025-01-15 23:13:11',
                'password' => Hash::make('Test123!'),
                'role_id' => 1,
                'siteid' => 1,
                'networkid' => 1, 
                'remember_token' => 'ltODNRqbFb',
                'created_at' => '2025-01-15 23:13:11',
                'updated_at' => '2025-01-15 23:13:11'
            ],

            [
                'id' => 3,
                'name' => 'Clerk User',
                'email' => 'clerk@clerk.com',
                'email_verified_at' => '2025-01-15 23:13:11',
                'password' => Hash::make('Test123!'),
                'role_id' => 2,
                'siteid' => 1,
                'networkid' => 1, 
                'remember_token' => '',
                'created_at' => '2025-01-15 23:13:11',
                'updated_at' => '2025-01-15 23:13:11'
            ],

            [
                'id' => 4,
                'name' => 'supervisor User',
                'email' => 'supervisor@supervisor.com',
                'email_verified_at' => '2025-01-15 23:13:11',
                'password' => Hash::make('Test123!'),
                'role_id' => 3,
                'siteid' => 1,
                'networkid' => 1, 
                'remember_token' => '',
                'created_at' => '2025-01-15 23:13:11',
                'updated_at' => '2025-01-15 23:13:11'
            ],

            [
                'id' => 5,
                'name' => 'manager User',
                'email' => 'manager@manager.com',
                'email_verified_at' => '2025-01-15 23:13:11',
                'password' => Hash::make('Test123!'),
                'role_id' => 4,
                'siteid' => 1,
                'networkid' => 1, 
                'remember_token' => '',
                'created_at' => '2025-01-15 23:13:11',
                'updated_at' => '2025-01-15 23:13:11'
            ],
        ]);
    }
}
