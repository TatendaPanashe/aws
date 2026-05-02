<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Network;
use Carbon\Carbon;

class NetworkSeeder extends Seeder
{
    public function run()
    {
        // Check if SBU3 already exists
        $sbu3 = Network::where('name', 'SBU3')->first();
        
        if (!$sbu3) {
            Network::create([
                'name' => 'SBU3',
                'description' => 'SBU3 - Courier Network for ZINARA operations',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $this->command->info('SBU3 network created successfully.');
        } else {
            $this->command->info('SBU3 network already exists.');
        }
        
        // Optionally create other networks if they don't exist
        if (!Network::where('name', 'SBU1')->first()) {
            Network::create([
                'name' => 'SBU1',
                'description' => 'SBU1 Network',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        
        if (!Network::where('name', 'SBU2')->first()) {
            Network::create([
                'name' => 'SBU2',
                'description' => 'SBU2 Network',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}