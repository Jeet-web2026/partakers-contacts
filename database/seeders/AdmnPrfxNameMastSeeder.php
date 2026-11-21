<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnPrfxNameMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Prfx_Name_UIN', 'Prfx_Name', 'Prfx_Name_Desp', 'Stau_UIN', 'CrBy', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On'];

        $rows = [
            [2, 'Blank', 'test', 101, 102, 102, null, 102, null, null, null],
            [4, 'Not Mentioned', 'test', 101, 102, 102, null, 102, null, null, null],
            [100001, 'ACP', 'Police ranks (India)', 100201, 103, 103, null, 103, null, null, null],
            [100002, 'Adv.', 'Advocate or Lawyer', 100201, 103, 103, null, 103, null, null, null],
            [100003, 'Arch.', 'Architect', 100201, 103, 103, null, 103, null, null, null],
            [100004, 'CA', 'Chartered Accountant', 100201, 103, 103, null, 103, null, null, null],
            [100005, 'Capt.', 'Captain', 100201, 103, 103, null, 103, null, null, null],
            [100006, 'Cllr.', 'Councillor', 100201, 103, 103, null, 103, null, null, null],
            [100007, 'Cmdr.', 'Commander', 100201, 103, 103, null, 103, null, null, null],
            [100008, 'Col.', 'Colonel', 100201, 103, 103, null, 103, null, null, null],
            [100009, 'DCP', 'Police ranks (India)', 100201, 103, 103, null, 103, null, null, null],
            [100010, 'Dr.', 'Doctor ', 100201, 103, 103, null, 103, null, null, null],
            [100011, 'Eng.', 'Engineer', 100201, 103, 103, null, 103, null, null, null],
            [100012, 'Fr.', 'Father (Christian clergy)', 100201, 103, 103, null, 103, null, null, null],
            [100013, 'Gen.', 'General', 100201, 103, 103, null, 103, null, null, null],
            [100014, 'Gov.', 'Governor', 100201, 103, 103, null, 103, null, null, null],
            [100015, 'Guru', 'Spiritual teacher', 100201, 103, 103, null, 103, null, null, null],
            [100016, 'Imam', 'Islamic clergy', 100201, 103, 103, null, 103, null, null, null],
            [100017, 'Lt.', 'Lieutenant', 100201, 103, 103, null, 103, null, null, null],
            [100018, 'Miss', 'Unmarried woman', 100201, 103, 103, null, 103, null, null, null],
            [100019, 'MP', 'Member of Parliament', 100201, 103, 103, null, 103, null, null, null],
            [100020, 'Mr.', 'Adult male ', 100201, 103, 103, null, 103, null, null, null],
            [100021, 'Mrs.', 'Married woman', 100201, 103, 103, null, 103, null, null, null],
            [100022, 'Ms.', 'Woman (marital status unspecified)', 100201, 103, 103, null, 103, null, null, null],
            [100023, 'Mx.', 'Gender-neutral title', 100201, 103, 103, null, 103, null, null, null],
            [100024, 'PM', 'Prime Minister', 100201, 103, 103, null, 103, null, null, null],
            [100025, 'Pres.', 'President', 100201, 103, 103, null, 103, null, null, null],
            [100026, 'Prof.', 'Professor', 100201, 103, 103, null, 103, null, null, null],
            [100027, 'Rabbi', 'Jewish clergy', 100201, 103, 103, null, 103, null, null, null],
            [100028, 'Rev.', 'Reverend', 100201, 103, 103, null, 103, null, null, null],
            [100029, 'Sen.', 'Senator', 100201, 103, 103, null, 103, null, null, null],
            [100030, 'Swami', 'Hindu spiritual leader', 100201, 103, 103, null, 103, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_prfx_name_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
