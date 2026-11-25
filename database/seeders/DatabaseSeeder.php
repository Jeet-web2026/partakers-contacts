<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data: countries, states, districts, pincodes, banks, orgs, settings, subscription masters
        $this->call(MasterDataSeeder::class);
        $this->call(UserDataSeeder::class);
    }
}
