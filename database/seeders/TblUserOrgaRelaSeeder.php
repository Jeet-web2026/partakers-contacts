<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TblUserOrgaRelaSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Orga_User_Assc_UIN', 'Orga_UIN', 'Vert_UIN', 'User_UIN', 'Expy', 'Stau_UIN', 'CrOn', 'CrBy', 'MoOn', 'MoBy'];

        $rows = [
            [1500000001, 1500000001, 112, 1500000001, null, null, null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('tbl_user_orga_rela')->insert(
                array_map(fn(array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
