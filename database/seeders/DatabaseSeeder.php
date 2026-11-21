<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data: countries, states, districts, pincodes, banks, orgs, settings, subscription masters
        $this->call(MasterDataSeeder::class);

        // Real users, logins, bank and contact data from the dump. Local/dev only.
        if (app()->environment('local')) {
            $this->call(UserDataSeeder::class);
        }
    }
}
