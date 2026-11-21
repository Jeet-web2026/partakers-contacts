<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Reference / master data: countries, states, districts, pincodes, banks, orgs, settings, subscription masters.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            AdmnAddrTypeMastSeeder::class,
            AdmnBankNameSeeder::class,
            AdmnOrgaMastSeeder::class,
            AdmnCutrMastSeeder::class,
            AdmnStatMastSeeder::class,
            AdmnDistMastSeeder::class,
            AdmnPincodeMastSeeder::class,
            AdmnDocuTypeMastSeeder::class,
            AdmnPrfxNameMastSeeder::class,
            AdmnUserAsscLbelMastSeeder::class,
            TblMastSbcrLogBookSeeder::class,
            TblMastSbcrRvnuCatgSeeder::class,
            TblMastSbcrUomSeeder::class,
            TblMastSettSeeder::class,
            TblMastUserAsscLbelSeeder::class,
            TblMastVertSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
