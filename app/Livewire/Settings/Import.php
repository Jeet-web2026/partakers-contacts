<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Traits\ManagesSettings;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Import extends Component
{
    use ManagesSettings;

    public bool $enabled = true;
    public string $selectedMonth;

    public function mount()
    {
        $this->enabled = $this->getFeatureStatus('Import');
        $this->selectedMonth = now()->format('Y-m');
    }

    public function toggle()
    {
        $this->enabled = !$this->enabled;
        $this->updateFeatureStatus('Import', $this->enabled);
    }

    #[Computed]
    public function availableMonths()
    {
        $orgUIN = session('selected_Orga_UIN');

        return DB::table('tbl_mast_sbcr_log_book')
            ->where('Indv_Orga_Uin', $orgUIN)
            ->where('Sbcr_Rvnu_Catg_Name_UIN', 126) // Import Data
            ->selectRaw("DATE_FORMAT(CrOn, '%Y-%m') as month_key, DATE_FORMAT(CrOn, '%b-%y') as month_label")
            ->groupBy('month_key', 'month_label')
            ->orderByDesc('month_key')
            ->pluck('month_label', 'month_key')
            ->toArray();
    }

    #[Computed]
    public function importSummary()
    {
        $orgUIN = session('selected_Orga_UIN');
        [$year, $month] = explode('-', $this->selectedMonth);

        return DB::table('tbl_mast_sbcr_log_book as l')
            ->leftJoin('admn_user_logi_mast as u', 'l.User_UIN', '=', 'u.User_UIN')
            ->where('l.Indv_Orga_Uin', $orgUIN)
            ->where('l.Sbcr_Rvnu_Catg_Name_UIN', 126) // Import Data
            ->whereYear('l.CrOn', $year)
            ->whereMonth('l.CrOn', $month)
            ->selectRaw('DATE(l.CrOn) as import_date, l.User_UIN, COALESCE(u.User_Name, "Unknown User") as imported_by, SUM(l.Cont) as nos')
            ->groupBy('import_date', 'l.User_UIN', 'u.User_Name')
            ->orderByDesc('import_date')
            ->get();
    }

    #[Computed]
    public function selectedMonthLabel()
    {
        return $this->availableMonths[$this->selectedMonth]
            ?? Carbon::createFromFormat('Y-m', $this->selectedMonth)->format('M-y');
    }

    public function render()
    {
        return view('livewire.settings.import');
    }
}
