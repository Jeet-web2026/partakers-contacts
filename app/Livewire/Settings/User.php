<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\User as AppUser;
use App\Traits\ManagesSettings;
use App\Traits\ValidatesSubscriptionLimits;

class User extends Component
{
    use ManagesSettings, \App\Traits\LogsActivity, ValidatesSubscriptionLimits;

    public bool $enabled = true;
    
    // Search Modal State
    public $searchQuery = '';
    public $searchedUser = null;
    public $searchError = null;
    public $isSearchModalOpen = false;

    // Permission Modal State
    public $isPermissionModalOpen = false;
    public $selectedUserUin = null;
    public $selectedUserName = null;
    public $can_add = false;
    public $can_edit = false;
    public $can_atch = false;
    public $can_delete = false;
    public $can_link = false;
    public $can_rept = false;
    public $can_impt = false;
    public $can_expt = false;
    public $mast_grp = false;
    public $mast_tag = false;
    public $mast_notes = false;
    public $valid_upto = null;
    public $user_type = null;

    public function mount()
    {
        $this->enabled = $this->getFeatureStatus('Add');
    }

    public function toggle()
    {
        $this->enabled = !$this->enabled;
        $this->updateFeatureStatus('Add', $this->enabled);
    }

    public function openSearchModal()
    {
        $this->resetSearch();
        $this->isSearchModalOpen = true;
    }

    public function closeSearchModal()
    {
        $this->isSearchModalOpen = false;
    }

    public function resetSearch()
    {
        $this->searchQuery = '';
        $this->searchedUser = null;
        $this->searchError = null;
        // reset validation errors if any
        $this->resetValidation();
    }

    public function searchUser()
    {
        $this->validate([
            'searchQuery' => 'required|string',
        ]);

        $this->searchError = null;
        $this->searchedUser = null;

        $user = AppUser::where('Prmy_Emai', $this->searchQuery)
            ->orWhere('Prmy_MoNo', $this->searchQuery)
            ->first();

        if (!$user) {
            $this->searchError = 'User not found.';
            return;
        }

        // Check if already onboarded for this organization
        $orgaUin = session('selected_Orga_UIN');
        $existingAssc = DB::table('tbl_tran_user_assc')
            ->where('Orga_UIN', $orgaUin)
            ->where('User_UIN', $user->User_UIN)
            ->first();

        if ($existingAssc) {
            $this->searchError = 'This user is already onboarded.';
            return;
        }

        $this->searchedUser = $user;
    }

    public function proceedToPermissions()
    {
        if (!$this->searchedUser) return;

        $this->selectedUserUin  = $this->searchedUser->User_UIN;
        $this->selectedUserName = $this->searchedUser->User_Name;
        $this->can_add    = false;
        $this->can_edit   = false;
        $this->can_atch   = false;
        $this->can_delete = false;
        $this->can_link   = false;
        $this->can_rept   = false;
        $this->can_impt   = false;
        $this->can_expt   = false;
        $this->mast_grp   = false;
        $this->mast_tag   = false;
        $this->mast_notes = false;
        $this->valid_upto = null;
        $this->user_type  = null;

        $this->closeSearchModal();
        $this->isPermissionModalOpen = true;
    }

    public function openPermissionModal($userUin)
    {
        $orgaUin = session('selected_Orga_UIN');

        $assc = DB::table('tbl_tran_user_assc')
            ->join('admn_user_logi_mast', 'tbl_tran_user_assc.User_UIN', '=', 'admn_user_logi_mast.User_UIN')
            ->where('tbl_tran_user_assc.Orga_UIN', $orgaUin)
            ->where('tbl_tran_user_assc.User_UIN', $userUin)
            ->select('tbl_tran_user_assc.*', 'admn_user_logi_mast.User_Name')
            ->first();

        if ($assc) {
            $this->selectedUserUin  = $assc->User_UIN;
            $this->selectedUserName = $assc->User_Name;
            $this->can_add    = (bool)$assc->can_add;
            $this->can_edit   = (bool)$assc->can_edit;
            $this->can_atch   = (bool)$assc->can_atch;
            $this->can_delete = (bool)$assc->can_del;
            $this->can_link   = (bool)$assc->can_link;
            $this->can_rept   = (bool)$assc->can_rept;
            $this->can_impt   = (bool)$assc->can_impt;
            $this->can_expt   = (bool)$assc->can_expt;
            $this->mast_grp   = (bool)$assc->mast_grp;
            $this->mast_tag   = (bool)$assc->mast_tag;
            $this->mast_notes = (bool)$assc->mast_notes;
            $this->valid_upto = $assc->valid_upto ? \Carbon\Carbon::parse($assc->valid_upto)->format('Y-m-d') : null;
            $this->user_type  = $assc->user_type ?? null;
            $this->isPermissionModalOpen = true;
        }
    }

    public function closePermissionModal()
    {
        $this->isPermissionModalOpen = false;
        $this->selectedUserUin  = null;
        $this->selectedUserName = null;
        $this->user_type        = null;
    }

    /**
     * Computed: list of user type labels from master table.
     */
    public function getUserTypeLabelsProperty()
    {
        $orgaUin = session('selected_Orga_UIN');
        return DB::table('tbl_mast_user_assc_lbel')
            ->whereIn('Orga_UIN', [1, $orgaUin]) // Include master (1) and current org
            ->where('User_Assc_Lbel_Name', '!=', 'Admin') // Exclude 'Admin' from levels
            ->select('Orga_User_Assc_UIN', 'User_Assc_Lbel_Name')
            ->orderBy('User_Assc_Lbel_Name')
            ->get();
    }

    /**
     * Returns the label name for currently selected user_type ID.
     */
    public function getSelectedLabelNameProperty(): string
    {
        if (!$this->user_type) return '';
        $label = $this->userTypeLabels->firstWhere('Orga_User_Assc_UIN', $this->user_type);
        return $label ? strtolower(trim($label->User_Assc_Lbel_Name)) : '';
    }

    public function getPlanEndDateProperty()
    {
        $orgaUin = session('selected_Orga_UIN');
        $plan = $this->getActivePlanValidation($orgaUin);
        return $plan && !empty($plan->Sbcr_Last_Dt) ? \Carbon\Carbon::parse($plan->Sbcr_Last_Dt)->format('Y-m-d') : null;
    }

    public function savePermissions()
    {
        $planEndDate = $this->planEndDate;
        $validUptoRule = ($this->selectedLabelName === 'employee') ? 'nullable|date|after_or_equal:today' : 'required|date|after_or_equal:today';
        
        if ($planEndDate) {
            $validUptoRule .= '|before_or_equal:' . $planEndDate;
        }

        $this->validate([
            'user_type'  => 'required',
            'valid_upto' => $validUptoRule,
        ], [
            'user_type.required'  => 'Please select a user type.',
            'valid_upto.required' => 'Validity End Date is required for this user type.',
            'valid_upto.after_or_equal' => "The 'Validity End Date' date must be today or a future date.",
            'valid_upto.before_or_equal' => "The 'Validity End Date' cannot be beyond the organization's plan end date ({$planEndDate}).",
        ]);

        if (!$this->selectedUserUin) return;

        $orgaUin = session('selected_Orga_UIN');
        
        $existingAssc = DB::table('tbl_tran_user_assc')
            ->where('Orga_UIN', $orgaUin)
            ->where('User_UIN', $this->selectedUserUin)
            ->first();

        $data = [
            'can_add'    => $this->can_add    ? 1 : 0,
            'can_edit'   => $this->can_edit   ? 1 : 0,
            'can_atch'   => $this->can_atch   ? 1 : 0,
            'can_del'    => $this->can_delete  ? 1 : 0,
            'can_link'   => $this->can_link   ? 1 : 0,
            'can_rept'   => $this->can_rept   ? 1 : 0,
            'can_impt'   => $this->can_impt   ? 1 : 0,
            'can_expt'   => $this->can_expt   ? 1 : 0,
            'mast_grp'   => $this->mast_grp   ? 1 : 0,
            'mast_tag'   => $this->mast_tag   ? 1 : 0,
            'mast_notes' => $this->mast_notes ? 1 : 0,
            'valid_upto' => ($this->user_type && $this->valid_upto) ? $this->valid_upto : null,
            'user_type'  => $this->user_type,
        ];

        if ($existingAssc) {
            $data['MoOn'] = now()->timestamp;
            $data['MoBy'] = auth()->id() ?? 1;
            DB::table('tbl_tran_user_assc')
                ->where('User_Assc_UIN', $existingAssc->User_Assc_UIN)
                ->update($data);
        } else {
            // Live check — same reasoning as Contacts/Create.php: the cached
            // session quota snapshot only refreshes on org switch, so a plan
            // that expired or was refunded mid-session needs a fresh DB read.
            if ($this->isPlanExpired()) {
                $this->addError('general', 'No active subscription plan found or plan expired. Please upgrade or renew your plan.');
                return;
            }

            if (!$this->canAddUser()) {
                $this->addError('general', 'Subscription user limit exceeded. Please upgrade your plan.');
                return;
            }

            $data['User_Assc_UIN'] = $this->generateUniqueUIN('tbl_tran_user_assc', 'User_Assc_UIN');
            $data['Orga_User_Assc_UIN'] = $this->generateUniqueUIN('tbl_tran_user_assc', 'Orga_User_Assc_UIN');
            $data['Orga_UIN'] = $orgaUin;
            $data['User_UIN'] = $this->selectedUserUin;
            $data['vert_uin'] = 112;
            $data['Expy'] = 99999;
            $data['Stau_UIN'] = 100201;
            $data['CrOn'] = now()->timestamp;
            $data['CrBy'] = auth()->id() ?? 1;
            $data['MoOn'] = now()->timestamp;
            $data['MoBy'] = auth()->id() ?? 1;
            
            DB::table('tbl_tran_user_assc')->insert($data);
            $this->incrementUtilization('Ulzn_User', 1);
        }

        $this->closePermissionModal();
        session()->flash('success', 'User permissions saved successfully.');

        $actionDesc = ($existingAssc ? "Updated permissions for user: " : "Onboarded new user: ") . ($this->selectedUserName ?? $this->selectedUserUin);
        $actionKey  = $existingAssc ? 'User Permission' : 'User Onboard';
        $this->logActivity($actionKey, $actionDesc);
    }

    public function getOnboardedUsersProperty()
    {
        $orgaUin = session('selected_Orga_UIN');
        $userUin = session('authenticated_user_uin');

        if ($userUin) {
            \App\Helpers\AuthHelper::ensureUserOrganizationAssociations($userUin);
        }

        // Filter by vert_uin=112 (Contacts vertical) to avoid showing users from other verticals.
        // Group by User_UIN so each person appears only once even if they have multiple
        // association rows (e.g. one added via admin panel + one from ensureUserOrg helper).
        return DB::table('tbl_tran_user_assc')
            ->join('admn_user_logi_mast', 'tbl_tran_user_assc.User_UIN', '=', 'admn_user_logi_mast.User_UIN')
            ->leftJoin('tbl_mast_user_assc_lbel', 'tbl_tran_user_assc.user_type', '=', 'tbl_mast_user_assc_lbel.Orga_User_Assc_UIN')
            ->where('tbl_tran_user_assc.Orga_UIN', $orgaUin)
            ->where('tbl_tran_user_assc.Stau_UIN', 100201)
            ->where('tbl_tran_user_assc.vert_uin', 112)
            ->select(
                DB::raw('MAX(tbl_tran_user_assc.User_Assc_UIN) as User_Assc_UIN'),
                'tbl_tran_user_assc.User_UIN',
                'tbl_tran_user_assc.Orga_UIN',
                DB::raw('MAX(tbl_tran_user_assc.can_add) as can_add'),
                DB::raw('MAX(tbl_tran_user_assc.can_edit) as can_edit'),
                DB::raw('MAX(tbl_tran_user_assc.can_atch) as can_atch'),
                DB::raw('MAX(tbl_tran_user_assc.can_del) as can_del'),
                DB::raw('MAX(tbl_tran_user_assc.can_link) as can_link'),
                DB::raw('MAX(tbl_tran_user_assc.can_rept) as can_rept'),
                DB::raw('MAX(tbl_tran_user_assc.can_impt) as can_impt'),
                DB::raw('MAX(tbl_tran_user_assc.can_expt) as can_expt'),
                DB::raw('MAX(tbl_tran_user_assc.mast_grp) as mast_grp'),
                DB::raw('MAX(tbl_tran_user_assc.mast_tag) as mast_tag'),
                DB::raw('MAX(tbl_tran_user_assc.mast_notes) as mast_notes'),
                DB::raw('MAX(tbl_tran_user_assc.is_admin) as is_admin'),
                DB::raw('MAX(tbl_tran_user_assc.Expy) as Expy'),
                'tbl_tran_user_assc.user_type',
                'admn_user_logi_mast.User_Name',
                'admn_user_logi_mast.Prmy_Emai',
                'admn_user_logi_mast.Prmy_MoNo',
                'admn_user_logi_mast.User_Logo',
                DB::raw('MAX(tbl_mast_user_assc_lbel.User_Assc_Lbel_Name) as User_Assc_Lbel_Name'),
                DB::raw('MAX(tbl_tran_user_assc.valid_upto) as valid_upto')
            )
            ->groupBy(
                'tbl_tran_user_assc.User_UIN',
                'tbl_tran_user_assc.Orga_UIN',
                'tbl_tran_user_assc.user_type',
                'admn_user_logi_mast.User_Name',
                'admn_user_logi_mast.Prmy_Emai',
                'admn_user_logi_mast.Prmy_MoNo',
                'admn_user_logi_mast.User_Logo'
            )
            ->get();
    }

    public function render()
    {
        return view('livewire.settings.user', [
            'onboardedUsers'  => $this->onboardedUsers,
            'userTypeLabels'  => $this->userTypeLabels,
            'selectedLabelName' => $this->selectedLabelName,
        ]);
    }
}
