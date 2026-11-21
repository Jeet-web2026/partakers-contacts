<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnCntaLinkMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Cnta_Link_Mast_UIN', 'Tokn', 'Admn_Orga_Mast_UIN', 'Cnta_Tag', 'Is_Used', 'Expy_Dt', 'Is_Actv', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On'];

        $rows = [
            [196032549, 'Luov5JjPjhb3oUbaIH3hM1B8yw9cvS3CQfniywqu', 1500000001, 'ByLink', 0, '2026-09-06 16:09:09', 100201, '1500000001', '2026-09-05 16:09:09', null, null, null, null, null, null],
            [196375640, 'oJum1uOSP9A3e1DetggtJO0Q73iQEtmZQ74R48rK', 1500000001, 'ByLink', 0, '2026-09-10 15:27:20', 100201, '1500000001', '2026-09-09 15:27:20', null, null, null, null, null, null],
            [198066405, 'w2sU3Y9T7phBvGK1Q0moUeRaSRXJTpgy5Qv6WAKF', 1787331949, 'ByLink', 0, '2026-09-30 05:06:45', 100201, '6857873701', '2026-09-29 05:06:45', null, null, null, null, null, null],
            [198104089, 'pNd3ubbYkrWpSGY0yhrIKrDNwLaLuaS7Wqu9VEpj', 1500000001, 'ByLink', 1, '2026-09-30 15:34:49', 0, '1500000001', '2026-09-29 15:34:49', '1', '2026-09-29 15:35:18', null, null, null, null],
            [201222472, 'l3LxB5QC8o0hmM0V2KEtBvQ9O38ed6HGxl52xIRM', 1500000001, 'ByLink', 1, '2026-11-05 17:47:52', 0, '1500000001', '2026-11-04 17:47:52', '1', '2026-11-04 17:51:51', null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_cnta_link_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
