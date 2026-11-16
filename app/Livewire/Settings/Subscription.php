<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;
use App\Traits\ValidatesSubscriptionLimits;

class Subscription extends Component
{
    use LogsActivity, ValidatesSubscriptionLimits;

    // Profile Details
    public string $email = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $mobile = '';
    public string $orgName = '';
    public string $orgWebsite = '';
    public $orgTypeUin = null;
    public $stateUin = null;
    public string $zipCode = '';
    public string $address = '';

    // Active Association
    public $association = null;
    public $activeSub = null;
    public $queuedSub = null; // Next plan purchased but not yet started (Stau_UIN = 100202)
    public ?string $queuedPlanName = null;
    public ?string $queuedStartDate = null;
    public ?string $queuedEndDate = null;
    public ?string $queuedDuration = null;

    // Plan & Allowance Info
    public string $planName = '';
    public string $expiryDate = '';
    public string $verticalName = 'Contacts';
    public string $startDate = '';
    public string $endDate = '';
    public string $durationDays = '';
    public string $statusName = 'Active';
    
    public int $userAlow = 0;
    public int $userUsed = 0;
    public int $baseUsers = 0;
    public int $adnlUsers = 0;   // purchased add-ons only
    public int $bnftUsers = 0;   // plan benefit seats

    public int $entyAlow = 0;
    public int $entyUsed = 0;
    public int $baseEntries = 0;
    public int $adnlEntries = 0; // purchased add-ons only
    public int $bnftEntries = 0; // plan benefit rows
    public int $crPtEntries = 0; // credit-point bonus rows

    public float $spceAlow = 0;
    public float $spceUsed = 0;
    public float $baseStorage = 0;
    public float $adnlStorage = 0; // purchased add-ons only
    public float $bnftStorage = 0; // plan benefit MB
    public float $crPtStorage = 0; // credit-point bonus MB

    // Add-on Selections
    public int $additionalUsers = 0;
    public int $additionalStorage = 0; // in MB
    public int $additionalEntries = 0;

    // Additional Purchase Logs / History
    public array $purchaseLogs = [];

    // 7-Day Refund Feature Properties
    public bool $isEligibleForRefund = false;
    public bool $hasPendingRefundRequest = false;
    public bool $hasApprovedRefundRequestAwaitingPayment = false;
    public bool $hasApprovedRefundRequest = false;
    public bool $hasDeniedRefundRequest = false;
    public ?array $activeRefundRequest = null;
    public int $refundDaysLeft = 0;
    public string $refundableAmount = '₹0.00';
    public float $planAmountRaw = 0.0;

    public bool $showRefundModal = false;
    public string $refundReasonCategory = 'Not satisfied with features';
    public string $refundReasonDetails = '';
    public string $refundErrorMessage = '';
    public string $refundSuccessMessage = '';

    public function mount()
    {
        $user = \App\Helpers\AuthHelper::getAuthenticatedUser();
        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        // Fetch complete user profile including mobile number
        $profile = DB::table('admn_user_logi_mast')
            ->where('User_UIN', $user->User_UIN)
            ->first();

        if ($profile) {
            $this->email = $profile->Prmy_Emai ?? '';
            $this->mobile = $profile->Prmy_MoNo ?? '';

            // Split name into first and last name
            $fullName = trim($profile->User_Name ?? '');
            $nameParts = explode(' ', $fullName, 2);
            $this->firstName = $nameParts[0] ?? '';
            $this->lastName = $nameParts[1] ?? '';
        }

        // Active organization and extended details
        $org = \App\Helpers\AuthHelper::getSelectedOrganization();
        $this->orgName = $org ? $org->name : '';
        if ($org && isset($org->Orga_UIN)) {
            $orgDetails = DB::table('admn_orga_mast')->where('Orga_UIN', $org->Orga_UIN)->first();
            if ($orgDetails) {
                if (empty($this->orgName)) {
                    $this->orgName = $orgDetails->Orga_Name ?? '';
                }
                $this->orgWebsite = $orgDetails->Web_Addr ?? '';
                $this->orgTypeUin = $orgDetails->Orga_Type_UIN ?? null;
                $this->stateUin   = $orgDetails->Stat_UIN ?? null;
                $this->zipCode    = (string) ($orgDetails->pin_UIN ?? '');
                $this->address    = $orgDetails->Addr_Line1 ?? ($orgDetails->Address ?? '');
            }
        }

        $vertUin = 112; // contacts vertical

        // Fetch active association from tbl_tran_user_assc
        $this->association = DB::table('tbl_tran_user_assc')
            ->where('User_UIN', $user->User_UIN)
            ->where('vert_uin', $vertUin)
            ->where('Stau_UIN', 100201) // Active status
            ->first();

        $plan = $this->getActivePlanValidation(session('selected_Orga_UIN'), true);

        if ($plan) {
            // Get Plan Name from Central Database if possible
            $planName = 'Active Plan';
            $panAmt = 0.0;
            if ($plan->New_Plan_UIN) {
                try {
                    $centralPlan = DB::connection('partkers_subs')->table('tbl_mast_sbcr_plan as p')
                        ->join('tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                        ->where('p.Plan_ID_UIN', $plan->New_Plan_UIN)
                        ->select('c.Plan_Catg_Name', 'p.Pan_Amt', 'p.Plan_Desc')
                        ->first();
                    if ($centralPlan && isset($centralPlan->Plan_Catg_Name)) {
                        $planName = $centralPlan->Plan_Catg_Name . ' Plan';
                        $panAmt = (float)($centralPlan->Pan_Amt ?? 0);
                    } else {
                        throw new \Exception("Plan not found in central DB");
                    }
                } catch (\Exception $e) {
                    // Fallback to schema prefix or local DB
                    try {
                        $centralPlan = DB::table('u975025641_subscription.tbl_mast_sbcr_plan as p')
                            ->join('u975025641_subscription.tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                            ->where('p.Plan_ID_UIN', $plan->New_Plan_UIN)
                            ->select('c.Plan_Catg_Name', 'p.Pan_Amt', 'p.Plan_Desc')
                            ->first();
                        if ($centralPlan && isset($centralPlan->Plan_Catg_Name)) {
                            $planName = $centralPlan->Plan_Catg_Name . ' Plan';
                            $panAmt = (float)($centralPlan->Pan_Amt ?? 0);
                        } else {
                            throw new \Exception("Plan not found in prefixed DB");
                        }
                    } catch (\Exception $ex2) {
                        try {
                            $localPlan = DB::table('tbl_mast_sbcr_plan as p')
                                ->leftJoin('tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                                ->where('p.Plan_ID_UIN', $plan->New_Plan_UIN)
                                ->select('c.Plan_Catg_Name', 'p.Pan_Amt', 'p.Plan_Desc')
                                ->first();
                            if ($localPlan) {
                                if (!empty($localPlan->Plan_Catg_Name)) {
                                    $planName = $localPlan->Plan_Catg_Name . ' Plan';
                                } elseif (!empty($localPlan->Plan_Desc)) {
                                    $planName = $localPlan->Plan_Desc;
                                }
                                $panAmt = (float)($localPlan->Pan_Amt ?? 0);
                            }
                        } catch (\Exception $ex) {
                            \Log::warning('Could not query plan name: ' . $ex->getMessage());
                        }
                    }
                }
            }

            $this->planName = $planName;
            $this->verticalName = 'Contacts';

            // Fetch actual wallet transaction amount paid for this active plan
            $actualAmountPaid = 0.0;
            $waltUin = null;
            $orgaUin = session('selected_Orga_UIN');
            if ($orgaUin) {
                $walletRec = null;
                try {
                    $walletRec = DB::connection('partkers_subs')->table('tbl_mast_sbcr_walt')
                        ->where('Orga_UIN', $orgaUin)
                        ->where('Vtcl_UIN', 112)
                        ->orderBy('Walt_UIN', 'desc')
                        ->first();
                } catch (\Exception $e1) {
                    try {
                        $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partkers_subs');
                        $walletRec = DB::table("{$subsDb}.tbl_mast_sbcr_walt")
                            ->where('Orga_UIN', $orgaUin)
                            ->where('Vtcl_UIN', 112)
                            ->orderBy('Walt_UIN', 'desc')
                            ->first();
                    } catch (\Exception $e2) {
                        try {
                            $walletRec = DB::table('tbl_mast_sbcr_walt')
                                ->where('Orga_UIN', $orgaUin)
                                ->where('Vtcl_UIN', 112)
                                ->orderBy('Walt_UIN', 'desc')
                                ->first();
                        } catch (\Exception $e3) {}
                    }
                }
                if ($walletRec) {
                    $actualAmountPaid = (float) ($walletRec->Recg_Amt ?? ($walletRec->Cr_Amt ?? 0));
                    $waltUin = $walletRec->Walt_UIN ?? null;
                }
            }

            if ($actualAmountPaid <= 0 && $panAmt > 0) {
                $actualAmountPaid = $panAmt;
            }

            $plan->Recg_Amt = $actualAmountPaid;
            $plan->Ordr_Amt = $actualAmountPaid;
            if ($waltUin) {
                $plan->Walt_UIN = $waltUin;
            }

            $this->planAmountRaw = $actualAmountPaid;
            $this->refundableAmount = '₹' . number_format($actualAmountPaid, 2);

            // Start Date: Sbcr_Frst_Dt or Plan_Strt_Dt (fallback to CrOn)
            $startRaw = !empty($plan->Sbcr_Frst_Dt) ? $plan->Sbcr_Frst_Dt : (!empty($plan->Plan_Strt_Dt) ? $plan->Plan_Strt_Dt : ($plan->CrOn ?? null));
            $firstDt  = $startRaw ? \Carbon\Carbon::parse($startRaw) : null;
            $lastDt   = isset($plan->Sbcr_Last_Dt) && $plan->Sbcr_Last_Dt ? \Carbon\Carbon::parse($plan->Sbcr_Last_Dt) : null;

            $this->startDate    = $firstDt ? $firstDt->format('d M Y') : '-';
            $this->endDate      = $lastDt ? $lastDt->format('d M Y') : '-';
            $this->expiryDate   = $this->endDate;

            // Status: Approved / Active / Pending
            if (isset($plan->Stau_Name) && !empty($plan->Stau_Name)) {
                $this->statusName = $plan->Stau_Name;
            } else {
                if (isset($plan->Stau_UIN)) {
                    if ($plan->Stau_UIN == 100201) {
                        $this->statusName = 'Approved';
                    } elseif ($plan->Stau_UIN == 100204) {
                        $this->statusName = 'Verified';
                    } elseif ($plan->Stau_UIN == 100203) {
                        $this->statusName = 'Rejected';
                    } else {
                        $this->statusName = 'Pending';
                    }
                } else {
                    $this->statusName = 'Pending';
                }
            }

            // The plan record is auto-marked Approved at purchase; the real approval state is the
            // payment (wallet) status, so let that drive the badge when it can be resolved.
            $paymentStatus = $this->resolvePaymentStatusName($plan);
            if ($paymentStatus) {
                $this->statusName = $paymentStatus;
            }

            // Duration in Days
            if (isset($plan->Sbcr_Dura) && (int)$plan->Sbcr_Dura > 0) {
                $this->durationDays = (int)$plan->Sbcr_Dura . ' Days';
            } elseif ($firstDt && $lastDt) {
                $days = $firstDt->diffInDays($lastDt);
                $this->durationDays = ($days > 0 ? $days : 30) . ' Days';
            } else {
                $this->durationDays = '30 Days';
            }
            
            // Users
            $this->baseUsers  = (int) ($plan->Sbcr_User  ?? 0);
            $this->adnlUsers  = (int) ($plan->Adnl_User  ?? 0); // purchased only
            $this->bnftUsers  = (int) ($plan->Bnft_User  ?? 0); // plan benefit
            $this->userAlow   = $this->baseUsers + $this->adnlUsers + $this->bnftUsers;
            $this->userUsed   = (int) ($plan->Ulzn_User  ?? 0);

            // Entries
            $this->baseEntries = (int) ($plan->Sbcr_Ntry     ?? 0);
            $this->adnlEntries = (int) ($plan->Adnl_Ntry     ?? 0); // purchased only
            $this->bnftEntries = (int) ($plan->Bnft_Ntry     ?? 0); // plan benefit
            $this->crPtEntries = (int) ($plan->Cr_Pont_Ntry  ?? 0); // credit-point bonus
            $this->entyAlow    = $this->baseEntries + $this->adnlEntries + $this->bnftEntries + $this->crPtEntries;

            // entyUsed: read directly from Past Record logs (same formula as History page)
            // so both places always show the identical count.
            $orgUinForEntries = $plan->Indv_Orga_UIN ?? ($org ? $org->Orga_UIN : null);
            $planStart        = $plan->Plan_Strt_Dt ?? null;
            $planEnd          = $plan->Sbcr_Last_Dt  ?? null;
            $entryQuery = DB::table('tbl_mast_sbcr_log_book')
                ->where('Indv_Orga_Uin', $orgUinForEntries)
                ->where('UOM', '!=', 'MB');
            if ($planStart) {
                $entryQuery->where('CrOn', '>=', $planStart);
            }
            if ($planEnd) {
                $entryQuery->where('CrOn', '<=', $planEnd);
            }
            $this->entyUsed = (int) $entryQuery->sum('Cont');

            // Storage
            $this->baseStorage = (float) ($plan->Sbcr_Spce    ?? 0);
            $this->adnlStorage = (float) ($plan->Adnl_Spce    ?? 0); // purchased only
            $this->bnftStorage = (float) ($plan->Bnft_Spce    ?? 0); // plan benefit
            $this->crPtStorage = (float) ($plan->Cr_Pont_Spce ?? 0); // credit-point bonus
            $this->spceAlow    = $this->baseStorage + $this->adnlStorage + $this->bnftStorage + $this->crPtStorage;
            
            $indvOrgaUin = $plan->Indv_Orga_UIN ?? ($org ? $org->Orga_UIN : null);
            $planStartDt = $plan->__plan_start ?? $plan->Plan_Strt_Dt ?? null;
            $planEndDt   = $plan->__plan_end ?? $plan->Sbcr_Last_Dt ?? null;
            
            $this->spceUsed = $this->calculateStorageUsed($indvOrgaUin, $planStartDt, $planEndDt);
            
            $this->activeSub = $plan;

            // Fetch or construct Purchase Logs
            $this->loadPurchaseLogs($plan);

            // Check 7-Day Refund Eligibility & Status
            $this->checkRefundEligibility($plan);

            // If a refund request is pending or approved, set allowed subscriptions/quotas to 0
            if ($this->hasPendingRefundRequest || $this->hasApprovedRefundRequestAwaitingPayment || $this->hasApprovedRefundRequest) {
                $this->userAlow = 0;
                $this->baseUsers = 0;
                $this->adnlUsers = 0;
                $this->bnftUsers = 0;

                $this->entyAlow = 0;
                $this->baseEntries = 0;
                $this->adnlEntries = 0;
                $this->bnftEntries = 0;
                $this->crPtEntries = 0;

                $this->spceAlow = 0;
                $this->baseStorage = 0;
                $this->adnlStorage = 0;
                $this->bnftStorage = 0;
                $this->crPtStorage = 0;
            }
        } else {
            // Fallback default values
            $this->planName = 'No Active Plan';
            $this->expiryDate = '-';
            $this->verticalName = 'Contacts';
            $this->startDate = '-';
            $this->endDate = '-';
            $this->durationDays = '-';
            $this->statusName = 'Inactive';
            $this->userAlow = 0; $this->userUsed = 0; $this->baseUsers = 0; $this->adnlUsers = 0; $this->bnftUsers = 0;
            $this->entyAlow = 0; $this->entyUsed = 0; $this->baseEntries = 0; $this->adnlEntries = 0; $this->bnftEntries = 0; $this->crPtEntries = 0;
            $this->spceAlow = 0; $this->spceUsed = 0; $this->baseStorage = 0; $this->adnlStorage = 0; $this->bnftStorage = 0; $this->crPtStorage = 0;
            $this->activeSub = null;
            $this->purchaseLogs = [];
        }

        // Fetch Queued / Upcoming Plan (Stau_UIN = 100202) – purchased but not yet active
        $orgaUin = session('selected_Orga_UIN');
        if ($orgaUin) {
            $activePlanVldtUin = $this->activeSub ? ($this->activeSub->Sbcr_Plan_Vldt_UIN ?? null) : null;
            $activePlanEndDt = $this->activeSub && !empty($this->activeSub->Sbcr_Last_Dt)
                ? \Carbon\Carbon::parse($this->activeSub->Sbcr_Last_Dt)->startOfDay()
                : null;

            $queued = DB::table('tbl_tran_sbcr_plan_vldt')
                ->where('Indv_Orga_UIN', $orgaUin)
                ->where('Vtcl_UIN', $vertUin)
                ->where('Stau_UIN', 100202) // Queued / Advance plan
                ->when($activePlanVldtUin, fn($q) => $q->where('Sbcr_Plan_Vldt_UIN', '!=', $activePlanVldtUin))
                ->orderBy('Sbcr_Plan_Vldt_UIN', 'asc')
                ->first();

            // Only display Upcoming plan if its start date is AFTER the active plan's end date
            if ($queued) {
                $qStrt = !empty($queued->Plan_Strt_Dt) ? \Carbon\Carbon::parse($queued->Plan_Strt_Dt)->startOfDay() : null;
                if ($activePlanEndDt && $qStrt && $qStrt->lt($activePlanEndDt)) {
                    $queued = null; // Do not display if it starts before the active plan ends (same-day start is a valid queued plan)
                }
            }

            if ($queued) {
                $this->queuedSub = $queued;

                // Resolve queued plan name
                $queuedPlanName = 'Upcoming Plan';
                if (!empty($queued->New_Plan_UIN)) {
                    try {
                        $qPlan = DB::connection('partkers_subs')
                            ->table('tbl_mast_sbcr_plan as p')
                            ->join('tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                            ->where('p.Plan_ID_UIN', $queued->New_Plan_UIN)
                            ->select('c.Plan_Catg_Name')
                            ->first();
                        if ($qPlan && !empty($qPlan->Plan_Catg_Name)) {
                            $queuedPlanName = $qPlan->Plan_Catg_Name . ' Plan';
                        } else {
                            throw new \Exception("Not found in central DB");
                        }
                    } catch (\Exception $e) {
                        try {
                            $qPlan = DB::table('u975025641_subscription.tbl_mast_sbcr_plan as p')
                                ->join('u975025641_subscription.tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                                ->where('p.Plan_ID_UIN', $queued->New_Plan_UIN)
                                ->select('c.Plan_Catg_Name')
                                ->first();
                            if ($qPlan && !empty($qPlan->Plan_Catg_Name)) {
                                $queuedPlanName = $qPlan->Plan_Catg_Name . ' Plan';
                            } else {
                                throw new \Exception("Not found in prefixed DB");
                            }
                        } catch (\Exception $ex2) {
                            try {
                                $qLocal = DB::table('tbl_mast_sbcr_plan as p')
                                    ->leftJoin('tbl_mast_sbcr_plan_catg as c', 'p.Plan_Catg_UIN', '=', 'c.Plan_Catg_UIN')
                                    ->where('p.Plan_ID_UIN', $queued->New_Plan_UIN)
                                    ->select('c.Plan_Catg_Name', 'p.Plan_Desc')
                                    ->first();
                                if ($qLocal) {
                                    if (!empty($qLocal->Plan_Catg_Name)) {
                                        $queuedPlanName = $qLocal->Plan_Catg_Name . ' Plan';
                                    } elseif (!empty($qLocal->Plan_Desc)) {
                                        $queuedPlanName = $qLocal->Plan_Desc;
                                    }
                                }
                            } catch (\Exception $ex) {}
                        }
                    }
                }

                $this->queuedPlanName = $queuedPlanName;

                $qStrt = !empty($queued->Plan_Strt_Dt) ? \Carbon\Carbon::parse($queued->Plan_Strt_Dt) : null;
                $qEnd  = !empty($queued->Sbcr_Last_Dt) ? \Carbon\Carbon::parse($queued->Sbcr_Last_Dt) : null;

                $this->queuedStartDate = $qStrt ? $qStrt->format('d M Y') : '-';
                $this->queuedEndDate   = $qEnd  ? $qEnd->format('d M Y')  : '-';

                if ($qStrt && $qEnd) {
                    $this->queuedDuration = $qStrt->diffInDays($qEnd) . ' Days';
                } elseif (!empty($queued->Sbcr_Dura)) {
                    $this->queuedDuration = (int)$queued->Sbcr_Dura . ' Days';
                } else {
                    $this->queuedDuration = '-';
                }
            }
        }
    }

    /**
     * Status name of the payment (wallet) behind the given plan, or null if it can't be resolved.
     */
    protected function resolvePaymentStatusName($plan): ?string
    {
        $orgaUin = session('selected_Orga_UIN');
        if (!$orgaUin || empty($plan->New_Plan_UIN)) {
            return null;
        }

        $sources = [
            fn () => DB::connection('partkers_subs'),
            fn () => DB::connection(),
        ];

        foreach ($sources as $conn) {
            try {
                $db = $conn();
                $sbrd = $db->table('tbl_mast_sbcr_sbrd')
                    ->where('Orga_UIN', $orgaUin)
                    ->where('Plan_UIN', $plan->New_Plan_UIN)
                    ->orderBy('Sbrd_UIN', 'desc')
                    ->first();
                if (!$sbrd || empty($sbrd->Walt_UIN)) {
                    continue;
                }
                $stau = $db->table('tbl_mast_sbcr_walt')->where('Walt_UIN', $sbrd->Walt_UIN)->value('Stau_UIN');
                if ($stau === null) {
                    continue;
                }
                return match ((int) $stau) {
                    100201 => 'Approved',
                    100204 => 'Verified',
                    100202 => 'Pending',
                    100203 => 'Rejected',
                    default => null,
                };
            } catch (\Exception $e) {
                // try next source
            }
        }

        return null;
    }

    /**
     * Load or construct purchase transaction logs for add-ons
     */
    protected function loadPurchaseLogs($plan)
    {
        $logs = [];
        $orgaUin = session('selected_Orga_UIN');

        // Try querying transaction history table if available in central or local DB
        $tableQueries = [
            'u975025641_subscription.tbl_tran_sbcr_order',
            'tbl_tran_sbcr_order',
            'tbl_tran_sbcr_paym'
        ];

        foreach ($tableQueries as $tableName) {
            try {
                $dbLogs = DB::table($tableName)
                    ->where('Indv_Orga_UIN', $orgaUin)
                    ->orderBy('CrOn', 'desc')
                    ->get();

                if ($dbLogs->isNotEmpty()) {
                    foreach ($dbLogs as $log) {
                        $rawAmt = $log->Totl_Amt ?? $log->Ordr_Amt ?? $log->Paym_Amt ?? $log->Amount ?? ($this->planAmountRaw ?? 0);
                        $formattedAmt = '₹' . number_format((float)$rawAmt, 2);

                        if (empty($logs) && (float)$rawAmt > 0) {
                            $this->refundableAmount = $formattedAmt;
                        }

                        $logs[] = [
                            'date'   => \Carbon\Carbon::parse($log->CrOn ?? now())->format('d M Y'),
                            'user'   => !empty($log->Adnl_User) ? $log->Adnl_User : ($this->adnlUsers > 0 ? $this->adnlUsers : '-'),
                            'entry'  => !empty($log->Adnl_Ntry) ? number_format($log->Adnl_Ntry) : ($this->adnlEntries > 0 ? number_format($this->adnlEntries) : '-'),
                            'space'  => !empty($log->Adnl_Spce) ? $log->Adnl_Spce . ' MB' : ($this->adnlStorage > 0 ? $this->adnlStorage . ' MB' : '-'),
                            'amount' => $formattedAmt,
                        ];
                    }
                    break;
                }
            } catch (\Exception $e) {
                // Table doesn't exist or error, try next
            }
        }

        // Fallback: If active additional resources exist, construct entry matching the admin purchase record
        if (empty($logs) && ($this->adnlUsers > 0 || $this->adnlStorage > 0 || $this->adnlEntries > 0)) {
            $purchDate = isset($plan->UpOn) && $plan->UpOn ? $plan->UpOn : ($plan->CrOn ?? null);
            $formattedDate = $purchDate ? \Carbon\Carbon::parse($purchDate)->format('d M Y') : \Carbon\Carbon::now()->format('d M Y');

            $logs[] = [
                'date'   => $formattedDate,
                'user'   => $this->adnlUsers > 0 ? $this->adnlUsers : '-',
                'entry'  => $this->adnlEntries > 0 ? number_format($this->adnlEntries) : '-',
                'space'  => $this->adnlStorage > 0 ? ($this->adnlStorage == (int)$this->adnlStorage ? (int)$this->adnlStorage : $this->adnlStorage) . ' MB' : '-',
                'amount' => $this->refundableAmount,
            ];
        }

        $this->purchaseLogs = $logs;
    }

    /**
     * Check 7-Day Refund Eligibility & Active Request Status
     * Business Rules:
     * 1. 7-Day Window from purchase date (CrOn or Sbcr_Frst_Dt).
     * 2. First-time purchase ONLY (1st purchase per Org/Vertical). From 2nd purchase onwards, refund link is disabled.
     * 3. Plan Duration <= 30 Days ONLY. If plan duration > 30 days (e.g. 60, 90, 365 days), refund link is disabled.
     */
    protected function checkRefundEligibility($plan)
    {
        $orgaUin = session('selected_Orga_UIN');
        $user = \App\Helpers\AuthHelper::getAuthenticatedUser();
        $vertUin = $plan->Vtcl_UIN ?? ($this->association->vert_uin ?? 112);

        // Rule A: Check Plan Duration (Must be <= 30 Days)
        $startRaw = isset($plan->CrOn) && $plan->CrOn ? $plan->CrOn : ($plan->Sbcr_Frst_Dt ?? null);
        $firstDt  = $startRaw ? \Carbon\Carbon::parse($startRaw) : null;
        $lastDt   = isset($plan->Sbcr_Last_Dt) && $plan->Sbcr_Last_Dt ? \Carbon\Carbon::parse($plan->Sbcr_Last_Dt) : null;

        $planDurationDays = 30;
        if (isset($plan->Sbcr_Dura) && (int)$plan->Sbcr_Dura > 0) {
            $planDurationDays = (int) $plan->Sbcr_Dura;
        } elseif ($firstDt && $lastDt) {
            $planDurationDays = (int) $firstDt->diffInDays($lastDt);
        }

        if ($planDurationDays > 30) {
            $this->isEligibleForRefund = false;
            $this->refundDaysLeft = 0;
            $this->checkExistingRefundRequest($orgaUin);
            return;
        }

        // Rule B: Check First-Time Purchase Only (Order/Purchase Count <= 1)
        $purchaseCount = 1;
        try {
            try {
                $purchaseCount = DB::connection('partkers_subs')->table('tbl_tran_sbcr_order')
                    ->where('Indv_Orga_UIN', $orgaUin)
                    ->where('Vtcl_UIN', $vertUin)
                    ->whereIn('Stau_UIN', [100201, 100204]) // Approved / Verified orders
                    ->count();
            } catch (\Exception $ex1) {
                try {
                    $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partakers');
                    $purchaseCount = DB::table("{$subsDb}.tbl_tran_sbcr_order")
                        ->where('Indv_Orga_UIN', $orgaUin)
                        ->where('Vtcl_UIN', $vertUin)
                        ->whereIn('Stau_UIN', [100201, 100204])
                        ->count();
                } catch (\Exception $ex2) {
                    $purchaseCount = DB::table('tbl_tran_sbcr_order')
                        ->where('Indv_Orga_UIN', $orgaUin)
                        ->whereIn('Stau_UIN', [100201, 100204])
                        ->count();
                }
            }
        } catch (\Exception $e) {
            $purchaseCount = 1; // Default to 1 if order history query is not available
        }

        if ($purchaseCount > 1) {
            $this->isEligibleForRefund = false;
            $this->refundDaysLeft = 0;
            $this->checkExistingRefundRequest($orgaUin);
            return;
        }

        // Rule C: Calculate 7-Day Refund Window from purchase date
        if ($startRaw) {
            $purchDate = \Carbon\Carbon::parse($startRaw);
            $daysDiff = (int) $purchDate->diffInDays(\Carbon\Carbon::now());
            if ($daysDiff <= 7) {
                $this->isEligibleForRefund = true;
                $this->refundDaysLeft = max(0, 7 - $daysDiff);
            } else {
                $this->isEligibleForRefund = false;
                $this->refundDaysLeft = 0;
            }
        } else {
            $this->isEligibleForRefund = false;
            $this->refundDaysLeft = 0;
        }

        $this->checkExistingRefundRequest($orgaUin, $plan);
    }

    private function isBetweenDates($date, $from, $to)
    {
        $cDate = \Carbon\Carbon::parse($date)->startOfDay();
        if ($from) {
            $cFrom = \Carbon\Carbon::parse($from)->startOfDay();
            if ($cDate->lt($cFrom)) return false;
        }
        if ($to) {
            $cTo = \Carbon\Carbon::parse($to)->endOfDay();
            if ($cDate->gt($cTo)) return false;
        }
        return true;
    }

    private function calculateStorageUsed($orgUin, $startDate = null, $endDate = null): float
    {
        if (!$orgUin) return 0.0;

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

        return round($attachMb + $dbMb, 2);
    }

    /**
     * Check if an active or historical refund request exists for this org
     */
    protected function checkExistingRefundRequest($orgaUin, $plan = null)
    {
        try {
            $plan = $plan ?? $this->activeSub;
            $planCreatedAt = $plan ? (!empty($plan->Bkng_Dt) ? $plan->Bkng_Dt : (!empty($plan->CrOn) ? $plan->CrOn : null)) : null;

            $existingRequest = null;
            try {
                $q = DB::connection('partkers_subs')->table('tbl_tran_rfnd_requ')
                    ->where('Indv_Orga_UIN', $orgaUin);
                if ($plan && !empty($plan->New_Plan_UIN)) {
                    $q->where('Plan_UIN', $plan->New_Plan_UIN);
                }
                if ($planCreatedAt) {
                    $q->where('CrOn', '>=', \Carbon\Carbon::parse($planCreatedAt)->format('Y-m-d H:i:s'));
                }
                $existingRequest = $q->orderBy('CrOn', 'desc')->first();
            } catch (\Exception $ex) {
                try {
                    $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partakers_subs');
                    $q = DB::table("{$subsDb}.tbl_tran_rfnd_requ")
                        ->where('Indv_Orga_UIN', $orgaUin);
                    if ($plan && !empty($plan->New_Plan_UIN)) {
                        $q->where('Plan_UIN', $plan->New_Plan_UIN);
                    }
                    if ($planCreatedAt) {
                        $q->where('CrOn', '>=', \Carbon\Carbon::parse($planCreatedAt)->format('Y-m-d H:i:s'));
                    }
                    $existingRequest = $q->orderBy('CrOn', 'desc')->first();
                } catch (\Exception $ex2) {
                    try {
                        $q = DB::table('tbl_tran_rfnd_requ')
                            ->where('Indv_Orga_UIN', $orgaUin);
                        if ($plan && !empty($plan->New_Plan_UIN)) {
                            $q->where('Plan_UIN', $plan->New_Plan_UIN);
                        }
                        if ($planCreatedAt) {
                            $q->where('CrOn', '>=', \Carbon\Carbon::parse($planCreatedAt)->format('Y-m-d H:i:s'));
                        }
                        $existingRequest = $q->orderBy('CrOn', 'desc')->first();
                    } catch (\Exception $ex3) {
                        $existingRequest = null;
                    }
                }
            }

            if ($existingRequest) {
                $this->activeRefundRequest = (array) $existingRequest;
                $statusUin = (int) ($existingRequest->Stau_UIN ?? 100201);

                if ($statusUin === 100201) {
                    $this->hasPendingRefundRequest = true;
                } elseif ($statusUin === 100204) {
                    // Admin has approved the refund but payment hasn't been made yet.
                    $this->hasApprovedRefundRequestAwaitingPayment = true;
                } elseif ($statusUin === 100202) {
                    // Payment has been made — refund is complete.
                    $this->hasApprovedRefundRequest = true;
                } elseif ($statusUin === 100203) {
                    $this->hasDeniedRefundRequest = true;
                    // Refund denied → restore the user's access by force-refreshing
                    // the session quota snapshot. This ensures canAddEntry/canAddUser/
                    // canAddStorage return true again based on the existing plan.
                    $this->refreshQuotaSnapshot();
                }
            }
        } catch (\Exception $e) {
            \Log::info('Refund request table check skipped: ' . $e->getMessage());
        }
    }

    public function openRefundModal()
    {
        $this->refundReasonCategory = 'Not satisfied with features';
        $this->refundReasonDetails = '';
        $this->refundErrorMessage = '';
        $this->refundSuccessMessage = '';
        $this->showRefundModal = true;
    }

    public function closeRefundModal()
    {
        $this->showRefundModal = false;
    }

    public function submitRefundRequest()
    {
        $user = \App\Helpers\AuthHelper::getAuthenticatedUser();
        $orgaUin = session('selected_Orga_UIN');

        if (!$this->isEligibleForRefund) {
            $this->refundErrorMessage = 'The 7-day refund eligibility period has expired.';
            return;
        }

        if ($this->hasPendingRefundRequest) {
            $this->refundErrorMessage = 'A refund request is already pending for your organization.';
            return;
        }

        $purchDateRaw = isset($this->activeSub->CrOn) && $this->activeSub->CrOn 
            ? $this->activeSub->CrOn 
            : ($this->activeSub->Sbcr_Frst_Dt ?? now());

        $vtclUin = $this->activeSub->Vtcl_UIN ?? ($this->association->vert_uin ?? 112);

        $amountToRefund = 0.0;
        if (isset($this->activeSub->Recg_Amt) && (float)$this->activeSub->Recg_Amt > 0) {
            $amountToRefund = (float)$this->activeSub->Recg_Amt;
        } elseif (isset($this->activeSub->Ordr_Amt) && (float)$this->activeSub->Ordr_Amt > 0) {
            $amountToRefund = (float)$this->activeSub->Ordr_Amt;
        } elseif ($this->planAmountRaw > 0) {
            $amountToRefund = (float)$this->planAmountRaw;
        }

        // Fallback: Query actual wallet transaction if amount is still 0
        if ($amountToRefund <= 0 && $orgaUin) {
            try {
                $wRec = DB::connection('partkers_subs')->table('tbl_mast_sbcr_walt')
                    ->where('Orga_UIN', $orgaUin)
                    ->where('Vtcl_UIN', $vtclUin)
                    ->orderBy('Walt_UIN', 'desc')
                    ->first();
                if ($wRec && (float)($wRec->Recg_Amt ?? 0) > 0) {
                    $amountToRefund = (float) $wRec->Recg_Amt;
                }
            } catch (\Exception $exW) {
                try {
                    $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partkers_subs');
                    $wRec = DB::table("{$subsDb}.tbl_mast_sbcr_walt")
                        ->where('Orga_UIN', $orgaUin)
                        ->where('Vtcl_UIN', $vtclUin)
                        ->orderBy('Walt_UIN', 'desc')
                        ->first();
                    if ($wRec && (float)($wRec->Recg_Amt ?? 0) > 0) {
                        $amountToRefund = (float) $wRec->Recg_Amt;
                    }
                } catch (\Exception $exW2) {
                    try {
                        $wRec = DB::table('tbl_mast_sbcr_walt')
                            ->where('Orga_UIN', $orgaUin)
                            ->where('Vtcl_UIN', $vtclUin)
                            ->orderBy('Walt_UIN', 'desc')
                            ->first();
                        if ($wRec && (float)($wRec->Recg_Amt ?? 0) > 0) {
                            $amountToRefund = (float) $wRec->Recg_Amt;
                        }
                    } catch (\Exception $exW3) {}
                }
            }
        }

        $insertData = [
            'Indv_Orga_UIN'  => $orgaUin,
            'User_UIN'       => $user->User_UIN ?? 0,
            'Vert_UIN'       => $vtclUin,
            'Plan_UIN'       => $this->activeSub->New_Plan_UIN ?? 1016,
            'Ordr_UIN'       => $this->activeSub->Walt_UIN ?? null,
            'Ordr_Amt'       => $amountToRefund,
            'Rfnd_Bank_UIN'  => 0,
            'Rfnd_Amt'       => $amountToRefund,
            'Rfnd_Dt'        => now(),
            'Rfnd_Remk'      => substr($this->refundReasonCategory . ($this->refundReasonDetails ? ': ' . $this->refundReasonDetails : ''), 0, 200),
            'Prch_Dt'        => $purchDateRaw,
            'Rfnd_Catg_Rson' => $this->refundReasonCategory,
            'Rfnd_Desc_Rson' => $this->refundReasonDetails,
            'Stau_UIN'       => 100201, // Pending Approval
            'CrOn'           => now(),
        ];

        try {
            try {
                DB::connection('partkers_subs')->table('tbl_tran_rfnd_requ')->insert($insertData);
            } catch (\Exception $ex) {
                try {
                    $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partakers');
                    DB::table("{$subsDb}.tbl_tran_rfnd_requ")->insert($insertData);
                } catch (\Exception $ex2) {
                    DB::table('tbl_tran_rfnd_requ')->insert($insertData);
                }
            }

            $this->hasPendingRefundRequest = true;
            $this->showRefundModal = false;
            $this->refundSuccessMessage = 'Your refund request of ₹' . number_format($amountToRefund, 2) . ' has been submitted successfully and is pending admin approval.';
            $this->logActivity('Refund Request Submitted', 'User requested 7-day refund for plan amount ₹' . $amountToRefund);

            // Immediately set all allowed quotas to 0
            $this->userAlow = 0;
            $this->baseUsers = 0;
            $this->adnlUsers = 0;
            $this->bnftUsers = 0;

            $this->entyAlow = 0;
            $this->baseEntries = 0;
            $this->adnlEntries = 0;
            $this->bnftEntries = 0;
            $this->crPtEntries = 0;

            $this->spceAlow = 0;
            $this->baseStorage = 0;
            $this->adnlStorage = 0;
            $this->bnftStorage = 0;
            $this->crPtStorage = 0;

            $this->refreshQuotaSnapshot();
        } catch (\Exception $e) {
            \Log::error('Error submitting refund request: ' . $e->getMessage());
            $this->refundErrorMessage = 'Failed to submit refund request: ' . $e->getMessage();
        }
    }

    public function increment($property, $step = 1)
    {
        $this->$property += $step;
    }

    public function decrement($property, $step = 1)
    {
        $this->$property = max(0, $this->$property - $step);
    }

    public function proceedToPurchase()
    {
        // 1. Fetch active subscription or fallback plan ID
        $planId = $this->activeSub 
            ? ($this->activeSub->New_Plan_UIN ?? null) 
            : null;

        if (!$planId) {
            try {
                $planId = DB::connection('partkers_subs')->table('tbl_mast_sbcr_plan')
                    ->where('Vtcl_Name_UIN', 112)
                    ->value('Plan_ID_UIN');
            } catch (\Exception $e) {
                try {
                    $planId = DB::table('u975025641_subscription.tbl_mast_sbcr_plan')
                        ->where('Vtcl_Name_UIN', 112)
                        ->value('Plan_ID_UIN');
                } catch (\Exception $ex) {
                    \Log::warning('Error fetching default plan for vertical 112: ' . $ex->getMessage());
                }
            }
        }

        // Fallback to 1016 if no plan is found
        $planId = $planId ?? 1016;

        // Ensure user profile & org details are populated
        if (empty($this->email) || empty($this->firstName) || empty($this->orgName)) {
            $user = \App\Helpers\AuthHelper::getAuthenticatedUser();
            if ($user) {
                $profile = DB::table('admn_user_logi_mast')->where('User_UIN', $user->User_UIN)->first();
                if ($profile) {
                    $this->email = $this->email ?: ($profile->Prmy_Emai ?? '');
                    $this->mobile = $this->mobile ?: ($profile->Prmy_MoNo ?? '');
                    $fullName = trim($profile->User_Name ?? '');
                    $nameParts = explode(' ', $fullName, 2);
                    $this->firstName = $this->firstName ?: ($nameParts[0] ?? '');
                    $this->lastName = $this->lastName ?: ($nameParts[1] ?? '');
                }
            }
            $org = \App\Helpers\AuthHelper::getSelectedOrganization();
            if ($org) {
                $this->orgName = $this->orgName ?: $org->name;
                $orgDetails = DB::table('admn_orga_mast')->where('Orga_UIN', $org->Orga_UIN)->first();
                if ($orgDetails) {
                    $this->orgWebsite = $this->orgWebsite ?: ($orgDetails->Web_Addr ?? '');
                    $this->orgTypeUin = $this->orgTypeUin ?: ($orgDetails->Orga_Type_UIN ?? null);
                    $this->stateUin = $this->stateUin ?: ($orgDetails->Stat_UIN ?? null);
                    $this->zipCode = $this->zipCode ?: ((string)($orgDetails->pin_UIN ?? ''));
                    $this->address = $this->address ?: ($orgDetails->Addr_Line1 ?? '');
                }
            }
        }

        // 2. Construct checkout URL
        $pricingUrl = config('services.pricing.url', 'https://subscription.partakers.in');
        $checkoutUrl = rtrim($pricingUrl, '/') . '/order-summary/' . $planId;

        // 3. Append comprehensive query parameters for seamless existing user checkout
        $queryParams = http_build_query(array_filter([
            'email'          => $this->email,
            'firstName'      => $this->firstName,
            'lastName'       => $this->lastName,
            'mobile'         => $this->mobile,
            'users'          => $this->additionalUsers,
            'storage'        => $this->additionalStorage,
            'entries'        => $this->additionalEntries,
            'orgName'        => $this->orgName ?: null,
            'orgWebsite'     => $this->orgWebsite ?: null,
            'orgTypeUin'     => $this->orgTypeUin ?: null,
            'stateUin'       => $this->stateUin ?: null,
            'zipCode'        => $this->zipCode ?: null,
            'address'        => $this->address ?: null,
            'sourceVertical' => 'contacts',
        ], fn($value) => !is_null($value) && $value !== ''));

        $this->logActivity('Add-ons Purchase Redirect', 'User redirected to checkout for plan ID ' . $planId . ' with selected add-ons and profile parameters.');

        return redirect()->away($checkoutUrl . '?' . $queryParams);
    }

    public function render()
    {
        return view('livewire.settings.subscription');
    }
}
