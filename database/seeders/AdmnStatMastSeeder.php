<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnStatMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Stat_Mast_UIN', 'Admn_Cutr_Mast_UIN', 'Name', 'Code', 'Stau_UIN', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On'];

        $rows = [
            [2, 2, 'Blank', null, 100201, null, null, null, null, null, null, null, null],
            [4, 4, 'Not Mentioned', null, 100201, null, null, null, null, null, null, null, null],
            [102001, 101107, 'Andaman & Nicobar Is', null, 100202, null, null, null, null, null, null, null, null],
            [102002, 101107, 'Andhra Pradesh', null, 100201, null, null, null, null, null, null, null, null],
            [102003, 101107, 'Arunachal Pradesh', null, 100201, null, null, null, null, null, null, null, null],
            [102004, 101107, 'Assam', null, 100201, null, null, null, null, null, null, null, null],
            [102005, 101107, 'Bihar', null, 100201, null, null, null, null, null, null, null, null],
            [102006, 101107, 'Chandigarh', null, 100201, null, null, null, null, null, null, null, null],
            [102007, 101107, 'Chattisgarh', null, 100201, null, null, null, null, null, null, null, null],
            [102008, 101107, 'Dadra & Nagar Haveli', null, 100201, null, null, null, null, null, null, null, null],
            [102009, 101107, 'Daman & Diu', null, 100201, null, null, null, null, null, null, null, null],
            [102010, 101107, 'Delhi', null, 100201, null, null, null, null, null, null, null, null],
            [102011, 101107, 'Goa', null, 100201, null, null, null, null, null, null, null, null],
            [102012, 101107, 'Gujarat', null, 100201, null, null, null, null, null, null, null, null],
            [102013, 101107, 'Haryana', null, 100201, null, null, null, null, null, null, null, null],
            [102014, 101107, 'Himachal Pradesh', null, 100201, null, null, null, null, null, null, null, null],
            [102015, 101107, 'Jammu & Kashmir', null, 100201, null, null, null, null, null, null, null, null],
            [102016, 101107, 'Jharkhand', null, 100201, null, null, null, null, null, null, null, null],
            [102017, 101107, 'Karnataka', null, 100201, null, null, null, null, null, null, null, null],
            [102018, 101107, 'Kerala', null, 100201, null, null, null, null, null, null, null, null],
            [102019, 101107, 'Lakshadweep', null, 100201, null, null, null, null, null, null, null, null],
            [102020, 101107, 'Madhya Pradesh', null, 100201, null, null, null, null, null, null, null, null],
            [102021, 101107, 'Maharashtra', null, 100201, null, null, null, null, null, null, null, null],
            [102022, 101107, 'Manipur', null, 100201, null, null, null, null, null, null, null, null],
            [102023, 101107, 'Meghalaya', null, 100201, null, null, null, null, null, null, null, null],
            [102024, 101107, 'Mizoram', null, 100201, null, null, null, null, null, null, null, null],
            [102025, 101107, 'Nagaland', null, 100201, null, null, null, null, null, null, null, null],
            [102026, 101107, 'Odisha', null, 100201, null, null, null, null, null, null, null, null],
            [102027, 101107, 'Pondicherry', null, 100201, null, null, null, null, null, null, null, null],
            [102028, 101107, 'Punjab', null, 100201, null, null, null, null, null, null, null, null],
            [102029, 101107, 'Rajasthan', null, 100201, null, null, null, null, null, null, null, null],
            [102030, 101107, 'Sikkim', null, 100201, null, null, null, null, null, null, null, null],
            [102031, 101107, 'Tamil Nadu', null, 100201, null, null, null, null, null, null, null, null],
            [102032, 101107, 'Telangana', null, 100201, null, null, null, null, null, null, null, null],
            [102033, 101107, 'Tripura', null, 100201, null, null, null, null, null, null, null, null],
            [102034, 101107, 'Uttar Pradesh', null, 100201, null, null, null, null, null, null, null, null],
            [102035, 101107, 'Uttarakhand', null, 100201, null, null, null, null, null, null, null, null],
            [102036, 101107, 'West Bengal', null, 100201, null, null, null, null, null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_stat_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
