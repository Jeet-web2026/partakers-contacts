<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class History extends Component
{
    use WithPagination;

    public string $activityFilter = '';
    public int    $perPage        = 50;
    public string $selectedMonth;
    public string $selectedYear;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('m');
        $this->selectedYear  = now()->format('Y');
    }

    public function updatingActivityFilter(): void { $this->resetPage(); }
    public function updatingPerPage(): void        { $this->resetPage(); }
    public function updatingSelectedMonth(): void  { $this->resetPage(); }
    public function updatingSelectedYear(): void   { $this->resetPage(); }

    /** Fixed list of contact activities (per feedback spec). */
    public function getAvailableActivitiesProperty(): array
    {
        return [
            'Add Entry',
            'Edit Entry',
            'Import Contact',
            'Export Contact',
            'Delete Contact',
            'Link Generate',
            'Add Notes',
            'Edit Notes',
            'Delete Notes',
            'Add Tag',
            'Edit Tag',
            'Delete Tag',
            'Add Group',
            'Edit Group',
            'Delete Group',
            'Add with Attachment Entry',
            'Edit with Attachment Entry',
            'Contact View',
            'User Onboard',
            'User Removed',
        ];
    }

    /** Available months dictionary (01 => January, etc.) */
    public function getAvailableMonthsProperty(): array
    {
        return [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];
    }

    /** Available years from log data. */
    public function getAvailableYearsProperty(): array
    {
        $orgUin = session('selected_Orga_UIN');
        $years = DB::table('tbl_mast_sbcr_log_book')
            ->where('Indv_Orga_Uin', $orgUin)
            ->selectRaw("YEAR(CrOn) as yr")
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->toArray();

        $currentYear = (int) now()->format('Y');
        if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }
        return $years;
    }

    /** Summary tile: distinct users with activity this month. */
    public function getTotalUsersProperty(): int
    {
        $orgUin = session('selected_Orga_UIN');
        return (int) DB::table('tbl_mast_sbcr_log_book')
            ->where('Indv_Orga_Uin', $orgUin)
            ->whereYear('CrOn', $this->selectedYear)
            ->whereMonth('CrOn', $this->selectedMonth)
            ->distinct()
            ->count('User_UIN');
    }

    /** Summary tile: total entry count this month. */
    public function getTotalEntryProperty(): int
    {
        $orgUin = session('selected_Orga_UIN');
        return (int) DB::table('tbl_mast_sbcr_log_book')
            ->where('Indv_Orga_Uin', $orgUin)
            ->whereYear('CrOn', $this->selectedYear)
            ->whereMonth('CrOn', $this->selectedMonth)
            ->where('UOM', '!=', 'MB')
            ->sum('Cont');
    }

    /** Summary tile: total storage (MB) used by this org.
     *  Formula: actual attachment files on disk + 1.5 KB × contact DB rows
     */
    public function getTotalSpaceProperty(): string
    {
        $orgUin = session('selected_Orga_UIN');
        if (!$orgUin) return '0.00 MB';

        $totalBytes = 0;
        foreach (['Bank', 'Document', 'Profile'] as $folder) {
            $dirPath = public_path('storage/Attachment/' . $folder);
            if (is_dir($dirPath)) {
                foreach (glob($dirPath . DIRECTORY_SEPARATOR . $orgUin . '_*.*') ?: [] as $file) {
                    $totalBytes += filesize($file);
                }
            }
        }

        $attachMb = $totalBytes / (1024 * 1024);

        // 1.5 KB per contact record in DB
        $contactCount = (int) DB::table('admn_user_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUin)
            ->count();
        $dbMb = $contactCount * (1.5 / 1024);

        return number_format($attachMb + $dbMb, 2) . ' MB';
    }

    public function render()
    {
        $orgUin = session('selected_Orga_UIN');

        $query = DB::table('tbl_mast_sbcr_log_book as l')
            ->leftJoin('admn_user_logi_mast as u', 'l.User_UIN', '=', 'u.User_UIN')
            ->leftJoin('tbl_mast_sbcr_rvnu_catg as c', 'l.Sbcr_Rvnu_Catg_Name_UIN', '=', 'c.Sbcr_Uin')
            ->where('l.Indv_Orga_Uin', $orgUin)
            ->whereYear('l.CrOn', $this->selectedYear)
            ->whereMonth('l.CrOn', $this->selectedMonth);

        // Apply activity filter in WHERE clause BEFORE grouping
        if ($this->activityFilter !== '') {
            switch ($this->activityFilter) {
                case 'Add Notes':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 115)->where('l.Desc', 'like', '%Note%');
                    break;
                case 'Edit Notes':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 152)->where('l.Desc', 'like', '%Note%');
                    break;
                case 'Delete Notes':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 153)->where('l.Desc', 'like', '%Note%');
                    break;

                case 'Add Tag':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 115)->where('l.Desc', 'like', '%Tag%');
                    break;
                case 'Edit Tag':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 152)->where('l.Desc', 'like', '%Tag%');
                    break;
                case 'Delete Tag':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 153)->where('l.Desc', 'like', '%Tag%');
                    break;

                case 'Add Group':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 115)->where('l.Desc', 'like', '%Group%');
                    break;
                case 'Edit Group':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 152)->where('l.Desc', 'like', '%Group%');
                    break;
                case 'Delete Group':
                    $query->where('l.Sbcr_Rvnu_Catg_Name_UIN', 153)->where('l.Desc', 'like', '%Group%');
                    break;

                case 'Add Entry':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 154)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'New Entry')
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Add Entry');
                    });
                    break;
                case 'Edit Entry':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 112)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Edit Entry');
                    });
                    break;
                case 'Delete Contact':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 114)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Delete Entry');
                    });
                    break;
                case 'Import Contact':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 126)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Import Data')
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Import Contact')
                          ->orWhere('l.Desc', 'like', '%import%');
                    });
                    break;
                case 'Export Contact':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 127)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Export Data')
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Export Contact')
                          ->orWhere('l.Desc', 'like', '%export%');
                    });
                    break;
                case 'Link Generate':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 124)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Link Generate')
                          ->orWhere('l.Desc', 'like', '%link%');
                    });
                    break;
                case 'Add with Attachment Entry':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 111)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Add with Attachment');
                    });
                    break;
                case 'Edit with Attachment Entry':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 113)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'Edit with Attachment');
                    });
                    break;
                case 'Contact View':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 118)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'General Report')
                          ->orWhere('l.Desc', 'like', '%Viewed contact%');
                    });
                    break;
                case 'User Onboard':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 155)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'User Onboard');
                    });
                    break;
                case 'User Removed':
                    $query->where(function ($q) {
                        $q->where('l.Sbcr_Rvnu_Catg_Name_UIN', 156)
                          ->orWhere('c.Sbcr_Rvnu_Catg_Name', 'User Permission')
                          ->orWhere('l.Desc', 'like', '%user%');
                    });
                    break;
                default:
                    $query->where(function ($q) {
                        $q->where('c.Sbcr_Rvnu_Catg_Name', $this->activityFilter)
                          ->orWhere('l.Desc', 'like', '%' . $this->activityFilter . '%');
                    });
                    break;
            }
        }

        $query->select(
            DB::raw('DATE(l.CrOn) as log_date'),
            'u.User_UIN',
            'u.User_Name',
            'l.Sbcr_Rvnu_Catg_Name_UIN',
            'c.Sbcr_Rvnu_Catg_Name as raw_activity',
            DB::raw('MIN(l.Desc) as sample_desc'),
            DB::raw('SUM(l.Cont) as total_count'),
            'l.UOM',
            DB::raw('SUM(l.Size) as total_size')
        )
        ->groupBy('log_date', 'u.User_UIN', 'u.User_Name', 'l.Sbcr_Rvnu_Catg_Name_UIN', 'c.Sbcr_Rvnu_Catg_Name', 'l.UOM')
        ->orderBy('log_date', 'desc');

        $logs = $query->paginate($this->perPage);

        $logs->through(function ($log) {
            $catgUin  = $log->Sbcr_Rvnu_Catg_Name_UIN ?? 0;
            $desc     = $log->sample_desc ?? '';
            $catgName = $log->raw_activity ?? 'Other';

            if ($catgUin == 115) {
                if (stripos($desc, 'Note') !== false) {
                    $log->activity = 'Add Notes';
                } elseif (stripos($desc, 'Tag') !== false) {
                    $log->activity = 'Add Tag';
                } elseif (stripos($desc, 'Group') !== false) {
                    $log->activity = 'Add Group';
                } else {
                    $log->activity = 'Master Data Entry';
                }
            } elseif ($catgUin == 152) {
                if (stripos($desc, 'Note') !== false) {
                    $log->activity = 'Edit Notes';
                } elseif (stripos($desc, 'Tag') !== false) {
                    $log->activity = 'Edit Tag';
                } elseif (stripos($desc, 'Group') !== false) {
                    $log->activity = 'Edit Group';
                } else {
                    $log->activity = 'Master Data Edit';
                }
            } elseif ($catgUin == 153) {
                if (stripos($desc, 'Note') !== false) {
                    $log->activity = 'Delete Notes';
                } elseif (stripos($desc, 'Tag') !== false) {
                    $log->activity = 'Delete Tag';
                } elseif (stripos($desc, 'Group') !== false) {
                    $log->activity = 'Delete Group';
                } else {
                    $log->activity = 'Master Data Delete';
                }
            } elseif ($catgUin == 154 || $catgName === 'New Entry') {
                $log->activity = 'Add Entry';
            } elseif ($catgUin == 112 || $catgName === 'Edit Entry') {
                $log->activity = 'Edit Entry';
            } elseif ($catgUin == 114 || $catgName === 'Delete Entry') {
                $log->activity = 'Delete Contact';
            } elseif ($catgUin == 126 || $catgName === 'Import Data' || stripos($desc, 'import') !== false) {
                $log->activity = 'Import Contact';
            } elseif ($catgUin == 127 || $catgName === 'Export Data' || stripos($desc, 'export') !== false) {
                $log->activity = 'Export Contact';
            } elseif ($catgUin == 124 || $catgName === 'Link Generate') {
                $log->activity = 'Link Generate';
            } elseif ($catgUin == 111 || $catgName === 'Add with Attachment') {
                $log->activity = 'Add with Attachment Entry';
            } elseif ($catgUin == 113 || $catgName === 'Edit with Attachment') {
                $log->activity = 'Edit with Attachment Entry';
            } elseif ($catgUin == 118 || $catgName === 'General Report') {
                $log->activity = 'Contact View';
            } else {
                $log->activity = $catgName;
            }

            return $log;
        });

        return view('livewire.settings.history', [
            'logs' => $logs,
        ]);
    }
}
