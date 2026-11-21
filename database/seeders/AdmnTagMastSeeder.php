<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnTagMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Tag_Mast_UIN', 'Admn_Orga_Mast_UIN', 'Name', 'stau', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On'];

        $rows = [
            [15038681798, 1500000001, 'Tag 1', '100201', '1500000001', '2026-09-09 15:27:13', null, '2026-09-09 15:27:13', null, null, null, null],
            [15622520182, 1500000001, 'Master Tags', '100201', '1500000001', '2026-09-05 15:58:56', '1500000001', '2026-09-05 16:04:40', null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_tag_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
