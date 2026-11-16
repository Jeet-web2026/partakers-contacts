<?php

namespace App\Livewire\Organizations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Select Organization')]
class Select extends Component
{
    public ?int $selectedOrgaUIN = null;

    public function mount()
    {
        if (!session('api_authenticated')) {
            return redirect()->route('api.auth.form');
        }

        $userUIN = session('authenticated_user_uin');
        if (!$userUIN) {
            $userData = session('user_data', []);
            $userUIN = $userData['User_UIN'] ?? null;
            if ($userUIN) {
                session(['authenticated_user_uin' => $userUIN]);
            }
        }

        if ($userUIN) {
            \App\Helpers\AuthHelper::ensureUserOrganizationAssociations($userUIN);
        }

        $this->selectedOrgaUIN = session('selected_Orga_UIN');

        $orgs = $this->userOrganizations();
        if ($orgs->count() === 1 && !$this->selectedOrgaUIN) {
            $singleOrgUIN = (int) $orgs->first()->Orga_UIN;
            $this->selectOrganization($singleOrgUIN);
        }
    }

    #[Computed]
    public function userOrganizations()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN) {
            $userData = session('user_data', []);
            $userUIN = $userData['User_UIN'] ?? null;
            if ($userUIN) {
                session(['authenticated_user_uin' => $userUIN]);
            } else {
                Log::warning('No authenticated user UIN found in session');
                return collect();
            }
        }

        // Auto-onboard/ensure admin associations exist across all required tables
        \App\Helpers\AuthHelper::ensureUserOrganizationAssociations($userUIN);

        // checking user exists in admn_user_logi_mast
        $userExists = DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->exists();

        if (!$userExists) {
            DB::table('admn_user_logi_mast')->insert([
                'User_UIN'  => $userUIN,
                'User_Name' => 'User ' . $userUIN,
                'Stau_UIN'  => 100201,
            ]);
        }

        // Get user associations from tbl_tran_user_assc table
        $userAssociations = DB::table('tbl_tran_user_assc')
            ->where('User_UIN', $userUIN)
            ->where('Stau_UIN', 100201)  // Only active associations
            ->select('User_Assc_UIN', 'Orga_UIN', 'User_UIN')
            ->get();

        if ($userAssociations->isEmpty()) {
            $organizations = DB::table('admn_orga_mast')
                ->select('Orga_UIN', 'Orga_Name')
                ->get();
        } else {
            $orgaUIDs = $userAssociations->pluck('Orga_UIN')->unique()->values();

            $organizations = DB::table('admn_orga_mast')
                ->whereIn('Orga_UIN', $orgaUIDs)
                ->select('Orga_UIN', 'Orga_Name')
                ->get();
        }

        if ($organizations->isEmpty()) {
            $organizations = DB::table('admn_orga_mast')
                ->select('Orga_UIN', 'Orga_Name')
                ->get();
        }

        if ($organizations->isEmpty()) {
            $defaultOrgaUIN = \App\Helpers\AuthHelper::generateUniqueUIN('admn_orga_mast', 'Orga_UIN');
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

            \App\Helpers\AuthHelper::ensureUserOrganizationAssociations($userUIN);

            $organizations = DB::table('admn_orga_mast')
                ->where('Orga_UIN', $defaultOrgaUIN)
                ->select('Orga_UIN', 'Orga_Name')
                ->get();
        }

        return $organizations->unique('Orga_Name')->sortBy('Orga_Name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    #[Computed]
    public function currentUser()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN) {
            return null;
        }

        return DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->select('User_UIN', 'User_Name', 'Prmy_Emai')
            ->first();
    }

    public function updatedSelectedOrgaUIN($value)
    {
        if ($value) {
            $this->selectOrganization($value);
        }
    }

    public function selectOrganization(int $orgaUIN): void
    {
        $userUIN = session('authenticated_user_uin');
        if (!$userUIN) {
            $userData = session('user_data', []);
            $userUIN = $userData['User_UIN'] ?? null;
            if ($userUIN) {
                session(['authenticated_user_uin' => $userUIN]);
            }
        }

        if (!$userUIN) {
            session()->flash('error', 'User authentication session missing');
            return;
        }

        // Verify user exists in admn_user_logi_mast
        $userExists = DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->exists();

        if (!$userExists) {
            DB::table('admn_user_logi_mast')->insert([
                'User_UIN'  => $userUIN,
                'User_Name' => 'User ' . $userUIN,
                'Stau_UIN'  => 100201,
            ]);
        }

        // Verify user has access to this organization through tbl_tran_user_assc
        $hasAccess = DB::table('tbl_tran_user_assc')
            ->where('User_UIN', $userUIN)
            ->where('Orga_UIN', $orgaUIN)
            ->where('Stau_UIN', 100201)
            ->exists();

        if (!$hasAccess) {
            \App\Helpers\AuthHelper::ensureUserOrganizationAssociations($userUIN);

            $hasAccessNow = DB::table('tbl_tran_user_assc')
                ->where('User_UIN', $userUIN)
                ->where('Orga_UIN', $orgaUIN)
                ->exists();

            if (!$hasAccessNow) {
                DB::table('tbl_tran_user_assc')->insert([
                    'User_Assc_UIN'      => \App\Helpers\AuthHelper::generateUniqueUIN('tbl_tran_user_assc', 'User_Assc_UIN'),
                    'Orga_User_Assc_UIN' => \App\Helpers\AuthHelper::generateUniqueUIN('tbl_tran_user_assc', 'Orga_User_Assc_UIN'),
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
                    ->where('User_UIN', $userUIN)
                    ->where('Orga_UIN', $orgaUIN)
                    ->update(['Stau_UIN' => 100201]);
            }
        }

        $organization = DB::table('admn_orga_mast')
            ->where('Orga_UIN', $orgaUIN)
            ->first();

        if (!$organization) {
            DB::table('admn_orga_mast')->insert([
                'Orga_UIN'           => $orgaUIN,
                'Orga_Type_UIN'      => 1500000001,
                'Orga_Name'          => 'Organization ' . $orgaUIN,
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
        }

        session(['selected_Orga_UIN' => $orgaUIN]);
        $this->selectedOrgaUIN = $orgaUIN;

        $this->redirect(route('contacts.index'));
    }
}
