<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait ValidatesSubscriptionLimits
{
     protected int $verticalUin = 112;

    // -----------------------------------------------------------------------
    // QUOTA SNAPSHOT — called ONCE per login/org-selection, cached in session
    // -----------------------------------------------------------------------

    /**
     * Build and store a quota snapshot for the given org into the session.
     *
     * Called by OrganizationContext middleware when:
     *   - No snapshot exists yet, OR
     *   - The org in session has changed (user switched org)
     *
     * Overage is intentional and flexible:
     *   - If Ulzn_Ntry < allowed  → quota_can_add_entry = true
     *   - If Ulzn_Ntry >= allowed → quota_can_add_entry = false
     *   - Ulzn_Ntry is still incremented on every entry (even past the limit)
     *   - The next *different* user's login re-checks and may block them
     */
    public function buildQuotaSnapshot(int $orgaUin): void
    {
        $plan = $this->getActivePlanValidation($orgaUin);

        if (!$plan) {
            // No active plan → block everything
            session([
                'quota_snapshot_org'       => $orgaUin,
                'quota_can_add_entry'      => false,
                'quota_can_add_user'       => false,
                'quota_can_add_storage'    => false,
                'quota_entry_overage'      => 0,
                'quota_entry_allowed'      => 0,
                'quota_entry_used'         => 0,
                'quota_user_allowed'       => 0,
                'quota_user_used'          => 0,
                'quota_storage_allowed'    => 0,
                'quota_storage_used'       => 0,
            ]);
            return;
        }

        // ── Entries ──────────────────────────────────────────────────────
        $entryAllowed = (int) ($plan->Sbcr_Ntry     ?? 0)
                      + (int) ($plan->Adnl_Ntry     ?? 0)
                      + (int) ($plan->Bnft_Ntry     ?? 0)
                      + (int) ($plan->Cr_Pont_Ntry  ?? 0);

        // Read entry count from Past Record logs — same formula as History page
        // so the subscription quota always matches what's shown in Past Record.
        $entryLogQuery = DB::table('tbl_mast_sbcr_log_book')
            ->where('Indv_Orga_Uin', $orgaUin)
            ->where('UOM', '!=', 'MB');
        $planStartDt = $plan->Plan_Strt_Dt ?? null;
        $planEndDt   = $plan->Sbcr_Last_Dt  ?? null;
        if ($planStartDt) {
            $entryLogQuery->where('CrOn', '>=', $planStartDt);
        }
        if ($planEndDt) {
            $entryLogQuery->where('CrOn', '<=', $planEndDt);
        }
        $entryUsed = (int) $entryLogQuery->sum('Cont');
        
        if (isset($plan->is_pending_expired) && $plan->is_pending_expired) {
            $entryAllowed = 0;
        }
        
        $entryOverage = max(0, $entryUsed - $entryAllowed);

        // ── Users ─────────────────────────────────────────────────────────
        $userAllowed  = (int) ($plan->Sbcr_User     ?? 0)
                      + (int) ($plan->Adnl_User     ?? 0)
                      + (int) ($plan->Bnft_User     ?? 0);
        $userUsed     = (int) ($plan->Ulzn_User     ?? 0);

        if (isset($plan->is_pending_expired) && $plan->is_pending_expired) {
            $userAllowed = 0;
        }

        // ── Storage (MB) ──────────────────────────────────────────────────
        // Calculate dynamic storage and sync it to DB and plan object
        $this->calculateAndSyncStorage($orgaUin, $plan);

        $storageAllowed = (float) ($plan->Sbcr_Spce    ?? 0)
                        + (float) ($plan->Adnl_Spce    ?? 0)
                        + (float) ($plan->Bnft_Spce    ?? 0)
                        + (float) ($plan->Cr_Pont_Spce ?? 0);
        $storageUsed    = (float) ($plan->Ulzn_Spce    ?? 0);

        if (isset($plan->is_pending_expired) && $plan->is_pending_expired) {
            $storageAllowed = 0;
        }

        session([
            // Which org this snapshot belongs to
            'quota_snapshot_org'    => $orgaUin,
            'quota_is_pending_expired' => isset($plan->is_pending_expired) ? $plan->is_pending_expired : false,

            // Entries
            'quota_can_add_entry'   => $entryUsed < $entryAllowed,
            'quota_entry_allowed'   => $entryAllowed,
            'quota_entry_used'      => $entryUsed,
            'quota_entry_overage'   => $entryOverage,

            // Users — strict live check at login; stays cached for session
            'quota_can_add_user'    => $userUsed < $userAllowed,
            'quota_user_allowed'    => $userAllowed,
            'quota_user_used'       => $userUsed,

            // Storage — cached at login like entries
            'quota_can_add_storage' => $storageUsed < $storageAllowed,
            'quota_storage_allowed' => $storageAllowed,
            'quota_storage_used'    => $storageUsed,
        ]);
    }

    /**
     * Force-refresh the quota snapshot from DB immediately.
     * Call this after a plan upgrade or additional purchase is detected.
     */
    public function refreshQuotaSnapshot(): void
    {
        $orgaUin = session('selected_Orga_UIN');
        if ($orgaUin) {
            $this->buildQuotaSnapshot((int) $orgaUin);
        }
    }

    /**
     * Refresh snapshot only if it is stale (org changed or not yet set).
     * This is the method called by OrganizationContext middleware on every request
     * — it is a no-op when the snapshot is already fresh for the current org.
     */
    public function refreshQuotaSnapshotIfNeeded(int $orgaUin): void
    {
        if (
            session('quota_snapshot_org') !== $orgaUin
            || !session('quota_can_add_entry')
            || session('quota_entry_allowed', 0) <= 0
            || session('quota_is_pending_expired', false)
        ) {
            $this->buildQuotaSnapshot($orgaUin);
        }
    }

    // -----------------------------------------------------------------------
    // QUOTA CHECKS — read from session, fall back to live DB if missing
    // -----------------------------------------------------------------------

    /**
     * Check if the org can add a new entry.
     *
     * TRUE  → at login time the org had quota available; user may freely add
     *         entries during the session (even beyond the limit).
     * FALSE → at login time the org was already at/over limit;
     *         this user is blocked until the plan is upgraded.
     */
    public function canAddEntry(): bool
    {
        // Use cached snapshot if available
        if (session()->has('quota_can_add_entry')) {
            return (bool) session('quota_can_add_entry');
        }

        // Fallback: live DB check (e.g. first request before middleware ran)
        $plan = $this->getActivePlanValidation();
        if (!$plan) return false;

        $allowed = (int) ($plan->Sbcr_Ntry    ?? 0)
                 + (int) ($plan->Adnl_Ntry    ?? 0)
                 + (int) ($plan->Bnft_Ntry    ?? 0)
                 + (int) ($plan->Cr_Pont_Ntry ?? 0);

        // Use Past Record log sum — same formula as History & Subscription pages
        $orgaUin     = session('selected_Orga_UIN') ?? $plan->Indv_Orga_UIN ?? null;
        $planStart   = $plan->Plan_Strt_Dt ?? null;
        $planEnd     = $plan->Sbcr_Last_Dt  ?? null;
        $logQuery    = DB::table('tbl_mast_sbcr_log_book')
            ->where('Indv_Orga_Uin', $orgaUin)
            ->where('UOM', '!=', 'MB');
        if ($planStart) { $logQuery->where('CrOn', '>=', $planStart); }
        if ($planEnd)   { $logQuery->where('CrOn', '<=', $planEnd);   }
        $entryUsed = (int) $logQuery->sum('Cont');

        return $entryUsed < $allowed;
    }

    /**
     * Check if the org can add a new user.
     * Uses session snapshot (set at login); stays cached for the session.
     */
    public function canAddUser(): bool
    {
        if (session()->has('quota_can_add_user')) {
            return (bool) session('quota_can_add_user');
        }

        // Fallback: live DB check
        $plan = $this->getActivePlanValidation();
        if (!$plan) return false;

        $allowed = (int) ($plan->Sbcr_User ?? 0)
                 + (int) ($plan->Adnl_User ?? 0)
                 + (int) ($plan->Bnft_User ?? 0);

        return ((int) ($plan->Ulzn_User ?? 0)) < $allowed;
    }

    /**
     * Check if the org can add storage of the given size (in MB).
     * Uses session snapshot (set at login).
     *
     * Note: For storage we still check the requested size against remaining
     * headroom, because a single upload can be very large.
     */
    public function canAddStorage(float $sizeInMB): bool
    {
        if (session()->has('quota_can_add_storage')) {
            if (!session('quota_can_add_storage')) {
                return false;
            }
            // Even if overall storage is ok, reject if this single file
            // would push used over allowed
            $allowed = (float) session('quota_storage_allowed', 0);
            $used    = (float) session('quota_storage_used', 0);
            return ($used + $sizeInMB) <= $allowed;
        }

        // Fallback: live DB check
        $plan = $this->getActivePlanValidation();
        if (!$plan) return false;

        $allowed = (float) ($plan->Sbcr_Spce    ?? 0)
                 + (float) ($plan->Adnl_Spce    ?? 0)
                 + (float) ($plan->Bnft_Spce    ?? 0)
                 + (float) ($plan->Cr_Pont_Spce ?? 0);

        return ((float) ($plan->Ulzn_Spce ?? 0) + $sizeInMB) <= $allowed;
    }

    /**
     * Return a human-readable message about the org's entry quota status.
     * Useful for showing blocked users WHY they cannot add entries.
     */
    public function getEntryQuotaMessage(): string
    {
        $allowed  = (int) session('quota_entry_allowed', 0);
        $used     = (int) session('quota_entry_used', 0);
        $overage  = (int) session('quota_entry_overage', 0);

        if ($overage > 0) {
            return "Your organization has used {$used} of {$allowed} allowed entries "
                 . "({$overage} over limit). Please upgrade your plan or purchase "
                 . "additional entries to continue adding records.";
        }

        return "Your organization has reached the entry limit ({$allowed} entries). "
             . "Please upgrade your plan or purchase additional entries.";
    }

    // -----------------------------------------------------------------------
    // UTILIZATION TRACKING — always runs, regardless of session quota state
    // -----------------------------------------------------------------------

    /**
     * Get the active plan validation record (live DB read).
     */
    protected function getActivePlanValidation($orgaUin = null, $ignoreRefunds = false)
    {
        $orgaUin = $orgaUin ?? session('selected_Orga_UIN');
        if (!$orgaUin) return null;

        // 1. LIVE SYNC WITH CENTRAL DASHBOARD (tbl_mast_sbcr_sbrd)
        try {
            $latestSbrd = null;
            try {
                $latestSbrd = DB::connection('partkers_subs')->table('tbl_mast_sbcr_sbrd')
                    ->where('Orga_UIN', $orgaUin)
                    ->orderBy('Sbrd_UIN', 'desc')
                    ->first();
            } catch (\Exception $e1) {
                try {
                    $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partkers_subs');
                    $latestSbrd = DB::table("{$subsDb}.tbl_mast_sbcr_sbrd")
                        ->where('Orga_UIN', $orgaUin)
                        ->orderBy('Sbrd_UIN', 'desc')
                        ->first();
                } catch (\Exception $e2) {
                    try {
                        $subsDb2 = env('DB_DATABASE_SUBS', 'u975025641_partakers');
                        $latestSbrd = DB::table("{$subsDb2}.tbl_mast_sbcr_sbrd")
                            ->where('Orga_UIN', $orgaUin)
                            ->orderBy('Sbrd_UIN', 'desc')
                            ->first();
                    } catch (\Exception $e3) {
                        try {
                            $latestSbrd = DB::table('tbl_mast_sbcr_sbrd')
                                ->where('Orga_UIN', $orgaUin)
                                ->orderBy('Sbrd_UIN', 'desc')
                                ->first();
                        } catch (\Exception $e4) {}
                    }
                }
            }

            if ($latestSbrd && in_array((int)$latestSbrd->Stau_UIN, [100201, 100202, 100204])) {
                $matchingVldt = DB::table('tbl_tran_sbcr_plan_vldt')
                    ->where('Indv_Orga_UIN', $orgaUin)
                    ->where('Vtcl_UIN', $this->verticalUin)
                    ->where('New_Plan_UIN', $latestSbrd->Plan_UIN)
                    ->orderBy('Sbcr_Plan_Vldt_UIN', 'desc')
                    ->first();

                $sbrdStart = !empty($latestSbrd->Strt_Date) ? $latestSbrd->Strt_Date : Carbon::now()->format('Y-m-d');
                $sbrdEnd = !empty($latestSbrd->End_Date) ? Carbon::parse($latestSbrd->End_Date)->endOfDay()->format('Y-m-d H:i:s') : Carbon::parse($sbrdStart)->addDays(365)->endOfDay()->format('Y-m-d H:i:s');

                if ($matchingVldt) {
                    if ((int)$matchingVldt->Stau_UIN !== (int)$latestSbrd->Stau_UIN || $matchingVldt->Plan_Strt_Dt !== $sbrdStart || $matchingVldt->Sbcr_Last_Dt !== $sbrdEnd) {
                        DB::table('tbl_tran_sbcr_plan_vldt')
                            ->where('Sbcr_Plan_Vldt_UIN', $matchingVldt->Sbcr_Plan_Vldt_UIN)
                            ->update([
                                'Stau_UIN'     => (int)$latestSbrd->Stau_UIN,
                                'Plan_Strt_Dt' => $sbrdStart,
                                'Sbcr_Last_Dt' => $sbrdEnd,
                            ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Live sync exception fallback
        }

        // Fetch currently active or valid plan (prefer plan that is currently not expired)
        $plan = DB::table('tbl_tran_sbcr_plan_vldt')
            ->where('Indv_Orga_UIN', $orgaUin)
            ->where('Vtcl_UIN', $this->verticalUin)
            ->whereIn('Stau_UIN', [100201, 100204])
            ->where('Sbcr_Last_Dt', '>=', Carbon::now()->startOfDay()->format('Y-m-d H:i:s'))
            ->orderBy('Sbcr_Plan_Vldt_UIN', 'desc')
            ->first();

        if (!$plan) {
            $plan = DB::table('tbl_tran_sbcr_plan_vldt')
                ->where('Indv_Orga_UIN', $orgaUin)
                ->where('Vtcl_UIN', $this->verticalUin)
                ->whereIn('Stau_UIN', [100201, 100202, 100204])
                ->orderBy('Sbcr_Plan_Vldt_UIN', 'desc')
                ->first();
        }

        // Automatic Rollover for Expired Plans OR Missing Active Plan
        if (!$plan || (!empty($plan->Sbcr_Last_Dt) && Carbon::parse($plan->Sbcr_Last_Dt)->isPast())) {
            $queuedPlan = DB::table('tbl_tran_sbcr_plan_vldt')
                ->where('Indv_Orga_UIN', $orgaUin)
                ->where('Vtcl_UIN', $this->verticalUin)
                ->whereIn('Stau_UIN', [100201, 100202, 100204]) // Queued / Advance / Active Plan
                ->when($plan, fn($q) => $q->where('Sbcr_Plan_Vldt_UIN', '!=', $plan->Sbcr_Plan_Vldt_UIN))
                ->orderBy('Sbcr_Plan_Vldt_UIN', 'desc')
                ->first();

            if ($queuedPlan) {
                // Expire old plan if it exists and is past
                if ($plan && !empty($plan->Sbcr_Last_Dt) && Carbon::parse($plan->Sbcr_Last_Dt)->isPast()) {
                    DB::table('tbl_tran_sbcr_plan_vldt')
                        ->where('Sbcr_Plan_Vldt_UIN', $plan->Sbcr_Plan_Vldt_UIN)
                        ->update(['Stau_UIN' => 100203]); // Expired
                }

                // A queued plan keeps the start date it was booked with (right after the previous
                // plan ended); fall back to the booking date only if none was stored.
                $newStart = !empty($queuedPlan->Plan_Strt_Dt) ? Carbon::parse($queuedPlan->Plan_Strt_Dt)->format('Y-m-d')
                    : (!empty($queuedPlan->Bkng_Dt) ? Carbon::parse($queuedPlan->Bkng_Dt)->format('Y-m-d') : Carbon::now()->format('Y-m-d'));
                $duraDays = 30;
                if (!empty($queuedPlan->Plan_Drtn) && preg_match('/(\d+)/', $queuedPlan->Plan_Drtn, $m)) {
                    $duraDays = (int) $m[1];
                }
                $newEnd = Carbon::parse($newStart)->addDays($duraDays)->endOfDay()->format('Y-m-d H:i:s');

                // Activate queued plan
                DB::table('tbl_tran_sbcr_plan_vldt')
                    ->where('Sbcr_Plan_Vldt_UIN', $queuedPlan->Sbcr_Plan_Vldt_UIN)
                    ->update([
                        'Plan_Strt_Dt' => $newStart,
                        'Sbcr_Last_Dt' => $newEnd,
                        'Stau_UIN'     => 100201, // Active
                    ]);

                $queuedPlan->Plan_Strt_Dt = $newStart;
                $queuedPlan->Sbcr_Last_Dt = $newEnd;
                $queuedPlan->Stau_UIN = 100201;
                $plan = $queuedPlan;
            }
        }

        // Ensure active plan has valid start and last date
        if ($plan && empty($plan->Sbcr_Last_Dt)) {
            $start = !empty($plan->Plan_Strt_Dt) ? Carbon::parse($plan->Plan_Strt_Dt) : (!empty($plan->Bkng_Dt) ? Carbon::parse($plan->Bkng_Dt) : Carbon::now());
            $duraDays = 30;
            if (!empty($plan->Plan_Drtn) && preg_match('/(\d+)/', $plan->Plan_Drtn, $m)) {
                $duraDays = (int) $m[1];
            }
            $newEnd = $start->copy()->addDays($duraDays)->endOfDay()->format('Y-m-d H:i:s');
            DB::table('tbl_tran_sbcr_plan_vldt')
                ->where('Sbcr_Plan_Vldt_UIN', $plan->Sbcr_Plan_Vldt_UIN)
                ->update([
                    'Plan_Strt_Dt' => $start->format('Y-m-d'),
                    'Sbcr_Last_Dt' => $newEnd,
                    'Stau_UIN'     => 100201,
                ]);
            $plan->Plan_Strt_Dt = $start->format('Y-m-d');
            $plan->Sbcr_Last_Dt = $newEnd;
            $plan->Stau_UIN = 100201;
        }

        // If plan has a future start date but there is NO other active plan currently running,
        // activate it immediately from its booking date / today!
        if ($plan && !empty($plan->Plan_Strt_Dt) && Carbon::parse($plan->Plan_Strt_Dt)->isFuture()) {
            $runningOther = DB::table('tbl_tran_sbcr_plan_vldt')
                ->where('Indv_Orga_UIN', $orgaUin)
                ->where('Vtcl_UIN', $this->verticalUin)
                ->where('Sbcr_Plan_Vldt_UIN', '!=', $plan->Sbcr_Plan_Vldt_UIN)
                ->whereIn('Stau_UIN', [100201, 100204])
                ->where('Sbcr_Last_Dt', '>=', Carbon::now()->format('Y-m-d H:i:s'))
                ->first();

            if (!$runningOther) {
                $newStart = !empty($plan->Bkng_Dt) ? Carbon::parse($plan->Bkng_Dt)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                $duraDays = 30;
                if (!empty($plan->Plan_Drtn) && preg_match('/(\d+)/', $plan->Plan_Drtn, $m)) {
                    $duraDays = (int) $m[1];
                }
                $newEnd = Carbon::parse($newStart)->addDays($duraDays)->endOfDay()->format('Y-m-d H:i:s');

                DB::table('tbl_tran_sbcr_plan_vldt')
                    ->where('Sbcr_Plan_Vldt_UIN', $plan->Sbcr_Plan_Vldt_UIN)
                    ->update([
                        'Plan_Strt_Dt' => $newStart,
                        'Sbcr_Last_Dt' => $newEnd,
                        'Stau_UIN'     => 100201, // Active
                    ]);

                $plan->Plan_Strt_Dt = $newStart;
                $plan->Sbcr_Last_Dt = $newEnd;
                $plan->Stau_UIN = 100201;
            }
        }

        // Check for Refund Requests that specifically target THIS active plan
        if ($plan && !$ignoreRefunds) {
            try {
                $planCreatedAt = !empty($plan->Bkng_Dt) ? $plan->Bkng_Dt : (!empty($plan->CrOn) ? $plan->CrOn : null);
                $refundRequest = null;

                try {
                    $rfQuery = DB::connection('partkers_subs')->table('tbl_tran_rfnd_requ')
                        ->where('Indv_Orga_UIN', $orgaUin);
                    if (!empty($plan->New_Plan_UIN)) {
                        $rfQuery->where('Plan_UIN', $plan->New_Plan_UIN);
                    }
                    if ($planCreatedAt) {
                        $rfQuery->where('CrOn', '>=', Carbon::parse($planCreatedAt)->format('Y-m-d H:i:s'));
                    }
                    $refundRequest = $rfQuery->orderBy('CrOn', 'desc')->first();
                } catch (\Exception $ex1) {
                    try {
                        $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partkers_subs');
                        $rfQuery = DB::table("{$subsDb}.tbl_tran_rfnd_requ")
                            ->where('Indv_Orga_UIN', $orgaUin);
                        if (!empty($plan->New_Plan_UIN)) {
                            $rfQuery->where('Plan_UIN', $plan->New_Plan_UIN);
                        }
                        if ($planCreatedAt) {
                            $rfQuery->where('CrOn', '>=', Carbon::parse($planCreatedAt)->format('Y-m-d H:i:s'));
                        }
                        $refundRequest = $rfQuery->orderBy('CrOn', 'desc')->first();
                    } catch (\Exception $ex2) {
                        try {
                            $rfQuery = DB::table('tbl_tran_rfnd_requ')
                                ->where('Indv_Orga_UIN', $orgaUin);
                            if (!empty($plan->New_Plan_UIN)) {
                                $rfQuery->where('Plan_UIN', $plan->New_Plan_UIN);
                            }
                            if ($planCreatedAt) {
                                $rfQuery->where('CrOn', '>=', Carbon::parse($planCreatedAt)->format('Y-m-d H:i:s'));
                            }
                            $refundRequest = $rfQuery->orderBy('CrOn', 'desc')->first();
                        } catch (\Exception $ex3) {
                            $refundRequest = null;
                        }
                    }
                }

                // Only pending or in-progress refund requests block active quota
                if ($refundRequest && in_array((int)$refundRequest->Stau_UIN, [100201, 100204])) {
                    $rfCreatedAt = !empty($refundRequest->CrOn) ? Carbon::parse($refundRequest->CrOn) : null;
                    $pCreatedAt = $planCreatedAt ? Carbon::parse($planCreatedAt) : null;
                    if (!$pCreatedAt || !$rfCreatedAt || $rfCreatedAt->gte($pCreatedAt)) {
                        // 100201 = Pending Request. We can ignore this for display purposes.
                        // 100204 = Approved Request. Plan is effectively dead, never ignore it!
                        if ($ignoreRefunds && (int)$refundRequest->Stau_UIN === 100201) {
                            // Let the plan through so the dashboard can show the "Pending" badge
                        } else {
                            return null;
                        }
                    }
                }
            } catch (\Exception $ex) {
                // Ignore refund check exception
            }
        }

        if ($plan) {
            // Live headcount — Ulzn_User (as stored/incremented) only ever tracks
            // additionally-invited users; the org admin's own seat is never
            // reflected in it (see Settings/User.php, which increments it only
            // in the "new invited user" branch, and the phase-1 purchase flow,
            // which always initializes it to 0). Recompute it here from the
            // actual membership table so it counts admin + invited users together
            // against the plan's total seat allowance.
            $plan->Ulzn_User = $this->getActiveOrgUserCount($orgaUin);

            // LIVE SYNC with Dashboard (tbl_mast_sbcr_sbrd) to get real-time status
            try {
                $subDashboard = null;
                try {
                    $subDashboard = DB::connection('partkers_subs')->table('tbl_mast_sbcr_sbrd')
                        ->where('Orga_UIN', $orgaUin)
                        ->where('Plan_UIN', $plan->New_Plan_UIN)
                        ->orderBy('Sbrd_UIN', 'desc')
                        ->first();
                } catch (\Exception $ex1) {
                    try {
                        $subsDb = env('DB_DATABASE_SUBS', 'u975025641_partakers');
                        $subDashboard = DB::table("{$subsDb}.tbl_mast_sbcr_sbrd")
                            ->where('Orga_UIN', $orgaUin)
                            ->where('Plan_UIN', $plan->New_Plan_UIN)
                            ->orderBy('Sbrd_UIN', 'desc')
                            ->first();
                    } catch (\Exception $ex2) {
                        $subDashboard = DB::table('tbl_mast_sbcr_sbrd')
                            ->where('Orga_UIN', $orgaUin)
                            ->where('Plan_UIN', $plan->New_Plan_UIN)
                            ->orderBy('Sbrd_UIN', 'desc')
                            ->first();
                    }
                }

                if ($subDashboard) {
                    $plan->Stau_UIN = $subDashboard->Stau_UIN;
                    
                    // Only Pending status (100202) has a 24-hour grace period. 100201 (Approved) and 100204 (Verified) are fully active!
                    if ((int) $subDashboard->Stau_UIN === 100202) {
                        $createdDate = $subDashboard->CrOn ? Carbon::parse($subDashboard->CrOn) : ($subDashboard->Strt_Date ? Carbon::parse($subDashboard->Strt_Date) : Carbon::now());
                        if ($createdDate->addHours(24)->isPast()) {
                            $plan->is_pending_expired = true;
                        }
                    }
                } else {
                    // Fallback to local plan validation status if dashboard is unreachable
                    if ((int) $plan->Stau_UIN === 100202) {
                        $createdDate = $plan->CrOn ? Carbon::parse($plan->CrOn) : ($plan->Plan_Strt_Dt ? Carbon::parse($plan->Plan_Strt_Dt) : Carbon::now());
                        if ($createdDate->addHours(24)->isPast()) {  // 24-hour grace
                            $plan->is_pending_expired = true;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore DB exception if table unreachable
            }

            // NOTE: We intentionally do NOT check tbl_tran_user_assc.valid_upto here.
            // The authoritative plan expiry is tbl_tran_sbcr_plan_vldt.Sbcr_Last_Dt.
            // valid_upto in user_assc is only an access hint and may be stale if
            // the admin approved a new plan without pulling on the contacts server.
            // Blocking based on it causes legitimate users to lose access.
        }

        return $plan;
    }

    /**
     * Live count of the org's total seats consumed against the plan's user
     * limit — this is admin + all active invited users together, since the
     * plan's Sbcr_User/Adnl_User/Bnft_User allowance is meant to cap total
     * org headcount, not just additionally-invited users.
     */
    protected function getActiveOrgUserCount(int $orgaUin): int
    {
        // Use COUNT(DISTINCT User_UIN) so that if a user has multiple rows in
        // tbl_tran_user_assc for the same org+vertical (e.g. one auto-created by
        // ensureUserOrganizationAssociations + one added via the user management panel),
        // they are only counted once against the plan's seat allowance.
        return (int) DB::table('tbl_tran_user_assc')
            ->where('Orga_UIN', $orgaUin)
            ->where('vert_uin', $this->verticalUin)
            ->where('Stau_UIN', 100201)
            ->distinct()
            ->count('User_UIN');
    }

    /**
     * Check if the active plan has expired or if no active plan is available.
     */
    public function isPlanExpired(): bool
    {
        $plan = $this->getActivePlanValidation();
        if (!$plan) {
            // No active plan found (whether expired or never purchased before) -> treat as restricted/expired
            return true;
        }
        
        if (isset($plan->is_pending_expired) && $plan->is_pending_expired) {
            return true;
        }
        
        return Carbon::parse($plan->Sbcr_Last_Dt)->endOfDay()->isPast();
    }

    /**
     * Increment a utilization field in the DB.
     * This ALWAYS runs — even if the user is over limit (tracking overage).
     * Supported fields: 'Ulzn_Ntry', 'Ulzn_Spce', 'Ulzn_User'
     */
    public function incrementUtilization(string $field, float $amount = 1): void
    {
        $orgaUin = session('selected_Orga_UIN');
        if (!$orgaUin) return;

        if (!in_array($field, ['Ulzn_Ntry', 'Ulzn_Spce', 'Ulzn_User'])) {
            return;
        }

        DB::table('tbl_tran_sbcr_plan_vldt')
            ->where('Indv_Orga_UIN', $orgaUin)
            ->where('Vtcl_UIN', $this->verticalUin)
            ->whereIn('Stau_UIN', [100201, 100202, 100204])
            ->increment($field, $amount);

        // Keep the session storage_used counter in sync so canAddStorage()
        // remains accurate within the same session
        if ($field === 'Ulzn_Spce' && session()->has('quota_storage_used')) {
            session(['quota_storage_used' => session('quota_storage_used') + $amount]);
        }
    }

    /**
     * Decrement a utilization field (e.g. when a file or entry is deleted).
     * Prevents the value from dropping below 0.
     */
    public function decrementUtilization(string $field, float $amount = 1): void
    {
        $orgaUin = session('selected_Orga_UIN');
        if (!$orgaUin) return;

        if (!in_array($field, ['Ulzn_Ntry', 'Ulzn_Spce', 'Ulzn_User'])) {
            return;
        }

        $plan = $this->getActivePlanValidation();
        if ($plan && isset($plan->$field)) {
            $current        = $plan->$field;
            $decrementAmount = min($current, $amount);

            if ($decrementAmount > 0) {
                DB::table('tbl_tran_sbcr_plan_vldt')
                    ->where('Sbcr_Plan_Vldt_UIN', $plan->Sbcr_Plan_Vldt_UIN)
                    ->decrement($field, $decrementAmount);

                // Keep session storage counter in sync
                if ($field === 'Ulzn_Spce' && session()->has('quota_storage_used')) {
                    session(['quota_storage_used' => max(0, session('quota_storage_used') - $decrementAmount)]);
                }
            }
        }
    }

    /**
     * Helper: convert bytes to MB
     */
    public function bytesToMB(int $bytes): float
    {
        return round($bytes / (1024 * 1024), 2);
    }

    /**
     * Calculate and sync total storage used (DB + Files) for the organization.
     */
    public function calculateAndSyncStorage(int $orgaUin, $plan): float
    {
        // 1. Calculate database usage (admn_user_mast for contacts)
        $multiplier = 1.5; // KB per entry for contacts
        $dbStorageKb = 0;
        try {
            $count = DB::connection('mysql_mystuff')
                ->table('admn_user_mast')
                ->where('Admn_Orga_Mast_UIN', $orgaUin)
                ->count();
            $dbStorageKb = $count * $multiplier;
        } catch (\Exception $e) {
            // Ignore if table doesn't exist
        }
        $dbStorageMb = $dbStorageKb / 1024;

        // 2. Calculate physical file storage
        $fileStorageBytes = 0;
        $directories = [
            storage_path('app/public/Attachment'),
            storage_path('app/public/attachments'),
            storage_path('app/public/Document'),
            storage_path('app/public/documents')
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    // Check if file belongs to this organization
                    if (str_starts_with($file, $orgaUin . '_')) {
                        $fileStorageBytes += filesize($dir . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }
        }
        $fileStorageMb = $fileStorageBytes / (1024 * 1024);

        // 3. Total Storage
        $totalMb = round($dbStorageMb + $fileStorageMb, 2);

        // 4. Update the active plan validation record
        if ($plan && isset($plan->Sbcr_Plan_Vldt_UIN)) {
            DB::table('tbl_tran_sbcr_plan_vldt')
                ->where('Sbcr_Plan_Vldt_UIN', $plan->Sbcr_Plan_Vldt_UIN)
                ->update(['Ulzn_Spce' => $totalMb]);
                
            $plan->Ulzn_Spce = $totalMb; // Update object reference so snapshot uses new value
        }

        return $totalMb;
    }
}
