<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SitesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('site')->insert([
            [
                'id' => 1,
                'site_name' => 'Zimpost Site',
                'network_id' => 1,
                'site_description' => 'Zimpost Site',
                'user_id' => 1, // Assuming this is a valid user ID
                'created_at' => '2025-01-17 06:29:01',
                'updated_at' => '2025-01-17 06:29:01',
            ],
            [
                'id' => 2,
                'site_name' => 'Joina City Site',
                'network_id' => 1,
                'site_description' => 'Joina City Site',
                'user_id' => 1, // Assuming this is a valid user ID
                'created_at' => '2025-01-17 06:29:21',
                'updated_at' => '2025-01-17 06:29:21',
            ],
            [
                'id' => 3,
                'site_name' => 'Hardon and Sligh Site',
                'network_id' => 2,
                'site_description' => 'Hardon and Sligh Site',
                'user_id' => 1, // Assuming this is a valid user ID
                'created_at' => '2025-01-17 06:31:19',
                'updated_at' => '2025-01-17 06:31:19',
            ],
        ]);
    }
}
