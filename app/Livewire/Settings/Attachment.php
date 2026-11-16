<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Traits\ManagesSettings;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Attachment extends Component
{
    use ManagesSettings;

    public bool $enabled = true;
    public string $selectedMonth;

    public function mount()
    {
        $this->enabled = $this->getFeatureStatus('Attachment');
        $this->selectedMonth = now()->format('Y-m');
    }

    public function toggle()
    {
        $this->enabled = !$this->enabled;
        $this->updateFeatureStatus('Attachment', $this->enabled);
    }

    #[Computed]
    public function availableMonths()
    {
        $orgUIN = session('selected_Orga_UIN');
        if (!$orgUIN) {
            return [now()->format('Y-m') => now()->format('M-y')];
        }

        // Get months from document, bank, and profile picture attachment uploads
        $docMonths = DB::table('admn_docu_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('Docu_Atch_Path')
            ->where('Docu_Atch_Path', '!=', '')
            ->where('Docu_Atch_Path', 'LIKE', '%.%')
            ->whereNotNull('CrOn')
            ->selectRaw("DATE_FORMAT(CrOn, '%Y-%m') as month_key, DATE_FORMAT(CrOn, '%b-%y') as month_label")
            ->groupBy('month_key', 'month_label');

        $bankMonths = DB::table('admn_bank_attachments as ba')
            ->join('admn_user_bank_mast as bm', 'ba.Admn_User_Bank_Mast_UIN', '=', 'bm.Admn_User_Bank_Mast_UIN')
            ->join('admn_user_mast as um', 'bm.Admn_User_Mast_UIN', '=', 'um.Admn_User_Mast_UIN')
            ->where('um.Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('ba.CrOn')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('ba.Atch_Path')->where('ba.Atch_Path', '!=', '')->where('ba.Atch_Path', 'LIKE', '%.%');
                })->orWhere(function ($sub) {
                    $sub->whereNotNull('ba.Atch_Path_2')->where('ba.Atch_Path_2', '!=', '')->where('ba.Atch_Path_2', 'LIKE', '%.%');
                });
            })
            ->selectRaw("DATE_FORMAT(ba.CrOn, '%Y-%m') as month_key, DATE_FORMAT(ba.CrOn, '%b-%y') as month_label")
            ->groupBy('month_key', 'month_label');

        $profileMonths = DB::table('admn_user_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('Prfl_Pict')
            ->where('Prfl_Pict', '!=', '')
            ->where('Prfl_Pict', 'LIKE', '%.%')
            ->whereNotNull('CrOn')
            ->selectRaw("DATE_FORMAT(CrOn, '%Y-%m') as month_key, DATE_FORMAT(CrOn, '%b-%y') as month_label")
            ->groupBy('month_key', 'month_label');

        $months = $docMonths->union($bankMonths)->union($profileMonths)
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
    public function attachmentSummary()
    {
        $orgUIN = session('selected_Orga_UIN');
        if (!$orgUIN) {
            return collect();
        }

        [$year, $month] = explode('-', $this->selectedMonth);

        // Document attachments — group by file extension, count uploads
        $docUploads = DB::table('admn_docu_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('Docu_Atch_Path')
            ->where('Docu_Atch_Path', '!=', '')
            ->where('Docu_Atch_Path', 'LIKE', '%.%')
            ->whereYear('CrOn', $year)
            ->whereMonth('CrOn', $month)
            ->selectRaw("
                TRIM(UPPER(SUBSTRING_INDEX(Docu_Atch_Path, '.', -1))) as file_type,
                COUNT(*) as uploads,
                0 as deletes
            ")
            ->groupBy('file_type');

        // Bank attachment 1
        $bankUploads1 = DB::table('admn_bank_attachments as ba')
            ->join('admn_user_bank_mast as bm', 'ba.Admn_User_Bank_Mast_UIN', '=', 'bm.Admn_User_Bank_Mast_UIN')
            ->join('admn_user_mast as um', 'bm.Admn_User_Mast_UIN', '=', 'um.Admn_User_Mast_UIN')
            ->where('um.Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('ba.Atch_Path')
            ->where('ba.Atch_Path', '!=', '')
            ->where('ba.Atch_Path', 'LIKE', '%.%')
            ->whereYear('ba.CrOn', $year)
            ->whereMonth('ba.CrOn', $month)
            ->selectRaw("
                TRIM(UPPER(SUBSTRING_INDEX(ba.Atch_Path, '.', -1))) as file_type,
                COUNT(*) as uploads,
                0 as deletes
            ")
            ->groupBy('file_type');

        // Bank attachment 2
        $bankUploads2 = DB::table('admn_bank_attachments as ba')
            ->join('admn_user_bank_mast as bm', 'ba.Admn_User_Bank_Mast_UIN', '=', 'bm.Admn_User_Bank_Mast_UIN')
            ->join('admn_user_mast as um', 'bm.Admn_User_Mast_UIN', '=', 'um.Admn_User_Mast_UIN')
            ->where('um.Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('ba.Atch_Path_2')
            ->where('ba.Atch_Path_2', '!=', '')
            ->where('ba.Atch_Path_2', 'LIKE', '%.%')
            ->whereYear('ba.CrOn', $year)
            ->whereMonth('ba.CrOn', $month)
            ->selectRaw("
                TRIM(UPPER(SUBSTRING_INDEX(ba.Atch_Path_2, '.', -1))) as file_type,
                COUNT(*) as uploads,
                0 as deletes
            ")
            ->groupBy('file_type');

        // Profile pictures uploaded
        $profileUploads = DB::table('admn_user_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('Prfl_Pict')
            ->where('Prfl_Pict', '!=', '')
            ->where('Prfl_Pict', 'LIKE', '%.%')
            ->whereYear('CrOn', $year)
            ->whereMonth('CrOn', $month)
            ->selectRaw("
                TRIM(UPPER(SUBSTRING_INDEX(Prfl_Pict, '.', -1))) as file_type,
                COUNT(*) as uploads,
                0 as deletes
            ")
            ->groupBy('file_type');

        // Documents deleted in that month (MoOn set + Stau = 100202)
        $docDeletes = DB::table('admn_docu_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('Docu_Atch_Path')
            ->where('Docu_Atch_Path', '!=', '')
            ->where('Docu_Atch_Path', 'LIKE', '%.%')
            ->where('Stau', 100202)
            ->whereYear('MoOn', $year)
            ->whereMonth('MoOn', $month)
            ->selectRaw("
                TRIM(UPPER(SUBSTRING_INDEX(Docu_Atch_Path, '.', -1))) as file_type,
                0 as uploads,
                COUNT(*) as deletes
            ")
            ->groupBy('file_type');

        // Profile picture deletes / soft-deleted contacts
        $profileDeletes = DB::table('admn_user_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUIN)
            ->whereNotNull('Prfl_Pict')
            ->where('Prfl_Pict', '!=', '')
            ->where('Prfl_Pict', 'LIKE', '%.%')
            ->whereNotNull('Del_On')
            ->whereYear('Del_On', $year)
            ->whereMonth('Del_On', $month)
            ->selectRaw("
                TRIM(UPPER(SUBSTRING_INDEX(Prfl_Pict, '.', -1))) as file_type,
                0 as uploads,
                COUNT(*) as deletes
            ")
            ->groupBy('file_type');

        // Combine and aggregate
        $combined = $docUploads
            ->union($bankUploads1)
            ->union($bankUploads2)
            ->union($profileUploads)
            ->union($docDeletes)
            ->union($profileDeletes)
            ->get();

        return $combined->groupBy('file_type')->map(function ($rows, $fileType) {
            return (object)[
                'file_type' => $fileType,
                'uploads'   => $rows->sum('uploads'),
                'deletes'   => $rows->sum('deletes'),
            ];
        })->filter(fn($row) => !empty($row->file_type))->sortByDesc('uploads')->values();
    }

    #[Computed]
    public function selectedMonthLabel()
    {
        return $this->availableMonths[$this->selectedMonth]
            ?? Carbon::createFromFormat('Y-m', $this->selectedMonth)->format('M-y');
    }

    public function render()
    {
        return view('livewire.settings.attachment');
    }
}
