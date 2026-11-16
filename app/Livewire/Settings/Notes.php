<?php

namespace App\Livewire\Settings;

use Livewire\Component;

use App\Traits\ManagesSettings;
use App\Traits\LogsActivity;
use App\Livewire\Traits\HasMaxConstants;
use App\Models\AdmnCntaNoteComnt;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Notes extends Component
{
    use ManagesSettings, HasMaxConstants, LogsActivity;

    public bool $enabled = true;
    public string $selectedMonth;

    public function mount()
    {
        $this->enabled = $this->getFeatureStatus('Notes');
        $this->selectedMonth = now()->format('Y-m');
    }

    public function toggle()
    {
        $this->enabled = !$this->enabled;
        $this->updateFeatureStatus('Notes', $this->enabled);
    }

    #[Computed]
    public function availableMonths()
    {
        $orgUin = session('selected_Orga_UIN');

        $months = DB::table('admn_cnta_note_comnt')
            ->where('Orga_UIN', $orgUin)
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
    public function allNotes()
    {
        $orgUin = session('selected_Orga_UIN');
        [$year, $month] = explode('-', $this->selectedMonth);

        return DB::table('admn_cnta_note_comnt as n')
            ->leftJoin('admn_user_logi_mast as u', 'n.CrBy', '=', 'u.User_UIN')
            ->where('n.Orga_UIN', $orgUin)
            ->whereYear('n.CrOn', $year)
            ->whereMonth('n.CrOn', $month)
            ->select(
                'n.Admn_Cnta_Note_Comnt_UIN',
                'n.Comnt_Text',
                'n.Category',
                'n.Stau_UIN',
                'n.CrOn',
                'n.CrBy',
                DB::raw('COALESCE(u.User_Name, "Unknown") as created_by')
            )
            ->orderByDesc('n.CrOn')
            ->get();
    }

    #[Computed]
    public function parents()
    {
        $org = session('selected_Orga_UIN');

        return AdmnCntaNoteComnt::where('Stau_UIN', self::STATUS_ACTIVE)
            ->where(function ($q) use ($org) {
                $q->whereNull('Orga_UIN')
                  ->orWhere('CrBy', 101)
                  ->orWhere('Orga_UIN', $org);
            })
            ->pluck('Category')
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    #[Computed]
    public function selectedMonthLabel()
    {
        return isset($this->availableMonths[$this->selectedMonth])
            ? $this->availableMonths[$this->selectedMonth]
            : Carbon::createFromFormat('Y-m', $this->selectedMonth)->format('M-y');
    }

    public function toggleNoteStatus($noteUIN)
    {
        $note = AdmnCntaNoteComnt::find($noteUIN);
        if ($note && $note->Orga_UIN == session('selected_Orga_UIN')) {
            $note->Stau_UIN = ($note->Stau_UIN == self::STATUS_ACTIVE) ? 100202 : self::STATUS_ACTIVE;
            $note->MoOn     = now();
            $note->save();

            $statusStr = ($note->Stau_UIN == self::STATUS_ACTIVE) ? 'Activated' : 'Deactivated';
            $this->logActivity('Master Data Edit', "Note [{$note->Category}] $statusStr");
            $this->dispatch('refreshQuickComments');
        }
    }

    public function render()
    {
        return view('livewire.settings.notes');
    }
}
