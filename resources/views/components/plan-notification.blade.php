@php
    $orgaUin = session('selected_Orga_UIN');
    if (!$orgaUin) {
        return;
    }

    // Check admin rights for action buttons
    $isAdmin = false;
    try {
        $isAdmin = \App\Traits\ManagesSettings::isAdmin();
    } catch (\Exception $e) {
        // Fallback if method not accessible
        $isAdmin = auth()->check();
    }

    // Fetch active plan validation directly or from session fallback
    $activePlan = null;
    $queuedPlan = null;
    try {
        $helper = new class {
            use \App\Traits\ValidatesSubscriptionLimits;
            public function getPlan($org) {
                return $this->getActivePlanValidation($org, true);
            }
        };
        $activePlan = $helper->getPlan($orgaUin);

        $queuedPlan = \Illuminate\Support\Facades\DB::table('tbl_tran_sbcr_plan_vldt')
            ->where('Indv_Orga_UIN', $orgaUin)
            ->where('Vtcl_UIN', 112)
            ->where('Stau_UIN', 100202)
            ->when($activePlan, fn($q) => $q->where('Sbcr_Plan_Vldt_UIN', '!=', $activePlan->Sbcr_Plan_Vldt_UIN))
            ->orderBy('Sbcr_Plan_Vldt_UIN', 'asc')
            ->first();
    } catch (\Exception $e) {
        // DB table unreachable or error
    }

    // Determine allowances and usage (prefer live DB calculations if available, else session)
    if ($activePlan) {
        $entryAllowed = (int) ($activePlan->Sbcr_Ntry ?? 0) + (int) ($activePlan->Adnl_Ntry ?? 0) + (int) ($activePlan->Bnft_Ntry ?? 0) + (int) ($activePlan->Cr_Pont_Ntry ?? 0);
        $entryUsed    = (int) ($activePlan->Ulzn_Ntry ?? 0);
        
        $userAllowed  = (int) ($activePlan->Sbcr_User ?? 0) + (int) ($activePlan->Adnl_User ?? 0) + (int) ($activePlan->Bnft_User ?? 0);
        // Live headcount (admin + active invited users) — Ulzn_User never
        // reflects the admin's own seat, see ValidatesSubscriptionLimits::getActiveOrgUserCount().
        $userUsed     = (int) \Illuminate\Support\Facades\DB::table('tbl_tran_user_assc')
            ->where('Orga_UIN', $orgaUin)
            ->where('vert_uin', 112)
            ->where('Stau_UIN', 100201)
            ->count();

        $storageAllowed = (float) ($activePlan->Sbcr_Spce ?? 0) + (float) ($activePlan->Adnl_Spce ?? 0) + (float) ($activePlan->Bnft_Spce ?? 0) + (float) ($activePlan->Cr_Pont_Spce ?? 0);
        $storageUsed    = (float) ($activePlan->Ulzn_Spce ?? 0);
    } else {
        $entryAllowed   = (int) session('quota_entry_allowed', 0);
        $entryUsed      = (int) session('quota_entry_used', 0);
        $userAllowed    = (int) session('quota_user_allowed', 0);
        $userUsed       = (int) session('quota_user_used', 0);
        $storageAllowed = (float) session('quota_storage_allowed', 0);
        $storageUsed    = (float) session('quota_storage_used', 0);
    }

    // Expiry evaluation
    $isExpired = false;
    $daysToExpiry = null;
    $expiryText = '-';
    if ($activePlan && !empty($activePlan->Sbcr_Last_Dt)) {
        if (isset($activePlan->is_pending_expired) && $activePlan->is_pending_expired) {
            $isExpired = true;
        } else {
            $lastDate = \Carbon\Carbon::parse($activePlan->Sbcr_Last_Dt)->endOfDay();
            $expiryText = $lastDate->format('jS M Y');
            if ($lastDate->isPast()) {
                $isExpired = true;
            } else {
                $daysToExpiry = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($lastDate->startOfDay(), false);
            }
        }
    } else {
        // If no active plan at all, treat as restricted
        $isExpired = true;
    }

    // Determine Alert Severity & Content
    $alertType = null; // 'critical_expired', 'critical_quota', 'warning_expiry', 'warning_quota', null
    $alertTitle = '';
    $alertMessage = '';
    $badgeText = '';

    if ($isExpired) {
        $alertType = 'critical_expired';
        $alertTitle = "Your organization's subscription plan has expired. Please select a new plan to continue.";
        $alertMessage = '';
        $badgeText = '';
    } elseif ($entryAllowed > 0 && $entryUsed >= $entryAllowed) {
        $alertType = 'critical_quota';
        $alertTitle = 'Entry Quota Exceeded (' . number_format($entryUsed) . ' / ' . number_format($entryAllowed) . ')';
        $alertMessage = 'You have reached or exceeded the maximum contacts entry limit allowed in your active plan. Additional entries may be blocked or billed as overage.';
        $badgeText = 'LIMIT REACHED';
    } elseif ($storageAllowed > 0 && $storageUsed >= $storageAllowed) {
        $alertType = 'critical_quota';
        $alertTitle = 'Storage Quota Exceeded (' . $storageUsed . ' / ' . $storageAllowed . ' MB)';
        $alertMessage = 'Your cloud storage quota is completely full. New document uploads will be rejected until additional storage is acquired.';
        $badgeText = 'STORAGE FULL';
    } elseif ($daysToExpiry !== null && $daysToExpiry <= 7) {
        $alertType = 'warning_expiry';
        $alertTitle = 'Plan Expiring Soon (' . ($daysToExpiry == 0 ? 'Today' : $daysToExpiry . ' days left') . ')';
        $alertMessage = 'Your active subscription expires on ' . $expiryText . '.' . ($queuedPlan ? ' An advance renewal plan is queued to start automatically.' : ' Please renew soon to ensure uninterrupted operation.');
        $badgeText = 'EXPIRING SOON';
    } elseif ($entryAllowed > 0 && ($entryUsed / $entryAllowed) >= 0.85) {
        $alertType = 'warning_quota';
        $percent = round(($entryUsed / $entryAllowed) * 100);
        $alertTitle = 'Approaching Contact Limit (' . $percent . '% consumed)';
        $alertMessage = 'You have used ' . number_format($entryUsed) . ' of ' . number_format($entryAllowed) . ' allowed entries in your plan. Consider adding extra entries or upgrading.';
        $badgeText = $percent . '% USED';
    } elseif ($storageAllowed > 0 && ($storageUsed / $storageAllowed) >= 0.85) {
        $alertType = 'warning_quota';
        $percent = round(($storageUsed / $storageAllowed) * 100);
        $alertTitle = 'Approaching Storage Limit (' . $percent . '% consumed)';
        $alertMessage = 'You have consumed ' . $storageUsed . ' MB of ' . $storageAllowed . ' MB available document storage.';
        $badgeText = $percent . '% USED';
    }

    // URL to order page
    $planId = null;
    try {
        $planId = \Illuminate\Support\Facades\DB::connection('partkers_subs')->table('tbl_mast_sbcr_plan')
            ->where('Vtcl_Name_UIN', 112)
            ->value('Plan_ID_UIN');
    } catch (\Exception $e) {}
    
    if (!$planId) {
        $planId = 1016;
    }
    
    $pricingUrl = config('services.pricing.url', 'https://subscription.partakers.in');
    
    // Construct comprehensive query parameters for seamless existing user checkout
    $queryParams = [];
    $queryParams['sourceVertical'] = 'contacts';
    
    $user = \App\Helpers\AuthHelper::getAuthenticatedUser();
    if ($user) {
        $profile = \Illuminate\Support\Facades\DB::table('admn_user_logi_mast')->where('User_UIN', $user->User_UIN)->first();
        if ($profile) {
            $queryParams['email'] = $profile->Prmy_Emai ?? '';
            $queryParams['mobile'] = $profile->Prmy_MoNo ?? '';
            $fullName = trim($profile->User_Name ?? '');
            $nameParts = explode(' ', $fullName, 2);
            $queryParams['firstName'] = $nameParts[0] ?? '';
            $queryParams['lastName'] = $nameParts[1] ?? '';
        }
    }
    
    $org = \App\Helpers\AuthHelper::getSelectedOrganization();
    if ($org && isset($org->Orga_UIN)) {
        $queryParams['orgName'] = $org->name;
        $orgDetails = \Illuminate\Support\Facades\DB::table('admn_orga_mast')->where('Orga_UIN', $org->Orga_UIN)->first();
        if ($orgDetails) {
            $queryParams['orgWebsite'] = $orgDetails->Web_Addr ?? '';
            $queryParams['orgTypeUin'] = $orgDetails->Orga_Type_UIN ?? null;
            $queryParams['stateUin'] = $orgDetails->Stat_UIN ?? null;
            $queryParams['zipCode'] = $orgDetails->pin_UIN ?? '';
        }
    }
    
    $queryString = http_build_query(array_filter($queryParams, fn($value) => !is_null($value) && $value !== ''));
    $subscriptionUrl = rtrim($pricingUrl, '/') . '/order-summary/' . $planId . '?' . $queryString;
@endphp

@if($alertType)
    <div x-data="{ showNotice: true }" x-show="showNotice" x-transition:leave="transition ease-in duration-200 opacity-0 transform -translate-y-1" class="w-full px-4 mb-2">
        @if(in_array($alertType, ['critical_expired', 'critical_quota']))
            {{-- CRITICAL ALERT BANNER (RED / ROSE) --}}
            <div class="bg-gradient-to-r from-red-950/80 via-rose-900/60 to-red-950/80 border border-red-500/50 rounded-lg p-3 sm:p-4 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-white backdrop-blur-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0 text-red-400">
                        <i class="bi bi-exclamation-octagon-fill text-lg animate-pulse"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-sm text-red-200">{{ $alertTitle }}</span>
                            @if($badgeText)
                                <span class="px-2 py-0.5 bg-red-600/50 text-red-200 border border-red-500/50 rounded text-[10px] font-bold tracking-wider uppercase">{{ $badgeText }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-red-300/90 mt-0.5 leading-relaxed truncate sm:whitespace-normal">{{ $alertMessage }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto justify-end mt-1 sm:mt-0">
                    @if($isAdmin)
                        <a href="{{ $subscriptionUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-medium text-xs rounded-md shadow transition-all duration-150 whitespace-nowrap">
                            <i class="bi bi-shield-plus text-xs"></i>
                            <span>{{ $alertType === 'critical_expired' ? 'Buy Plan' : 'Upgrade Plan / Add-ons' }}</span>
                        </a>
                    @else
                        <span class="text-[11px] text-gray-400 italic">Contact admin to upgrade</span>
                    @endif
                    <button @click="showNotice = false" title="Dismiss Notice" class="p-1.5 text-red-300/70 hover:text-white rounded hover:bg-red-800/40 transition-colors">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

        @else
            {{-- WARNING / APPROACHING ALERT BANNER (AMBER / YELLOW) --}}
            <div class="bg-gradient-to-r from-amber-950/80 via-yellow-900/50 to-amber-950/80 border border-amber-500/50 rounded-lg p-3 sm:p-4 shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-white backdrop-blur-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0 text-amber-400">
                        <i class="bi bi-exclamation-triangle-fill text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-sm text-amber-200">{{ $alertTitle }}</span>
                            @if($badgeText)
                                <span class="px-2 py-0.5 bg-amber-600/50 text-amber-100 border border-amber-500/50 rounded text-[10px] font-bold tracking-wider uppercase">{{ $badgeText }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-amber-300/90 mt-0.5 leading-relaxed truncate sm:whitespace-normal">{{ $alertMessage }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0 w-full sm:w-auto justify-end mt-1 sm:mt-0">
                    @if($isAdmin)
                        <a href="{{ $subscriptionUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-500 active:bg-amber-700 text-white font-medium text-xs rounded-md shadow transition-all duration-150 whitespace-nowrap">
                            <i class="bi bi-lightning-charge text-xs"></i>
                            <span>Manage Plan</span>
                        </a>
                    @endif
                    <button @click="showNotice = false" title="Dismiss Notice" class="p-1.5 text-amber-300/70 hover:text-white rounded hover:bg-amber-800/40 transition-colors">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
@endif
