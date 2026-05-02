<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NetworksTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('network')->insert([
            [
                'id' => 1,
                'name' => 'Harare Network',
                'city' => 'Masvingo',
                'province' => null, // Assuming this is a nullable field
                'description' => 'For sites in Harare',
                'user_id' => 1, // Assuming this is a valid user ID
                'created_at' => '2025-01-17 06:27:27',
                'updated_at' => '2025-01-17 06:27:27',
            ],
            [
                'id' => 2,
                'name' => 'Bulawayo Network',
                'city' => 'Bulawayo',
                'province' => null,
                'description' => 'For sites in Bulawayo',
                'user_id' => 1, // Assuming this is a valid user ID
                'created_at' => '2025-01-17 06:28:00',
                'updated_at' => '2025-01-17 06:28:00',
            ],
        ]);
    }
}
