<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnUserBankMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_User_Bank_Mast_UIN', 'Admn_User_Mast_UIN', 'Admn_Orga_Mast_UIN', 'Bank_Name_UIN', 'Bank_Brnc_Name', 'Acnt_Type', 'Acnt_Numb', 'IFSC_Code', 'Swift_Code', 'Prmy', 'Stau', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VrBy', 'VrOn', 'created_at', 'updated_at'];

        $rows = [
            [15439251374, 15303745366, 1500000001, 20000233, 'dfgdfgd', 'Savings Account', '21010121', '', '', 0, 100201, '1500000001', null, null, null, null, null, '2026-09-05 16:55:02', '2026-09-05 16:55:02'],
            [15445280173, 15991393648, 1500000001, 20000298, '', 'Savings Account', '36816135', '', '', 0, 100201, '1500000001', null, null, null, null, null, '2026-09-05 15:54:10', '2026-09-05 15:54:10'],
            [15784742583, 15483394442, 1500000001, 20000298, 'sdf', 'Savings Account', '563563', '', '', 0, 100201, '1500000001', null, null, null, null, null, '2026-09-05 16:52:03', '2026-09-05 16:52:03'],
            [15960490292, 15120786586, 1500000001, 20000260, 'afsdfdsf', 'Current Account', '3943643446', '', '', 0, 100201, '1500000001', null, null, null, null, null, '2026-09-09 15:59:28', '2026-09-09 15:59:28'],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_user_bank_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
