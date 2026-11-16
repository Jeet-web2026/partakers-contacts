<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Traits\LogsActivity;

class Index extends Component
{
    use LogsActivity;

    public string $activeTab = 'overview';

    public function mount()
    {
        if (!\App\Traits\ManagesSettings::isAdmin()) {
            abort(403, 'Unauthorized access: You must be an administrator to view settings.');
        }
        if (request()->has('tab')) {
            $this->activeTab = request()->query('tab');
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $orgaUin = session('selected_Orga_UIN');
        $totalUsers = 0;
        $activePlan = null;
        $planName = 'Basic Plan'; // Default fallback

        try {
            $totalUsers = \Illuminate\Support\Facades\DB::table('tbl_tran_user_assc')
                ->where('Orga_UIN', $orgaUin)
                ->count();
                
            $activePlan = \Illuminate\Support\Facades\DB::table('tbl_tran_sbcr_plan_vldt')
                ->where('Indv_Orga_UIN', $orgaUin)
                ->where('Vtcl_UIN', 112)
                ->where('Stau_UIN', 100201)
                ->orderBy('Sbcr_Plan_Vldt_UIN', 'desc')
                ->first();
                
            if ($activePlan && isset($activePlan->New_Plan_UIN)) {
                try {
                    $centralPlan = \Illuminate\Support\Facades\DB::connection('partkers_subs')->table('tbl_mast_sbcr_plan as p')
                        ->join('tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                        ->where('p.Plan_ID_UIN', $activePlan->New_Plan_UIN)
                        ->select('c.Plan_Catg_Name', 'p.Plan_Desc')
                        ->first();
                    if ($centralPlan && !empty($centralPlan->Plan_Catg_Name)) {
                        $planName = $centralPlan->Plan_Catg_Name . ' Plan';
                    } else {
                        throw new \Exception("Plan not found in central DB");
                    }
                } catch (\Exception $e) {
                    try {
                        $centralPlan = \Illuminate\Support\Facades\DB::table('u975025641_subscription.tbl_mast_sbcr_plan as p')
                            ->join('u975025641_subscription.tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                            ->where('p.Plan_ID_UIN', $activePlan->New_Plan_UIN)
                            ->select('c.Plan_Catg_Name', 'p.Plan_Desc')
                            ->first();
                        if ($centralPlan && !empty($centralPlan->Plan_Catg_Name)) {
                            $planName = $centralPlan->Plan_Catg_Name . ' Plan';
                        } else {
                            throw new \Exception("Plan not found in prefixed DB");
                        }
                    } catch (\Exception $ex) {
                        // Fallback to local DB
                        try {
                            $planMaster = \Illuminate\Support\Facades\DB::table('tbl_mast_sbcr_plan as p')
                                ->leftJoin('tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                                ->where('p.Plan_ID_UIN', $activePlan->New_Plan_UIN)
                                ->select('c.Plan_Catg_Name', 'p.Plan_Desc')
                                ->first();
                            if ($planMaster) {
                                if (!empty($planMaster->Plan_Catg_Name)) {
                                    $planName = $planMaster->Plan_Catg_Name . ' Plan';
                                } elseif (!empty($planMaster->Plan_Desc)) {
                                    $planName = $planMaster->Plan_Desc;
                                }
                            }
                        } catch (\Exception $ex2) {}
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Settings Overview DB Error: ' . $e->getMessage());
        }

        return view('livewire.settings.index', [
            'totalUsers' => $totalUsers,
            'activePlan' => $activePlan,
            'planName'   => $planName,
            'spaceUsedMb' => $this->calculateSpaceUsedMb($orgaUin),
        ])->layout('components.layouts.app');
    }

    /**
     * Calculate total storage used for the org:
     * - Actual attachment files on disk (Bank / Document / Profile folders)
     * - + 1.5 KB per contact row in DB
     */
    private function calculateSpaceUsedMb(int|string|null $orgUin): string
    {
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

        // 1.5 KB per contact record
        $contactCount = (int) \Illuminate\Support\Facades\DB::table('admn_user_mast')
            ->where('Admn_Orga_Mast_UIN', $orgUin)
            ->count();
        $dbMb = $contactCount * (1.5 / 1024);

        return number_format($attachMb + $dbMb, 2) . ' MB';
    }
}
