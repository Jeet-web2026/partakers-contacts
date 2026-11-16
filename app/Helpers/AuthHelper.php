<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthHelper
{
    public static function getAuthenticatedUser()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN || !session('api_authenticated')) {
            return null;
        }

        return DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->select('User_UIN', 'User_Name', 'Prmy_Emai', 'User_Logo')
            ->first();
    }

    public static function isAuthenticated(): bool
    {
        return session('api_authenticated', false) && session('authenticated_user_uin');
    }

    public static function getUserOrganizations()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN) {
            return collect();
        }

        return DB::table('tbl_tran_user_assc')
            ->join('admn_orga_mast', 'tbl_tran_user_assc.Orga_UIN', '=', 'admn_orga_mast.Orga_UIN')
            ->where('tbl_tran_user_assc.User_UIN', $userUIN)  // Use User_UIN from user_assc
            ->where('tbl_tran_user_assc.Stau_UIN', 100201)  // Only active associations
            ->select('admn_orga_mast.Orga_UIN', 'admn_orga_mast.Orga_Name as name')
            ->distinct()
            ->get()
            ->unique('name')
            ->values();
    }

    public static function getSelectedOrganization()
    {
        $orgaUIN = session('selected_Orga_UIN');

        if (!$orgaUIN) {
            return null;
        }

        return DB::table('admn_orga_mast')
            ->where('Orga_UIN', $orgaUIN)
            ->select('Orga_UIN', 'Orga_Name as name')
            ->first();
    }

    public static function getSelectedOrganizationName(): string
    {
        $org = self::getSelectedOrganization();
        return $org ? $org->name : 'No Organization Selected';
    }

    public static function generateUniqueUIN(string $table, string $pkColumn): int
    {
        for ($i = 0; $i < 50; $i++) {
            $microtime = microtime(true);
            $timestampPart = ((int) ($microtime * 1000000)) % 1000000;
            $randomPart = mt_rand(100, 999);
            $newUIN = 1500000000 + ($timestampPart * 1000) + $randomPart;

            if (!DB::table($table)->where($pkColumn, $newUIN)->exists()) {
                return $newUIN;
            }
        }

        return (DB::table($table)->max($pkColumn) ?? 1500000000) + 1;
    }

    public static function ensureUserOrganizationAssociations(?int $userUIN): void
    {
        if (!$userUIN) {
            return;
        }

        try {
            $orgaUINs = [];

            // 1. Check local DB (mystuff) - admn_orga_mast
            try {
                $localAdmins = DB::table('admn_orga_mast')
                    ->where(function ($q) use ($userUIN) {
                        $q->where('Admt_Pers_1st', $userUIN)
                          ->orWhere('Admt_Pers_2nd', $userUIN)
                          ->orWhere('Admt_Pers_3rd', $userUIN)
                          ->orWhere('Admt_Pers_4th', $userUIN)
                          ->orWhere('Cnta_Pers', $userUIN)
                          ->orWhere('Sytm_Admt_Pers', $userUIN)
                          ->orWhere('CrBy', $userUIN)
                          ->orWhere('Orga_UIN', $userUIN);
                    })
                    ->pluck('Orga_UIN')
                    ->toArray();
                $orgaUINs = array_merge($orgaUINs, $localAdmins);
            } catch (\Exception $e) {
            }

            // 2. Check local DB (mystuff) - admn_user_orga_rela
            try {
                $localRelas = DB::table('admn_user_orga_rela')
                    ->where('User_UIN', $userUIN)
                    ->pluck('Orga_UIN')
                    ->toArray();
                $orgaUINs = array_merge($orgaUINs, $localRelas);
            } catch (\Exception $e) {
            }

            // 3. Check central DB (partkers_subs) - tbl_user_orga_rela
            try {
                if (Schema::connection('partkers_subs')->hasTable('tbl_user_orga_rela')) {
                    $subsRelas = DB::connection('partkers_subs')
                        ->table('tbl_user_orga_rela')
                        ->where('User_UIN', $userUIN)
                        ->pluck('Orga_UIN')
                        ->toArray();
                    $orgaUINs = array_merge($orgaUINs, $subsRelas);
                }
            } catch (\Exception $e) {
            }

            // 4. Check central DB (partkers_subs) - tbl_mast_orga or admn_orga_mast
            try {
                $subsOrgTable = Schema::connection('partkers_subs')->hasTable('tbl_mast_orga')
                    ? 'tbl_mast_orga'
                    : (Schema::connection('partkers_subs')->hasTable('admn_orga_mast') ? 'admn_orga_mast' : null);

                if ($subsOrgTable) {
                    $subsAdmins = DB::connection('partkers_subs')
                        ->table($subsOrgTable)
                        ->where(function ($q) use ($userUIN, $subsOrgTable) {
                            $q->where('Orga_UIN', $userUIN);
                            if (Schema::connection('partkers_subs')->hasColumn($subsOrgTable, 'Admt_Pers_1st')) {
                                $q->orWhere('Admt_Pers_1st', $userUIN)
                                  ->orWhere('Admt_Pers_2nd', $userUIN)
                                  ->orWhere('Admt_Pers_3rd', $userUIN)
                                  ->orWhere('Admt_Pers_4th', $userUIN);
                            }
                            if (Schema::connection('partkers_subs')->hasColumn($subsOrgTable, 'Cnta_Pers')) {
                                $q->orWhere('Cnta_Pers', $userUIN);
                            }
                            if (Schema::connection('partkers_subs')->hasColumn($subsOrgTable, 'CrBy')) {
                                $q->orWhere('CrBy', $userUIN);
                            }
                        })
                        ->pluck('Orga_UIN')
                        ->toArray();
                    $orgaUINs = array_merge($orgaUINs, $subsAdmins);
                }
            } catch (\Exception $e) {
            }

            $allOrgaUINs = array_values(array_unique(array_filter($orgaUINs)));

            if (empty($allOrgaUINs)) {
                try {
                    $localOrgs = DB::table('admn_orga_mast')->pluck('Orga_UIN')->toArray();
                    if (!empty($localOrgs)) {
                        $allOrgaUINs = array_values(array_unique(array_filter($localOrgs)));
                    } else {
                        $defaultOrgaUIN = self::generateUniqueUIN('admn_orga_mast', 'Orga_UIN');
                        DB::table('admn_orga_mast')->insert([
                            'Orga_UIN'           => $defaultOrgaUIN,
                            'Orga_Type_UIN'      => 1500000001,
                            'Orga_Name'          => 'Default Organization',
                            'orga_Logo'          => '',
                            'Dt_of_Incp'         => now()->timestamp,
                            'Fnal_Year_Strt_Dt'  => now()->timestamp,
                            'Actn_Strt_Dt'       => now()->timestamp,
                            'Web_Addr'           => '',
                            'Cnta_Pers'          => $userUIN,
                            'Admt_Pers_1st'      => $userUIN,
                            'Admt_Pers_2nd'      => 0,
                            'Admt_Pers_3rd'      => 0,
                            'Admt_Pers_4th'      => 0,
                            'Assc_Part_Code'     => 0,
                            'Sytm_Admt_Pers'     => $userUIN,
                            'Eft_Dt'             => now()->timestamp,
                            'Renw_Dt'            => now()->timestamp,
                            'Expy_Dt'            => now()->addYears(1)->timestamp,
                            'Orga_Del_Dt'        => 0,
                            'Stau_UIN'           => 100201,
                            'Stat_UIN'           => 100201,
                            'pin_UIN'            => '',
                            'Prmy_Emai'          => '',
                            'Prmy_Emai_Vefi'     => 0,
                            'mpos_key'           => 0,
                            'CrOn'               => now()->format('Y-m-d H:i:s'),
                            'CrBy'               => $userUIN,
                            'MoOn'               => now()->format('Y-m-d H:i:s'),
                            'MoBy'               => $userUIN,
                        ]);
                        $allOrgaUINs = [$defaultOrgaUIN];
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Fallback org association failed: ' . $e->getMessage());
                    return;
                }
            }

            // 5. Ensure user exists in local admn_user_logi_mast
            $userExists = DB::table('admn_user_logi_mast')
                ->where('User_UIN', $userUIN)
                ->exists();

            if (!$userExists) {
                $userRecord = null;
                try {
                    if (Schema::connection('partkers_subs')->hasTable('tbl_user_logn')) {
                        $userRecord = DB::connection('partkers_subs')
                            ->table('tbl_user_logn')
                            ->where('User_UIN', $userUIN)
                            ->first();
                    } elseif (Schema::connection('partkers_subs')->hasTable('admn_user_logi_mast')) {
                        $userRecord = DB::connection('partkers_subs')
                            ->table('admn_user_logi_mast')
                            ->where('User_UIN', $userUIN)
                            ->first();
                    }
                } catch (\Exception $e) {
                }

                $userSessionData = session('user_data', []);
                DB::table('admn_user_logi_mast')->insert([
                    'User_UIN'  => $userUIN,
                    'User_Name' => $userRecord->User_Name ?? ($userRecord->User_Logi_ID ?? ($userSessionData['User_Name'] ?? 'User ' . $userUIN)),
                    'Prmy_Emai' => $userRecord->Prmy_Emai ?? ($userSessionData['Prmy_Emai'] ?? null),
                    'Prmy_MoNo' => $userRecord->Prmy_MoNo ?? ($userSessionData['Prmy_MoNo'] ?? null),
                    'User_Logo' => $userRecord->User_Logo ?? ($userSessionData['User_Logo'] ?? null),
                    'Stau_UIN'  => 100201,
                ]);
            }

            foreach ($allOrgaUINs as $orgaUIN) {
                // 6. Ensure organization exists in local admn_orga_mast
                $orgExists = DB::table('admn_orga_mast')
                    ->where('Orga_UIN', $orgaUIN)
                    ->exists();

                if (!$orgExists) {
                    $orgRecord = null;
                    try {
                        $subsOrgTable = Schema::connection('partkers_subs')->hasTable('tbl_mast_orga')
                            ? 'tbl_mast_orga'
                            : (Schema::connection('partkers_subs')->hasTable('admn_orga_mast') ? 'admn_orga_mast' : null);

                        if ($subsOrgTable) {
                            $orgRecord = DB::connection('partkers_subs')
                                ->table($subsOrgTable)
                                ->where('Orga_UIN', $orgaUIN)
                                ->first();
                        }
                    } catch (\Exception $e) {
                    }

                    DB::table('admn_orga_mast')->insert([
                        'Orga_UIN'           => $orgaUIN,
                        'Orga_Type_UIN'      => $orgRecord->Orga_Type_UIN ?? 1500000001,
                        'Orga_Name'          => $orgRecord->Orga_Name ?? ($orgRecord->FaNm ?? ('Organization ' . $orgaUIN)),
                        'orga_Logo'          => $orgRecord->orga_Logo ?? '',
                        'Dt_of_Incp'         => $orgRecord->Dt_of_Incp ?? now()->timestamp,
                        'Fnal_Year_Strt_Dt'  => now()->timestamp,
                        'Actn_Strt_Dt'       => now()->timestamp,
                        'Web_Addr'           => $orgRecord->Web_Addr ?? '',
                        'Cnta_Pers'          => $userUIN,
                        'Admt_Pers_1st'      => $userUIN,
                        'Admt_Pers_2nd'      => 0,
                        'Admt_Pers_3rd'      => 0,
                        'Admt_Pers_4th'      => 0,
                        'Assc_Part_Code'     => 0,
                        'Sytm_Admt_Pers'     => $userUIN,
                        'Eft_Dt'             => now()->timestamp,
                        'Renw_Dt'            => now()->timestamp,
                        'Expy_Dt'            => now()->addYears(1)->timestamp,
                        'Orga_Del_Dt'        => 0,
                        'Stau_UIN'           => 100201,
                        'Stat_UIN'           => 100201,
                        'pin_UIN'            => '',
                        'Prmy_Emai'          => $orgRecord->Prmy_Emai ?? '',
                        'Prmy_Emai_Vefi'     => 0,
                        'mpos_key'           => 0,
                        'CrOn'               => now()->format('Y-m-d H:i:s'),
                        'CrBy'               => $userUIN,
                        'MoOn'               => now()->format('Y-m-d H:i:s'),
                        'MoBy'               => $userUIN,
                    ]);
                }

                // 7. Ensure admn_user_assc_lbel_mast Admin label exists
                $asscLbel = DB::table('admn_user_assc_lbel_mast')
                    ->where('Orga_UIN', $orgaUIN)
                    ->where('User_Assc_Lbel_Name', 'Admin')
                    ->first();

                if ($asscLbel) {
                    $orgaUserAsscUIN = $asscLbel->Orga_User_Assc_UIN;
                } else {
                    $orgaUserAsscUIN = self::generateUniqueUIN('admn_user_assc_lbel_mast', 'Orga_User_Assc_UIN');
                    DB::table('admn_user_assc_lbel_mast')->insert([
                        'Orga_User_Assc_UIN'  => $orgaUserAsscUIN,
                        'Orga_UIN'            => $orgaUIN,
                        'User_Assc_Lbel_Name' => 'Admin',
                        'User_Assc_Lbel_Desp' => 'Organization Administrator',
                        'CrBy'                => (string) $userUIN,
                        'CrOn'                => now()->format('Y-m-d H:i:s'),
                    ]);
                }

                // 8. Ensure admn_user_orga_rela relation exists
                $relaExists = DB::table('admn_user_orga_rela')
                    ->where('User_UIN', $userUIN)
                    ->where('Orga_UIN', $orgaUIN)
                    ->exists();

                if (!$relaExists) {
                    DB::table('admn_user_orga_rela')->insert([
                        'User_Assc_UIN'      => self::generateUniqueUIN('admn_user_orga_rela', 'User_Assc_UIN'),
                        'Orga_User_Assc_UIN' => $orgaUserAsscUIN,
                        'User_UIN'           => $userUIN,
                        'Orga_UIN'           => $orgaUIN,
                        'Stau_UIN'           => 100201,
                        'CrOn'               => now()->format('Y-m-d H:i:s'),
                        'CrBy'               => (string) $userUIN,
                    ]);
                } else {
                    DB::table('admn_user_orga_rela')
                        ->where('User_UIN', $userUIN)
                        ->where('Orga_UIN', $orgaUIN)
                        ->update(['Stau_UIN' => 100201]);
                }

                // 9. Ensure tbl_tran_user_assc association exists with admin permissions
                $asscExists = DB::table('tbl_tran_user_assc')
                    ->where('Orga_UIN', $orgaUIN)
                    ->where('User_UIN', $userUIN)
                    ->where('vert_uin', 112)  // Only check for the same vertical to avoid false positives
                    ->exists();

                if (!$asscExists) {
                    DB::table('tbl_tran_user_assc')->insert([
                        'User_Assc_UIN'      => self::generateUniqueUIN('tbl_tran_user_assc', 'User_Assc_UIN'),
                        'Orga_User_Assc_UIN' => self::generateUniqueUIN('tbl_tran_user_assc', 'Orga_User_Assc_UIN'),
                        'Orga_UIN'           => $orgaUIN,
                        'User_UIN'           => $userUIN,
                        'can_add'            => 1,
                        'can_edit'           => 1,
                        'can_atch'           => 1,
                        'can_del'            => 1,
                        'can_link'           => 1,
                        'can_rept'           => 1,
                        'can_impt'           => 1,
                        'can_expt'           => 1,
                        'mast_grp'           => 1,
                        'mast_tag'           => 1,
                        'mast_notes'         => 1,
                        'is_admin'           => 1,
                        'vert_uin'           => 112,
                        'Expy'               => 99999,
                        'Stau_UIN'           => 100201,
                        'CrOn'               => now()->timestamp,
                        'CrBy'               => $userUIN,
                        'MoOn'               => now()->timestamp,
                        'MoBy'               => $userUIN,
                    ]);
                } else {
                    DB::table('tbl_tran_user_assc')
                        ->where('Orga_UIN', $orgaUIN)
                        ->where('User_UIN', $userUIN)
                        ->where('vert_uin', 112)
                        ->update([
                            'Stau_UIN' => 100201,
                            'is_admin' => 1,
                        ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ensureUserOrganizationAssociations failed: ' . $e->getMessage());
        }
    }
}
