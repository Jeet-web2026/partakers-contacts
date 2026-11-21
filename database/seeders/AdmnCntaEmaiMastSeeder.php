<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnCntaEmaiMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['Admn_Cnta_Emai_Mast_UIN', 'Admn_User_Mast_UIN', 'Emai_Addr', 'Emai_Type', 'Is_Prmy', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On'];

        $rows = [
            [15010489706, 15124365155, 'janeee@email.com', 'personal', 1, '1500000001', '2026-09-18 15:36:22', null, null, null, null, null, null],
            [15067553845, 15481224229, 'jane1@email.com', 'personal', 1, '1500000001', '2026-04-03 09:38:06', null, null, null, null, null, null],
            [15070918341, 15811980667, 'example@email.com', 'personal', 1, '1500000001', '2026-03-30 10:00:05', null, null, null, null, null, null],
            [15381924259, 15310367750, 'jane2@email.com', 'personal', 1, '1500000001', '2026-09-05 16:48:53', null, null, null, null, null, null],
            [15434773670, 15303745366, 'example2@email.com', 'personal', 1, '1500000001', '2026-09-05 16:55:02', null, null, null, null, null, null],
            [15594176486, 15592973535, 'example@email.com', 'personal', 1, '6857873700', '2026-08-02 18:49:20', null, '2026-08-02 18:49:20', null, null, null, null],
            [15596052554, 15595342457, 'jane@email.com', 'personal', 1, '6857873700', '2026-08-02 18:49:20', null, '2026-08-02 18:49:20', null, null, null, null],
            [15597891301, 15597168613, 'example1@email.com', 'personal', 1, '6857873700', '2026-08-02 18:49:20', null, '2026-08-02 18:49:20', null, null, null, null],
            [15599663457, 15598990442, 'jane1@email.com', 'personal', 1, '6857873700', '2026-08-02 18:49:20', null, '2026-08-02 18:49:20', null, null, null, null],
            [15775938434, 15483394442, 'example2@email1.com', 'personal', 1, '1500000001', '2026-09-05 16:52:03', null, null, null, null, null, null],
            [15814338145, 15813624540, 'jane@email.com', 'personal', 1, '6857873677', '2026-03-29 09:44:08', null, '2026-03-29 09:44:08', null, null, null, null],
            [15922836295, 15961980681, 'jane@email.com', 'personal', 1, '1500000001', '2026-07-01 10:23:38', null, null, null, null, null, null],
            [15957865673, 15120786586, 'examplwe@email.com', 'personal', 1, '1500000001', '2026-09-09 15:59:28', null, null, null, null, null, null],
            [15958774179, 15983581282, 'jane@email.com', 'personal', 1, '1500000001', '2026-04-03 08:19:13', null, null, null, null, null, null],
            [15961106607, 15960057264, 'example@email.com', 'personal', 1, '1500000001', '2026-03-29 08:36:23', null, '2026-03-29 08:36:23', null, null, null, null],
            [15982549424, 15981477942, 'example@email.com', 'personal', 1, '1500000001', '2026-03-29 08:35:37', null, '2026-03-29 08:35:37', null, null, null, null],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_cnta_emai_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
