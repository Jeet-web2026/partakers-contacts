<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnCntaTagMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Cnta_Tag_Mast_UIN', 'Admn_User_Mast_UIN', 'Admn_Tag_Mast_UIN', 'Is_Actv', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On'];

        $rows = [
            [15011948214, 15124365155, 15622520182, 1, '1500000001', '2026-09-18 15:36:22', null, null, null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_cnta_tag_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
