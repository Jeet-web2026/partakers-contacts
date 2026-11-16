<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Traits\ManagesSettings;
use App\Models\Admn_Cnta_Link_Mast;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Invite extends Component
{
    use ManagesSettings;

    public bool $enabled = true;

    // Month/Year filter: format YYYY-MM
    public string $selectedMonth;

    public function mount()
    {
        $this->enabled = $this->getFeatureStatus('Invite');
        $this->selectedMonth = now()->format('Y-m');
    }

    public function toggle()
    {
        $this->enabled = !$this->enabled;
        $this->updateFeatureStatus('Invite', $this->enabled);
    }

    #[Computed]
    public function availableMonths()
    {
        $orgUIN = session('selected_Orga_UIN');

        return Admn_Cnta_Link_Mast::where('Admn_Orga_Mast_UIN', $orgUIN)
            ->selectRaw("DATE_FORMAT(CrOn, '%Y-%m') as month_key, DATE_FORMAT(CrOn, '%b-%y') as month_label")
            ->groupBy('month_key', 'month_label')
            ->orderByDesc('month_key')
            ->pluck('month_label', 'month_key')
            ->toArray();
    }

    #[Computed]
    public function inviteSummary()
    {
        $orgUIN = session('selected_Orga_UIN');

        [$year, $month] = explode('-', $this->selectedMonth);

        return DB::table('admn_cnta_link_mast as l')
            ->leftJoin('admn_user_logi_mast as u', 'l.CrBy', '=', 'u.User_UIN')
            ->where('l.Admn_Orga_Mast_UIN', $orgUIN)
            ->whereYear('l.CrOn', $year)
            ->whereMonth('l.CrOn', $month)
            ->selectRaw('
                DATE(l.CrOn) as creation_date,
                l.CrBy,
                COALESCE(u.User_Name, "Unknown User") as creator_name,
                COUNT(*) as total_invites,
                SUM(CASE WHEN l.Is_Used = 1 THEN 1 ELSE 0 END) as used_invites
            ')
            ->groupBy('creation_date', 'l.CrBy', 'u.User_Name')
            ->orderByDesc('creation_date')
            ->get();
    }

    #[Computed]
    public function selectedMonthLabel()
    {
        return isset($this->availableMonths[$this->selectedMonth])
            ? $this->availableMonths[$this->selectedMonth]
            : Carbon::createFromFormat('Y-m', $this->selectedMonth)->format('M-y');
    }

    public function render()
    {
        return view('livewire.settings.invite');
    }
}
