<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TblMastUserAsscLbelSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Orga_User_Assc_UIN', 'Orga_UIN', 'User_Assc_Lbel_Name', 'User_Assc_Lbel_Desp', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn'];

        $rows = [
            [100100, 1, 'Admin', 'Admin', 1, 1720849601, 0, 0, 0, 0],
            [100101, 1, 'Employee', 'Employee', 1, 1720849601, 0, 0, 0, 0],
            [100102, 1, 'Contractor', 'Contractor', 1, 1720849601, 0, 0, 0, 0],
            [100103, 1, 'Vendor', 'Vendor', 1, 1720849601, 0, 0, 0, 0],
            [100104, 1, 'Customer', 'Customer', 1, 1720849601, 0, 0, 0, 0],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('tbl_mast_user_assc_lbel')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
