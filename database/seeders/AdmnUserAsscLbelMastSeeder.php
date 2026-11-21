<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnUserAsscLbelMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Orga_User_Assc_UIN', 'Orga_UIN', 'User_Assc_Lbel_Name', 'User_Assc_Lbel_Desp', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn'];

        $rows = [
            [100101, 0, 'Admin', 'Default API Administrator Role', null, '2026-07-24 00:00:00', null, '2026-07-24 00:00:00', null, null],
            [1546040583, 1500000002, 'Admin', 'Organization Administrator', '1500000001', '2026-08-25 05:45:12', null, null, null, null],
            [1547763322, 1715097168, 'Admin', 'Organization Administrator', '1500000001', '2026-08-25 05:45:12', null, null, null, null],
            [1549371768, 1787331830, 'Admin', 'Organization Administrator', '1500000001', '2026-08-25 05:45:12', null, null, null, null],
            [1550887593, 1787331948, 'Admin', 'Organization Administrator', '1500000001', '2026-08-25 05:45:12', null, null, null, null],
            [1569380538, 1788089434, 'Admin', 'Organization Administrator', '6857873702', '2026-08-30 11:32:26', null, null, null, null],
            [1575432792, 1787592629, 'Admin', 'Organization Administrator', '6857873702', '2026-08-30 11:32:26', null, null, null, null],
            [1757510371, 1787592628, 'Admin', 'Organization Administrator', '6857873701', '2026-08-24 17:33:33', null, null, null, null],
            [1784825586, 1784658318, 'Admin', 'Organization Administrator', '6857873697', '2026-07-23 16:53:05', null, null, null, null],
            [1784832047, 1779902009, 'Admin', 'Organization Administrator', '6857873696', '2026-07-23 18:40:47', null, null, null, null],
            [1804878109, 1787331949, 'Admin', 'Organization Administrator', '6857873701', '2026-08-30 07:11:05', null, null, null, null],
            [1946530666, 1500000001, 'Admin', 'Organization Administrator', '1500000001', '2026-07-26 03:54:01', null, null, null, null],
            [1952084875, 1787286058, 'Admin', 'Organization Administrator', '2', '2026-08-21 04:22:42', null, null, null, null],
            [2239391486, 1784961420, 'Admin', 'Organization Administrator', '6857873700', '2026-07-25 06:39:38', null, null, null, null],
            [2287738162, 1784959952, 'Admin', 'Organization Administrator', '6857873699', '2026-07-25 06:14:46', null, null, null, null],
            [2294570871, 1788540697, 'Admin', 'Organization Administrator', '6857873703', '2026-09-04 16:52:34', null, null, null, null],
            [2302803853, 1786196663, 'Admin', 'Organization Administrator', '6857873706', '2026-08-08 13:46:37', null, null, null, null],
            [2305319384, 1788089435, 'Admin', 'Organization Administrator', '6857873703', '2026-09-04 16:52:34', null, null, null, null],
            [2452228889, 1784913755, 'Admin', 'Organization Administrator', '6857873696', '2026-08-03 17:39:13', null, null, null, null],
            [2471034427, 1785779937, 'Admin', 'Organization Administrator', '6857873703', '2026-08-03 18:00:36', null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_user_assc_lbel_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
