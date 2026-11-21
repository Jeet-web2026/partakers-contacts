<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnCntaNoteMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Cnta_Note_Mast_UIN', 'Admn_User_Mast_UIN', 'Admn_Orga_Mast_UIN', 'Vertical_ID', 'Note_Detl', 'CrOn', 'CrBy', 'MoBy', 'MoOn', 'DelBy', 'DelOn', 'IsPinned', 'PinnedOn', 'PinnedBy'];

        $rows = [
            [15094168250, 15653640536, 1589781954, 5, 'Customer agreed to submit PAN later.', '2026-03-20 11:58:49', '1766061572', null, null, null, null, 0, null, null],
            [15102605623, 15653640536, 1589781954, 5, 'PAN card uploaded - awaiting approval.', '2026-03-20 11:58:56', '1766061572', null, null, null, null, 0, null, null],
            [15510371605, 15991393648, 1500000001, 5, 'Customer informed about acceptable documents for permanent address.', '2026-04-19 04:21:10', '1766061572', null, null, null, null, 0, null, null],
            [15693737745, 15653640536, 1589781954, 5, 'Discussed PAN requirement with customer.', '2026-03-20 11:58:53', '1766061572', null, null, null, null, 0, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_cnta_note_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
