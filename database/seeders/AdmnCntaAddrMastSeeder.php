<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnCntaAddrMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Cnta_Addr_Mast_UIN', 'Admn_User_Mast_UIN', 'Admn_Addr_Type_Mast_UIN', 'Addr', 'Loca', 'Lndm', 'Is_Prmy', 'Admn_Cutr_Mast_UIN', 'Admn_Stat_Mast_UIN', 'Admn_Dist_Mast_UIN', 'Admn_PinCode_Mast_UIN', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On'];

        $rows = [
            [15141807919, 15285503951, 2, '', '', '', 0, 101107, 102034, 300658, 1018264, '1766061572', '2026-03-07 04:36:51', null, null, null, null, null, null],
            [15147472197, 15285503951, 1, '', 'gerg', 'reg', 1, 101107, 102003, 300043, 1001468, '1766061572', '2026-03-07 04:36:51', null, null, null, null, null, null],
            [15599623539, 15967724519, 2, 'few', 'wefw', 'fwef', 1, 101107, 102034, 300659, 1018291, '1766061572', '2026-03-07 04:34:39', null, null, null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_cnta_addr_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
