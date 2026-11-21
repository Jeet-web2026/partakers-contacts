<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnBankAttachmentsSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Bank_Attachment_UIN', 'Admn_User_Bank_Mast_UIN', 'Atch_Path', 'Atch_Path_2', 'Atch_Type_1', 'Atch_Type_2', 'Orgn_Name', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfOn', 'VfBy'];

        $rows = [
            [15439997889, 15439251374, 'Attachment/Bank/1500000001_15303745366_20260905_165502.jpeg', null, 'Bank Statement', null, 'WhatsApp Image 2026-08-08 at 16.02.29.jpeg', '1500000001', '2026-09-05 16:55:02', '1500000001', '2026-09-05 16:55:02', null, null],
            [15785933289, 15784742583, 'Attachment/Bank/1500000001_15483394442_20260905_165203.jpeg', null, 'Cancelled Cheque', null, 'WhatsApp Image 2026-08-08 at 16.02.29.jpeg', '1500000001', '2026-09-05 16:52:03', '1500000001', '2026-09-05 16:52:03', null, null],
            [15961855493, 15960490292, 'Attachment/Bank/1500000001_15120786586_20260909_155928.webp', null, 'Bank Statement', null, 'copy 2.webp', '1500000001', '2026-09-09 15:59:28', '1500000001', '2026-09-09 15:59:28', null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_bank_attachments')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
