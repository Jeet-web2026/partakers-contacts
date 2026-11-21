<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * OPTIONAL. Real user/contact/bank/login data copied from the dump. Run only on local/dev, never commit to a public repo.
 */
class UserDataSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            AdmnGrupMastSeeder::class,
            AdmnUserMastSeeder::class,
            AdmnUserBankMastSeeder::class,
            AdmnBankAttachmentsSeeder::class,
            AdmnCntaAddrMastSeeder::class,
            AdmnCntaEmaiMastSeeder::class,
            AdmnCntaLinkMastSeeder::class,
            AdmnCntaNoteComntSeeder::class,
            AdmnCntaNoteMastSeeder::class,
            AdmnCntaPhonMastSeeder::class,
            AdmnCntaRefaMastSeeder::class,
            AdmnTagMastSeeder::class,
            AdmnCntaTagMastSeeder::class,
            AdmnUserLogiMastSeeder::class,
            AdmnUserOrgaRelaSeeder::class,
            TblTranSbcrPlanVldtSeeder::class,
            TblTranUserAsscSeeder::class,
            TblUserOrgaRelaSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
