<?php

namespace App\Livewire\Settings;

use Livewire\Component;

use App\Traits\ManagesSettings;
use App\Traits\LogsActivity;
use App\Livewire\Traits\HasMaxConstants;
use App\Models\Admn_Tag_Mast;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Livewire\Traits\GeneratesUINs;

class Tag extends Component
{
    use ManagesSettings, HasMaxConstants, LogsActivity, GeneratesUINs;

    public bool $enabled = true;
    public string $selectedMonth;

    public function mount()
    {
        $this->enabled = $this->getFeatureStatus('Tag');
        $this->selectedMonth = now()->format('Y-m');
    }

    public function toggle()
    {
        $this->enabled = !$this->enabled;
        $this->updateFeatureStatus('Tag', $this->enabled);
    }

    #[Computed]
    public function availableMonths()
    {
        $orgUin = session('selected_Orga_UIN');

        $months = DB::table('admn_tag_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUin)
            ->whereNotNull('CrOn')
            ->selectRaw("DATE_FORMAT(CrOn, '%Y-%m') as month_key, DATE_FORMAT(CrOn, '%b-%y') as month_label")
            ->groupBy('month_key', 'month_label')
            ->orderByDesc('month_key')
            ->pluck('month_label', 'month_key')
            ->toArray();

        $currentKey = now()->format('Y-m');
        if (!isset($months[$currentKey])) {
            $months = array_merge([$currentKey => now()->format('M-y')], $months);
        }

        return $months;
    }

    #[Computed]
    public function allTags()
    {
        $orgUin = session('selected_Orga_UIN');
        [$year, $month] = explode('-', $this->selectedMonth);

        return DB::table('admn_tag_mast as t')
            ->leftJoin('admn_user_logi_mast as u', 't.CrBy', '=', 'u.User_UIN')
            ->where('t.Admn_Orga_Mast_UIN', $orgUin)
            ->whereYear('t.CrOn', $year)
            ->whereMonth('t.CrOn', $month)
            ->select(
                't.Admn_Tag_Mast_UIN',
                't.Name',
                't.stau',
                't.CrOn',
                't.CrBy',
                DB::raw('COALESCE(u.User_Name, "Unknown") as created_by'),
                DB::raw('(SELECT COUNT(*) FROM admn_cnta_tag_mast ct WHERE ct.Admn_Tag_Mast_UIN = t.Admn_Tag_Mast_UIN) as contacts_count')
            )
            ->orderByDesc('t.CrOn')
            ->get();
    }

    #[Computed]
    public function selectedMonthLabel()
    {
        return isset($this->availableMonths[$this->selectedMonth])
            ? $this->availableMonths[$this->selectedMonth]
            : Carbon::createFromFormat('Y-m', $this->selectedMonth)->format('M-y');
    }

    public function toggleTagStatus($tagUIN)
    {
        $tag = Admn_Tag_Mast::find($tagUIN);
        if ($tag && $tag->Admn_Orga_Mast_UIN == session('selected_Orga_UIN')) {
            $tag->stau = ($tag->stau == self::STATUS_ACTIVE) ? 100202 : self::STATUS_ACTIVE;
            $tag->MoOn = now();
            $tag->save();

            $statusStr = ($tag->stau == self::STATUS_ACTIVE) ? 'Activated' : 'Deactivated';
            $this->logActivity('Master Data Edit', "Tag [{$tag->Name}] $statusStr");
            $this->dispatch('tags-updated');
        }
    }

    public function render()
    {
        return view('livewire.settings.tag');
    }
}
