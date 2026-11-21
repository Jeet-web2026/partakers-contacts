<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnGrupMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Grup_Mast_UIN', 'Admn_Orga_Mast_UIN', 'Parent_Grup_UIN', 'Name', 'Is_Actv', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'created_at', 'updated_at'];

        $rows = [
            [111, 1500000001, null, 'Master Groups', 100201, 1500000001, null, 1500000001, null, '2026-09-05 16:06:22', '2026-09-05 16:06:22'],
            [112, 1500000001, 111, 'Test Group', 100201, 1500000001, null, 1500000001, null, '2026-09-07 17:27:08', '2026-09-07 17:27:08'],
            [113, 1500000001, 112, 'Test 11 group', 100201, 1500000001, null, 1500000001, null, '2026-09-09 15:26:33', '2026-09-09 15:49:26'],
            [114, 1500000001, 113, 'Test 2', 100201, 1500000001, null, 1500000001, null, '2026-09-09 15:49:11', '2026-09-09 15:49:11'],
            [115, 1500000001, 114, 'Test 21', 100201, 1500000001, null, 1500000001, null, '2026-09-13 15:28:15', '2026-09-13 15:28:15'],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_grup_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
