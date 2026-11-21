<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdmnUserLogiMastSeeder extends Seeder
{
    public function run(): void
    {
        $columns = ['User_UIN', 'User_Name', 'User_Logo', 'Prmy_Emai', 'Prmy_Emai_Vefi', 'Altn_Emai', 'Altn_Emai_Vefi', 'Cutr_UIN', 'ISD_UIN', 'Prmy_MoNo', 'Prmy_MoNo_Vefi', 'OTP_Prmy_MoNo', 'Altn_MoNo', 'Altn_MoNo_Vefi', 'OTP_Altn_MoNo', 'User_pasw', 'Pasw_Self_Gntd', 'Pasw_Rest_By', 'Pasw_Rest_On', 'CrBy', 'Stau_UIN'];

        $rows = [
            [0, '', null, '', null, null, null, 0, 0, 0, null, null, null, null, null, '', null, null, null, null, null],
            [1500000001, 'Partha Das', null, 'parthadas83@gmail.com', 100207, null, 100207, 101107, 91, 9810122401, 1, null, null, null, null, '$2y$10$3WPidIOn33TWpzJLa5yrl.67QeOIseeJciViBBIXjq2...', 1, 0, null, null, null],
            [1677851592, 'Arijit Chakrabarty', null, 'arijit4real@gmail.com', null, null, 100207, 101107, 91, 9999955555, 1, null, null, null, null, '$2y$10$fmjJGGgCBuZwEWhF61DPmOrmsBhxhIs1AyvHGCP8jwr...', 1, 0, null, null, null],
            [1724728683, 'Ujan Tozo', null, 'ujantozo@gmail.com', null, null, 100207, 101107, 91, 9999955555, 1, null, null, null, null, '$2y$10$QyyJdiXVH38X64d6i5Oo9ud7cFCoHTrOst4.SSotf/w...', 1, 0, null, null, null],
            [1738254418, 'ARUNODOY DAS', null, 'arunodaydas@gmail.com', null, null, 100207, 101107, 91, 9999955555, 1, null, null, null, null, '$2y$10$aeyoIqHpoTtVMuIxpgaFrewwoO8L3ltXMDAhFc6dBtf...', 1, 0, null, null, null],
            [1768039919, 'Partake Digital', null, 'partakedigital@gmail.com', null, null, 100207, 101107, 91, 9999955555, 1, null, null, null, null, '', 1, 0, null, null, null],
            [6857873677, 'Swastika Das', null, 'swastikadas83@gmail.com', null, null, 100207, 101107, 91, 9999955555, 1, null, null, null, null, '$2y$10$LpcfKchSahekAzAbvw948.OBMDZs9W45504groHHIqg...', 1, 0, null, null, null],
            [6857873700, 'Partakers Das', null, 'partakedigitalmedia@gmail.com', null, null, 100207, 101107, 91, 9999955555, 1, null, null, null, null, '$2y$10$s8IYRASXmuOg5mBrHzitFudqUt44ueDIYHRTIcaO3UB...', 1, 0, null, null, null],
            [6857873701, 'Divyanshu Yadav', '', 'ydivyanshuya22@gmail.com', 100207, '', null, 101107, 91, 8303436435, 0, null, null, null, null, '$2y$12$uPEFD0RZgsejNIfTa6NSa.cqM2iQQEpDKRi0KFd58UYXyGHIRlxie', null, null, null, null, 100201],
            [6857873702, 'dd y', '', 'divyanshuyadav297@gmail.com', 100207, '', null, 101107, 91, 2132115735, 0, null, null, null, null, '$2y$12$Aa6yUZtvtAC4jjM4hbisnuQGRyaxh59wdGpILpDx7hNUpoTV5Q0QO', null, null, null, null, 100201],
            [6857873703, 'tp y', '', 'ketad97069@hebase.com', 100207, '', null, 101107, 91, 3543653653, 0, null, null, null, null, '$2y$12$lCFr/UiLeslqyoca9ZGYc.cWuWVo1PAjOTvTEG7urHVMWKOwv6Nt.', null, null, null, null, 100201],
        ];

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('admn_user_logi_mast')->insert(
                array_map(fn (array $row) => array_combine($columns, $row), $chunk)
            );
        }
    }
}
