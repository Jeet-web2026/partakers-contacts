<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnAddrTypeMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Addr_Type_Mast_UIN', 'Name', 'Addr_Type', 'Cr_By', 'Cr_On', 'Mo_By', 'Mo_On', 'Del_By', 'Del_On', 'Vf_By', 'Vf_On'];

        $rows = [
            [1, 'Resident', 'i', '0', null, '0', null, '0', null, null, null],
            [2, 'Rental', 'i', '0', null, '0', null, '0', null, null, null],
            [3, 'Registered Office', 'b', '0', null, '0', null, '0', null, null, null],
            [4, 'Regional Office', 'b', '0', null, '0', null, '0', null, null, null],
            [5, 'Branch Office', 'b', '0', null, '0', null, '0', null, null, null],
            [6, 'Shop', 'b', '0', null, '0', null, '0', null, null, null],
            [7, 'Wherehouse', 'b', '0', null, '0', null, '0', null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_addr_type_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
