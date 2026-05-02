<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id' => 1,
                'role_name' => 'admin',
                'role_description' => 'admin',
                'created_at' => '2025-01-17 09:03:13',
                'updated_at' => '2025-01-17 09:03:13',
            ],
            [
                'id' => 2,
                'role_name' => 'clerk',
                'role_description' => 'clerk',
                'created_at' => '2025-01-17 09:03:23',
                'updated_at' => '2025-01-17 09:03:23',
            ],
            [
                'id' => 3,
                'role_name' => 'supervisor',
                'role_description' => 'supervisor',
                'created_at' => '2025-01-17 09:03:41',
                'updated_at' => '2025-01-17 09:03:41',
            ],
            [
                'id' => 4,
                'role_name' => 'manager',
                'role_description' => 'manager',
                'created_at' => '2025-01-17 09:03:49',
                'updated_at' => '2025-01-17 09:03:49',
            ],
            [
                'id' => 5,
                'role_name' => 'super_user',
                'role_description' => 'super_user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'role_name' => 'zinara_supervisor',
                'role_description' => 'ZINARA Supervisor for face value management',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'role_name' => 'zinara_clerk',
                'role_description' => 'ZINARA Clerk for face value declarations',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}