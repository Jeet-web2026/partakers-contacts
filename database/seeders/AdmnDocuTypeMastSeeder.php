<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnDocuTypeMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Docu_Type_Mast_UIN', 'Docu_Name', 'Stau', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VrBy', 'VrOn', 'created_at', 'updated_at'];

        $rows = [
            [110, 'Rental Address Proof', 100201, null, null, null, null, null, null, null, null],
            [111, 'Photograph', 100201, null, null, null, null, null, null, null, '2025-11-15 09:36:22'],
            [112, 'Permanent Address Proof', 100201, null, null, null, null, null, null, null, null],
            [113, 'Current Address Proof', 100201, null, null, null, null, null, null, null, null],
            [114, 'Relationship Proof', 100201, null, null, null, null, null, null, null, null],
            [115, 'Address Proof of Guardian', 100201, null, null, null, null, null, null, null, null],
            [116, 'Identity Proof of Guardian', 100201, null, null, null, null, null, null, null, null],
            [117, 'Nominee Proof', 100201, null, null, null, null, null, null, null, null],
            [118, 'Expiry Proof', 100201, null, null, null, null, null, null, null, null],
            [119, 'Other', 100201, null, null, null, null, null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_docu_type_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
