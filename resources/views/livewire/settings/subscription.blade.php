<div class="p-6 text-slate-100">
    {{-- Header Section --}}
    <div class="flex items-center justify-between gap-3 mb-2">
        <div class="flex items-center gap-3">
            <i class="bi bi-credit-card-2-front text-blue-400 text-2xl flex-shrink-0"></i>
            <div>
                <h2 class="text-lg font-semibold text-white">Ongoing Subscription Plan</h2>
                <p class="text-xs text-gray-400">View your current subscription plan and purchase additional resource allowances</p>
            </div>
        </div>

        {{-- Recharge Plan CTA --}}
        <button type="button" wire:click="proceedToPurchase"
            class="flex-shrink-0 flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-sm font-bold shadow-lg shadow-blue-500/20 ring-1 ring-blue-400/40 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-blue-500/40 focus:outline-none focus:ring-2 focus:ring-blue-400 select-none whitespace-nowrap">
            <i class="bi bi-box-arrow-up-right text-sm"></i>
            <span>Recharge Plan</span>
        </button>
    </div>

    <div class="h-px bg-blue-500 my-5"></div>

    {{-- Top Overview Section: 3 equal-height cards in one row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">

        {{-- Column 1: Current Plan --}}
        <div
            class="bg-gradient-to-br from-slate-800/80 to-blue-950/40 border border-blue-500 rounded-2xl p-6 relative overflow-hidden shadow-lg shadow-blue-950/10 flex flex-col justify-between">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-blue-500/10 rounded-full blur-xl pointer-events-none">
            </div>

            {{-- Top Header: Vertical & Status Badge (Inactive is NOT displayed) --}}
            <div class="relative z-10 flex items-center justify-between">
                <span
                    class="px-2.5 py-1 text-[10px] font-bold tracking-wider text-blue-400 bg-blue-500/10 border border-blue-500/20 rounded-full uppercase">
                    {{ $verticalName }}
                </span>
                @if(strtolower($statusName) === 'rejected')
                    <span class="px-2.5 py-0.5 text-[10px] font-bold text-red-400 bg-red-500/10 border border-red-500/20 rounded-full flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                        Rejected
                    </span>
                @elseif(in_array(strtolower($statusName), ['pending', 'verified', 'approved']) && $activeSub)
                    <span class="px-2.5 py-0.5 text-[10px] font-bold text-blue-300 bg-blue-500/10 border border-blue-500/20 rounded-full flex items-center gap-1 uppercase">
                        {{ $statusName }}
                    </span>
                @endif
            </div>

            {{-- Plan Name (Active Plan text removed) --}}
            <div class="relative z-10 mt-3">
                <h3 class="text-xl font-bold text-white mt-0.5">{{ $planName }}</h3>
            </div>

            {{-- Plan Timeline Details Grid (Start Date & End Date with Blue border) --}}
            <div class="relative z-10 mt-3 flex-1 flex flex-col justify-center">
                <div class="bg-slate-900/40 border border-blue-500 rounded-xl p-3 flex flex-col gap-2">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="min-w-0">
                            <p class="text-[9px] text-gray-400 uppercase tracking-wider font-bold">Start Date</p>
                            <p class="text-xs font-bold text-white mt-0.5 truncate">{{ $startDate }}</p>
                        </div>
                        <div class="min-w-0 border-l border-blue-500 pl-2">
                            <p class="text-[9px] text-gray-400 uppercase tracking-wider font-bold">End Date</p>
                            <p class="text-xs font-bold text-blue-300 mt-0.5 truncate">{{ $endDate }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer: Duration (Inactive not displayed) --}}
            <div class="relative z-10 mt-3 pt-3 border-t border-blue-500 flex items-center justify-between text-xs">
                <div class="flex items-center gap-1.5">
                    <i class="bi bi-clock text-blue-400 text-xs"></i>
                    <span class="text-gray-400 text-[11px]">Duration:</span>
                    <span class="font-bold text-white text-[11px]">{{ $durationDays }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    @if(strtolower($statusName) === 'rejected')
                        <i class="bi bi-x-circle text-red-400 text-xs"></i>
                        <span class="font-bold text-red-400 text-[11px]">Rejected</span>
                    @endif
                </div>
            </div>

            {{-- 7-Day Refund Action / Status Row --}}
            <div class="relative z-10 mt-3 pt-2">
                @if($hasPendingRefundRequest)
                    <div class="p-2 bg-amber-500/10 border border-amber-500/30 rounded-xl text-center">
                        <span class="text-[11px] font-bold text-amber-400 flex items-center justify-center gap-1.5">
                            <i class="bi bi-hourglass-split"></i>
                            Refund Request Pending Admin Review
                        </span>
                    </div>
                @elseif($hasApprovedRefundRequestAwaitingPayment)
                    <div class="p-2 bg-sky-500/10 border border-sky-500/30 rounded-xl text-center">
                        <span class="text-[11px] font-bold text-sky-400 flex items-center justify-center gap-1.5">
                            <i class="bi bi-patch-check-fill"></i>
                            Refund Approved — Payment Processing
                        </span>
                    </div>
                @elseif($hasApprovedRefundRequest)
                    <div class="p-2 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-center">
                        <span class="text-[11px] font-bold text-emerald-400 flex items-center justify-center gap-1.5">
                            <i class="bi bi-check-circle-fill"></i>
                            Refund Approved & Paid
                        </span>
                    </div>
                @elseif($hasDeniedRefundRequest)
                    <div class="p-2 bg-rose-500/10 border border-rose-500/30 rounded-xl text-center">
                        <span class="text-[11px] font-bold text-rose-400 flex items-center justify-center gap-1.5">
                            <i class="bi bi-x-circle-fill"></i>
                            Refund Request Denied
                        </span>
                    </div>
                @elseif($isEligibleForRefund)
                    <button type="button" wire:click="openRefundModal"
                        class="w-full py-2 px-3 rounded-xl bg-gradient-to-r from-rose-500/20 to-pink-500/20 hover:from-rose-500/30 hover:to-pink-500/30 border border-rose-500/30 text-rose-300 text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 shadow-sm">
                        <i class="bi bi-arrow-counterclockwise text-rose-400"></i>
                        <span>Request Refund (7-Day Policy)</span>
                        <span
                            class="text-[10px] px-1.5 py-0.5 rounded bg-rose-500/30 text-rose-200 font-extrabold ml-auto">{{ $refundDaysLeft }}d
                            left</span>
                    </button>
                @endif
            </div>
        </div>
        {{-- end Column 1 --}}

        {{-- Column 2: Your Plan Includes (with outline & breakdown Like 1+2) --}}
        <div class="bg-slate-800/40 border border-blue-500 rounded-2xl p-6 shadow-md flex flex-col relative overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4 flex-shrink-0">
                <h4 class="text-xs font-bold text-blue-300 uppercase tracking-wider">
                    @if($hasPendingRefundRequest || $hasApprovedRefundRequestAwaitingPayment || $hasApprovedRefundRequest)
                        Plan Resource Limits
                    @else
                        Your Plan Includes
                    @endif
                </h4>
                <i class="bi bi-bar-chart-fill text-blue-400 text-sm"></i>
            </div>

            {{-- Pending/Approved Refund Overlay Notice --}}
            @if($hasPendingRefundRequest || $hasApprovedRefundRequestAwaitingPayment || $hasApprovedRefundRequest)
                <div class="flex flex-col items-center justify-center flex-1 py-6 gap-3">
                    <div class="w-12 h-12 rounded-full bg-amber-500/10 border border-amber-500/30 flex items-center justify-center">
                        <i class="bi bi-slash-circle text-amber-400 text-xl"></i>
                    </div>
                    <p class="text-xs font-bold text-amber-400 text-center">Plan Suspended</p>
                    <p class="text-[11px] text-gray-400 text-center leading-relaxed">
                        Your plan access has been suspended<br>pending refund review.
                    </p>
                    <div class="mt-1 px-3 py-1.5 bg-amber-500/10 border border-amber-500/20 rounded-lg text-[10px] text-amber-300 text-center">
                        All quotas: <span class="font-bold">0 / 0</span>
                    </div>
                </div>
            @else
                {{-- Resource rows — with blue outline and breakdown (Plan + Additional Purchase Number) --}}
                <div class="flex flex-col gap-3 flex-1">
                    {{-- Users --}}
                    <div
                        class="bg-slate-900/40 border border-blue-500 rounded-xl px-4 py-3 flex items-center justify-between gap-3 flex-1">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-people text-blue-400 text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-gray-300 font-bold uppercase tracking-wider">Users</p>
                                <p class="text-[10px] text-gray-400">Used / Allowed seats</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-base font-extrabold text-white">
                                {{ number_format($userUsed) }} / {{ number_format($userAlow) }}
                            </p>
                            <p class="text-[10px] text-blue-300 font-semibold font-mono">
                                ({{ $baseUsers }}+{{ $adnlUsers }})
                            </p>
                        </div>
                    </div>

                    {{-- Cloud Storage --}}
                    <div
                        class="bg-slate-900/40 border border-blue-500 rounded-xl px-4 py-3 flex items-center justify-between gap-3 flex-1">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-hdd-network text-emerald-400 text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-gray-300 font-bold uppercase tracking-wider">Cloud Storage (MB)</p>
                                <p class="text-[10px] text-gray-400">Used / Allowed space</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-base font-extrabold text-white">
                                {{ number_format($spceUsed, 1) }} / {{ number_format($spceAlow) }}
                            </p>
                            <p class="text-[10px] text-emerald-300 font-semibold font-mono">
                                ({{ (int)$baseStorage }}+{{ (int)$adnlStorage }})
                            </p>
                        </div>
                    </div>

                    {{-- Entries --}}
                    <div
                        class="bg-slate-900/40 border border-blue-500 rounded-xl px-4 py-3 flex items-center justify-between gap-3 flex-1">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-journal-text text-purple-400 text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-gray-300 font-bold uppercase tracking-wider">Entries</p>
                                <p class="text-[10px] text-gray-400">Used / Allowed rows</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-base font-extrabold text-white">
                                {{ number_format($entyUsed) }} / {{ number_format($entyAlow) }}
                            </p>
                            <p class="text-[10px] text-purple-300 font-semibold font-mono">
                                ({{ number_format($baseEntries) }}+{{ number_format($adnlEntries) }})
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Column 3: Plan Benefits --}}
        <div
            class="bg-gradient-to-br from-slate-800/60 via-slate-800/40 to-blue-950/20 border border-blue-500 rounded-2xl p-6 shadow-md relative overflow-hidden flex flex-col">
            <div
                class="absolute -right-10 -bottom-10 w-36 h-36 bg-blue-500/5 rounded-full blur-2xl pointer-events-none">
            </div>

            {{-- Header --}}
            <div class="relative z-10 flex items-center justify-between mb-4 flex-shrink-0">
                <h4 class="text-xs font-bold text-blue-300 uppercase tracking-wider">Plan Benefits</h4>
                @if($bnftUsers > 0 || $bnftStorage > 0 || $bnftEntries > 0 || $crPtEntries > 0 || $crPtStorage > 0)
                    <span
                        class="px-2 py-0.5 text-[10px] font-bold text-blue-300 bg-blue-500/10 border border-blue-500/20 rounded-full flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        Included
                    </span>
                @else
                    <span
                        class="px-2 py-0.5 text-[10px] font-medium text-gray-400 bg-slate-800 border border-blue-500/20 rounded-full">None</span>
                @endif
            </div>

            {{-- Benefit rows --}}
            <div class="relative z-10 flex flex-col gap-3 flex-1">

                {{-- Benefit Users --}}
                <div
                    class="bg-slate-900/50 border border-blue-500 rounded-xl px-4 py-3 flex items-center justify-between gap-3 flex-1">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-people-fill text-blue-400 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] text-gray-300 font-bold uppercase tracking-wider">Bonus Users</p>
                            <p class="text-[10px] text-gray-500">Included with plan</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        @if($bnftUsers > 0)
                            <span class="text-sm font-extrabold text-blue-400">+{{ number_format($bnftUsers) }} <span
                                    class="text-xs font-normal text-gray-400">seats</span></span>
                        @else
                            <span class="text-xs font-semibold text-gray-500">—</span>
                        @endif
                    </div>
                </div>

                {{-- Benefit Storage --}}
                <div
                    class="bg-slate-900/50 border border-blue-500 rounded-xl px-4 py-3 flex items-center justify-between gap-3 flex-1">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-hdd-fill text-emerald-400 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] text-gray-300 font-bold uppercase tracking-wider">Bonus Cloud Storage</p>
                            <p class="text-[10px] text-gray-500">
                                @if($crPtStorage > 0) incl. {{ number_format($crPtStorage, 1) }} MB credit pts @else
                                Included with plan @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        @if($bnftStorage > 0 || $crPtStorage > 0)
                            <span
                                class="text-sm font-extrabold text-emerald-400">+{{ number_format($bnftStorage + $crPtStorage, 1) }}
                                <span class="text-xs font-normal text-gray-400">MB</span></span>
                        @else
                            <span class="text-xs font-semibold text-gray-500">—</span>
                        @endif
                    </div>
                </div>

                {{-- Benefit Entries --}}
                <div
                    class="bg-slate-900/50 border border-blue-500 rounded-xl px-4 py-3 flex items-center justify-between gap-3 flex-1">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-journal-richtext text-purple-400 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] text-gray-300 font-bold uppercase tracking-wider">Bonus Entries</p>
                            <p class="text-[10px] text-gray-500">
                                @if($crPtEntries > 0) incl. {{ number_format($crPtEntries) }} credit pt rows @else
                                Included with plan @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        @if($bnftEntries > 0 || $crPtEntries > 0)
                            <span
                                class="text-sm font-extrabold text-purple-400">+{{ number_format($bnftEntries + $crPtEntries) }}
                                <span class="text-xs font-normal text-gray-400">rows</span></span>
                        @else
                            <span class="text-xs font-semibold text-gray-500">—</span>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>


    {{-- Additional Purchases Log Table --}}
    @if(!empty($purchaseLogs) || $adnlUsers > 0 || $adnlStorage > 0 || $adnlEntries > 0)
        <div class="mt-6 bg-slate-800/40 border border-blue-500 rounded-2xl overflow-hidden shadow-md">
            {{-- Table Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-blue-500">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-500/15 flex items-center justify-center">
                        <i class="bi bi-clock-history text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Additional Purchase History / Log</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Chronological record of approved add-on purchases</p>
                    </div>
                </div>
                <span
                    class="px-2.5 py-1 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Active Log
                </span>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-blue-500 bg-slate-900/40">
                            <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest px-5 py-3">
                                Date</th>
                            <th class="text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest px-5 py-3">
                                User</th>
                            <th class="text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest px-5 py-3">
                                Entry</th>
                            <th class="text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest px-5 py-3">
                                Space</th>
                            <th class="text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest px-5 py-3">
                                Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-500/20">
                        @forelse($purchaseLogs as $log)
                            <tr class="hover:bg-slate-700/20 transition-colors duration-150">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <i class="bi bi-calendar-check text-blue-400 text-sm"></i>
                                        <span class="text-sm font-semibold text-white">{{ $log['date'] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold text-blue-400 bg-blue-500/10 border border-blue-500/20">
                                        {{ $log['user'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold text-purple-400 bg-purple-500/10 border border-purple-500/20">
                                        {{ $log['entry'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20">
                                        {{ $log['space'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <span class="text-sm font-extrabold text-emerald-400">{{ $log['amount'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-xs text-gray-500">
                                    No additional purchases logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Upcoming / Queued Plan Banner (shows if a 2nd plan was purchased while current is active) --}}
    @if($queuedSub)
        <div class="mt-6 mb-2">
            <div
                class="bg-gradient-to-r from-blue-900/40 via-indigo-900/30 to-blue-950/20 border border-blue-500 rounded-2xl px-6 py-5 flex flex-col md:flex-row md:items-center gap-6 shadow-lg relative overflow-hidden">
                {{-- animated glow --}}
                <div class="absolute -left-6 -top-6 w-24 h-24 bg-blue-400/10 rounded-full blur-2xl pointer-events-none">
                </div>
                <div class="absolute right-0 bottom-0 w-32 h-16 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none">
                </div>

                {{-- Icon --}}
                <div
                    class="w-12 h-12 rounded-xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/10 relative z-10">
                    <i class="bi bi-calendar2-check text-blue-300 text-xl"></i>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0 relative z-10">
                    <div class="flex items-center gap-2.5 mb-1">
                        <span class="text-xs font-black uppercase tracking-widest text-blue-300">Upcoming Plan Purchased</span>
                        <span
                            class="px-2.5 py-0.5 text-[10px] font-black rounded-full bg-blue-400/20 text-blue-300 border border-blue-400/30 uppercase tracking-wider">Queued</span>
                    </div>
                    <p class="text-base font-extrabold text-white truncate">
                        {{ $queuedPlanName ?? 'Upcoming Plan' }}
                        <span class="text-sm font-normal text-slate-400 ml-2">— Contacts Vertical</span>
                    </p>
                </div>

                {{-- Dates Grid --}}
                <div
                    class="flex items-center gap-6 flex-shrink-0 relative z-10 bg-slate-900/40 border border-blue-500 rounded-xl px-5 py-3 shadow-inner">
                    <div class="text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Starts On</p>
                        <p class="text-sm font-black text-white mt-1">{{ $queuedStartDate }}</p>
                    </div>
                    <div class="w-px h-10 bg-blue-500/30"></div>
                    <div class="text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Ends On</p>
                        <p class="text-sm font-black text-blue-300 mt-1">{{ $queuedEndDate }}</p>
                    </div>
                    <div class="w-px h-10 bg-blue-500/30"></div>
                    <div class="text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Duration</p>
                        <p class="text-sm font-black text-blue-300 mt-1">{{ $queuedDuration }}</p>
                    </div>
                </div>

                {{-- Tail note --}}
                <div class="flex-shrink-0 relative z-10 border-l border-blue-500 pl-4 hidden xl:block">
                    <p class="text-xs text-slate-300 font-medium leading-relaxed text-right">
                        This plan will activate automatically<br>when your current plan expires.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Refund Success Toast Alert --}}
    @if($refundSuccessMessage)
        <div
            class="mt-4 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-300 text-xs font-semibold flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-400 text-sm"></i>
                {{ $refundSuccessMessage }}
            </span>
            <button type="button" wire:click="$set('refundSuccessMessage', '')" class="text-emerald-400 hover:text-white">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    @endif

    {{-- 7-Day Refund Request Modal --}}
    @if($showRefundModal)
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div
                class="bg-slate-900 border border-blue-500 rounded-2xl max-w-md w-full p-6 shadow-2xl relative overflow-hidden animate-fadeIn">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-4 border-b border-blue-500">
                    <div class="flex items-center gap-2.5">
                        <i class="bi bi-arrow-counterclockwise text-rose-400 text-xl"></i>
                        <div>
                            <h3 class="text-base font-bold text-white">Request 7-Day Refund</h3>
                        </div>
                    </div>
                    <button type="button" wire:click="closeRefundModal"
                        class="text-gray-400 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                {{-- Error Message Alert --}}
                @if($refundErrorMessage)
                    <div
                        class="mt-4 p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-300 text-xs font-semibold">
                        {{ $refundErrorMessage }}
                    </div>
                @endif

                {{-- Plan & Transaction Details Summary --}}
                <div class="mt-4 p-3.5 bg-slate-800/60 border border-blue-500 rounded-xl space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Active Plan:</span>
                        <span class="font-bold text-white">{{ $planName }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Purchase Date:</span>
                        <span class="font-medium text-gray-300">{{ $startDate }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Refundable Amount:</span>
                        <span class="font-extrabold text-emerald-400">{{ $refundableAmount }}</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-blue-500 text-[11px]">
                        <span class="text-rose-400">Eligibility Window:</span>
                        <span class="font-bold text-rose-300">{{ $refundDaysLeft }} days remaining</span>
                    </div>
                </div>

                {{-- Refund Reason Form --}}
                <form wire:submit.prevent="submitRefundRequest" class="mt-4 space-y-4">
                    {{-- Reason Category Dropdown --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Reason for Refund <span
                                class="text-rose-400">*</span></label>
                        <select wire:model="refundReasonCategory"
                            class="w-full bg-slate-800 border border-blue-500 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                            <option value="Not satisfied with features">Not satisfied with features</option>
                            <option value="Accidental purchase">Accidental purchase</option>
                            <option value="Technical issue or bugs">Technical issue or bugs</option>
                            <option value="Switched to different product">Switched to different product</option>
                            <option value="Other">Other reason</option>
                        </select>
                    </div>

                    {{-- Reason Details Textarea --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Additional Details (Optional)</label>
                        <textarea wire:model="refundReasonDetails" rows="3"
                            placeholder="Please explain briefly why you want a refund..."
                            class="w-full bg-slate-800 border border-blue-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 placeholder-gray-500"></textarea>
                    </div>

                    {{-- Policy Notice --}}
                    <div
                        class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-[11px] text-amber-300 leading-relaxed flex gap-2">
                        <i class="bi bi-info-circle text-amber-400 text-sm flex-shrink-0 mt-0.5"></i>
                        <span>Upon approval by admin in central portal, your plan limits will revert and refund will be
                            processed within 5-7 business days.</span>
                    </div>

                    {{-- Modal Action Buttons --}}
                    <div class="flex items-center justify-end pt-2">
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white text-xs font-bold shadow-lg shadow-rose-950/30 transition-all">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
