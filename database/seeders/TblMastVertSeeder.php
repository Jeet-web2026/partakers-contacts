<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TblMastVertSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['id', 'vertical', 'redirect_url', 'Stau_UIN'];

        $rows = [
            [101, 'accounts', 'https://bookkeeping.partakedigital.in/loginViaToken?token=', 100201],
            [102, 'individual', 'https://individual.partakedigital.in/dashboard?token=', 100201],
            [103, 'cashcounter', 'https://cc.partakedigital.in/loginViaToken?token=', 100201],
            [104, 'sales', 'https://sales.partakedigital.in/loginViaToken?token=', 100201],
            [105, 'circular & notice', 'https://nc.partakers.in/notices', 100201],
            [106, 'visitors', 'https://visitors.partakers.in', 100201],
            [107, 'customers', 'https://customers.partakers.in', 100201],
            [108, 'vendors', 'https://vendors.partakers.in', 100201],
            [109, 'promotions', 'https://promotions.partakers.in', 100201],
            [110, 'job', 'https://job.partakers.in/', 100201],
            [111, 'hr', 'https://hr.partakers.in', 100201],
            [112, 'contacts', 'https://contacts.partakers.in/', 100201],
            [113, 'agreements', 'https://agreements.partakers.in', 100201],
            [114, 'organizations', 'https://organizations.partakers.in', 100201],
            [115, 'mr', 'https://mr.partakers.in', 100201],
            [116, 'reception', 'https://reception.partakers.in', 100201],
            [117, 'album', 'https://album.partakers.in', 100201],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('tbl_mast_vert')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
