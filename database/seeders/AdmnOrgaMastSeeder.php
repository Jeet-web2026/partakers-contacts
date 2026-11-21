<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnOrgaMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Orga_UIN', 'Orga_Type_UIN', 'Orga_Name', 'orga_Logo', 'Dt_of_Incp', 'Fnal_Year_Strt_Dt', 'Actn_Strt_Dt', 'Web_Addr', 'Cnta_Pers', 'Admt_Pers_1st', 'Admt_Pers_2nd', 'Admt_Pers_3rd', 'Admt_Pers_4th', 'Assc_Part_Code', 'Sytm_Admt_Pers', 'Eft_Dt', 'Renw_Dt', 'Expy_Dt', 'Orga_Del_Dt', 'Stau_UIN', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Stat_UIN', 'pin_UIN', 'Prmy_Emai', 'Prmy_Emai_Vefi', 'mpos_key'];

        $rows = [
            [1500000001, 1500000001, 'D S Computer Center', '', 0, 0, 0, '', 0, 1500000001, 1677851592, 0, 0, 0, 0, 0, 0, 0, 0, 0, null, null, null, null, null, null, 0, '', '', 0, 0],
            [1500000002, 1500000001, 'Marcus Org', '', 0, 0, 0, '', 0, 1500000001, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null, null, null, null, null, null, 0, '', '', 0, 0],
            [1715097168, 1500000033, 'Ekatrap', '', 0, 0, 0, '', 0, 1500000001, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null, null, null, null, null, null, 0, '', '', 0, 0],
            [1787331830, 1500000009, 'Rajdeep Trading Company', '', 0, 0, 0, '', 0, 1500000001, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null, null, null, null, null, null, 0, '', '', 0, 0],
            [1787331948, 1500000012, 'Partakers Organization LLP', '', 0, 0, 0, '', 0, 1500000001, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null, null, null, null, null, null, 0, '', '', 0, 0],
            [1787331949, 1500000001, 'wbc', '', 0, 0, 0, '', 0, 6857873701, 0, 0, 0, 0, 0, 1787592627, 0, 0, 0, 100201, '6857873701', '1970-01-01 00:33:46', null, null, null, null, 102034, '221108', 'ydivyanshuya22@gmail.com', 100207, 1],
            [1787592628, 1500000001, 'wbc', '', 1787592813, 1787592813, 1787592813, '', 6857873701, 6857873701, 0, 0, 0, 0, 6857873701, 1787592813, 1787592813, 1819128813, 0, 100201, '6857873701', '1970-01-01 00:33:46', '6857873701', '1970-01-01 00:33:46', null, null, 100201, '', 'ydivyanshuya22@gmail.com', 0, 0],
            [1787592629, 1500000001, 'potion', '', 0, 0, 0, '', 0, 6857873702, 0, 0, 0, 0, 0, 1788089433, 0, 0, 0, 100201, '6857873702', '1970-01-01 00:33:46', null, null, null, null, 102034, '221108', 'divyanshuyadav297@gmail.com', 100207, 2],
            [1788089434, 1500000001, 'potion', '', 1788089546, 1788089546, 1788089546, '', 6857873702, 6857873702, 0, 0, 0, 0, 6857873702, 1788089546, 1788089546, 1819625546, 0, 100201, '6857873702', '1970-01-01 00:33:46', '6857873702', '1970-01-01 00:33:46', null, null, 100201, '', 'divyanshuyadav297@gmail.com', 0, 0],
            [1788089435, 1500000001, 'techy', '', 0, 0, 0, '', 0, 6857873703, 0, 0, 0, 0, 0, 1788540697, 0, 0, 0, 100201, '6857873703', '1970-01-01 00:33:46', null, null, null, null, 102034, '221108', 'ketad97069@hebase.com', 100207, 3],
            [1788540697, 1500000001, 'techy', '', 1788540754, 1788540754, 1788540754, '', 6857873703, 6857873703, 0, 0, 0, 0, 6857873703, 1788540754, 1788540754, 1820076754, 0, 100201, '6857873703', '1970-01-01 00:33:46', '6857873703', '1970-01-01 00:33:46', null, null, 100201, '', 'ketad97069@hebase.com', 0, 0],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_orga_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
