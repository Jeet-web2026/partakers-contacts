<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TblMastSbcrUomSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Sbcr_Uom_Uin', 'Uom_Name', 'Uom_Shrt_Name', 'Stau_Uin', 'CrBy', 'CrOn'];

        $rows = [
            [100001, 'Numbers', 'Nos', 100201, 101, '2026-03-13 16:40:39'],
            [100002, 'Mega Bytes', 'MB', 100201, 101, '2026-03-13 16:40:39'],
            [100003, 'Kilo Bytes', 'KB', 100201, 101, '2026-03-13 16:40:39'],
            [100004, 'Single', 'Single', 100201, 101, '2026-03-13 16:40:39'],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('tbl_mast_sbcr_uom')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
