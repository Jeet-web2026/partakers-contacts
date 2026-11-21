<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnCntaRefaMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Cnta_Refa_Mast_UIN', 'Admn_User_Mast_UIN', 'Refa_Name', 'Refa_Phon', 'Refa_Emai', 'Refa_Rsip', 'Is_Prmy', 'CrOn', 'MoOn', 'CrBy'];

        $rows = [
            [15617343817, 15967724519, 'nkk', '', '', '', 0, '2026-03-07 04:34:39', null, '1766061572'],
            [15926623200, 15961980681, 'A1', '', '', 'df', 0, '2026-07-01 10:23:38', null, '1500000001'],
            [15926994666, 15961980681, 'a2', '', '', 'dde', 0, '2026-07-01 10:23:38', null, '1500000001'],
            [15927336813, 15961980681, 'a3', '', '', 'dd', 0, '2026-07-01 10:23:38', null, '1500000001'],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_cnta_refa_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
