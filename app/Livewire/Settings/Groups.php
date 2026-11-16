<?php

namespace App\Livewire\Settings;

use Livewire\Component;

use App\Traits\ManagesSettings;
use App\Traits\LogsActivity;
use App\Livewire\Traits\HasMaxConstants;
use App\Models\Admn_Grup_Mast;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

class Groups extends Component
{
    use ManagesSettings, HasMaxConstants, LogsActivity;
    
    public bool $enabled = true;

    public function mount()
    {
        $this->enabled = $this->getFeatureStatus('Groups');
    }

    public function toggle()
    {
        $this->enabled = !$this->enabled;
        $this->updateFeatureStatus('Groups', $this->enabled);
    }

    #[Computed]
    public function allGroups()
    {
        $orgUin = session('selected_Orga_UIN');

        return DB::table('admn_grup_mast as g')
            ->leftJoin('admn_grup_mast as p', 'g.Parent_Grup_UIN', '=', 'p.Admn_Grup_Mast_UIN')
            ->leftJoin('admn_user_logi_mast as u', 'g.CrBy', '=', 'u.User_UIN')
            ->where('g.Admn_Orga_Mast_UIN', $orgUin)
            ->select(
                'g.Admn_Grup_Mast_UIN',
                'g.Name',
                'g.Is_Actv',
                'g.CrOn',
                DB::raw('p.Name as parent_name'),
                DB::raw('COALESCE(u.User_Name, "Unknown") as created_by'),
                DB::raw('(SELECT COUNT(*) FROM admn_user_mast um WHERE um.Admn_Grup_Mast_UIN = g.Admn_Grup_Mast_UIN) as users_count')
            )
            ->orderBy('g.Name')
            ->get();
    }

    public function toggleGroupStatus($groupUIN)
    {
        $group = Admn_Grup_Mast::find($groupUIN);
        if ($group) {
            $group->Is_Actv = ($group->Is_Actv == self::STATUS_ACTIVE) ? 100202 : self::STATUS_ACTIVE;
            $group->MoBy    = session('authenticated_user_uin');
            $group->MoOn    = now();
            $group->save();
            
            $statusStr = ($group->Is_Actv == self::STATUS_ACTIVE) ? 'Activated' : 'Deactivated';
            $this->logActivity('Master Data Edit', "Group [{$group->Name}] $statusStr");
        }
    }

    public function render()
    {
        return view('livewire.settings.groups');
    }
}
