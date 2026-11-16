<div class="p-6 sm:px-8 lg:px-10 text-slate-100 max-w-7xl mx-auto">

    {{-- ============================================================ --}}
    {{-- TOP BAR: Title + Month & Year Selectors                      --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Past Record</h2>
            <p class="text-xs text-gray-400 mt-0.5">Historical log of organization activities, entries, and storage utilization</p>
        </div>

        {{-- Month and Year dropdowns --}}
        <div class="flex items-center gap-2">
            <select wire:model.live="selectedMonth"
                class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3 cursor-pointer">
                @foreach($this->availableMonths as $num => $name)
                    <option value="{{ $num }}" {{ $num === $selectedMonth ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>

            <select wire:model.live="selectedYear"
                class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3 cursor-pointer">
                @foreach($this->availableYears as $year)
                    <option value="{{ $year }}" {{ $year === $selectedYear ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 3 SUMMARY TILES (Month + Year Scoped)                        --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

        {{-- Total Users --}}
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-600/20 border border-blue-500/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-people text-blue-400 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Total Users</p>
                <p class="text-xl font-bold text-white">{{ number_format($this->totalUsers) }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Active this period</p>
            </div>
        </div>

        {{-- Total Entry --}}
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-journal-text text-indigo-400 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Total Entry</p>
                <p class="text-xl font-bold text-white">{{ number_format($this->totalEntry) }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Recorded entries</p>
            </div>
        </div>

        {{-- Total Space --}}
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-database text-emerald-400 text-lg"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Total Space</p>
                <p class="text-xl font-bold text-white">{{ $this->totalSpace }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Attachments + 1.50 KB/contact</p>
            </div>
        </div>

    </div>

    {{-- ============================================================ --}}
    {{-- CONTROLS BAR: Row selector on Left, Activity on Right        --}}
    {{-- (Exact same layout & classes as contacts/index.blade.php)    --}}
    {{-- ============================================================ --}}
    <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
        {{-- Row selector --}}
        <div class="flex items-center gap-1.5">
            <span class="text-sm text-gray-400 font-medium">Row</span>
            <select wire:model.live="perPage" class="bg-slate-700 border-slate-600 rounded text-sm py-1 px-2 text-white cursor-pointer focus:ring-blue-500 focus:border-blue-500">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
        </div>

        {{-- Activity filter --}}
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-400 font-medium shrink-0">Activity:</span>
            <select wire:model.live="activityFilter" class="bg-slate-700 border-slate-600 rounded text-sm py-1 px-2 text-white cursor-pointer min-w-[200px] focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Activities</option>
                @foreach($this->availableActivities as $act)
                    <option value="{{ $act }}">{{ $act }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- TABLE                                                        --}}
    {{-- ============================================================ --}}
    <div class="bg-slate-900/50 backdrop-blur-sm shadow-md rounded-xl overflow-hidden border border-slate-700/60">

        {{-- Custom scrollbar styles --}}
        <style>
            .past-record-scroll::-webkit-scrollbar { height: 5px; }
            .past-record-scroll::-webkit-scrollbar-track { background: transparent; }
            .past-record-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 99px; }
            .past-record-scroll::-webkit-scrollbar-thumb:hover { background: #475569; }
        </style>

        <div class="past-record-scroll overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-400">
                <thead class="text-cyan-400 bg-slate-800/80 text-xs font-medium uppercase tracking-wider border-b border-slate-700">
                    <tr>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">User Name</th>
                        <th class="px-6 py-4">Activity</th>
                        <th class="px-6 py-4 text-center">Count</th>
                        <th class="px-6 py-4 text-right">UOM</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/40 bg-slate-900/20">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/40 transition-colors duration-150 group">
                            <td class="px-6 py-4 text-slate-400 font-mono text-xs whitespace-nowrap tracking-wide">
                                {{ \Carbon\Carbon::parse($log->log_date)->format('d M Y') }}
                            </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="text-slate-200 font-medium text-sm">{{ $log->User_Name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                            <td class="px-6 py-4">
                                @php
                                    $activityColors = [
                                        'Add Entry'                  => 'bg-emerald-500/15 text-emerald-400',
                                        'Edit Entry'                 => 'bg-blue-500/15 text-blue-400',
                                        'Delete Contact'             => 'bg-rose-500/15 text-rose-400',
                                        'Import Contact'             => 'bg-violet-500/15 text-violet-400',
                                        'Export Contact'             => 'bg-amber-500/15 text-amber-400',
                                        'Add Notes'                  => 'bg-teal-500/15 text-teal-400',
                                        'Edit Notes'                 => 'bg-cyan-500/15 text-cyan-400',
                                        'Delete Notes'               => 'bg-rose-500/15 text-rose-400',
                                        'Add Tag'                    => 'bg-pink-500/15 text-pink-400',
                                        'Edit Tag'                   => 'bg-fuchsia-500/15 text-fuchsia-400',
                                        'Delete Tag'                 => 'bg-rose-500/15 text-rose-400',
                                        'Add Group'                  => 'bg-indigo-500/15 text-indigo-400',
                                        'Edit Group'                 => 'bg-blue-500/15 text-blue-400',
                                        'Delete Group'               => 'bg-rose-500/15 text-rose-400',
                                        'Add with Attachment Entry'  => 'bg-green-500/15 text-green-400',
                                        'Edit with Attachment Entry' => 'bg-sky-500/15 text-sky-400',
                                        'Link Generate'              => 'bg-orange-500/15 text-orange-400',
                                        'Contact View'               => 'bg-slate-500/15 text-slate-300',
                                        'User Onboard'               => 'bg-lime-500/15 text-lime-400',
                                        'User Removed'               => 'bg-red-500/15 text-red-400',
                                    ];
                                    $badgeClass = $activityColors[$log->activity] ?? 'bg-slate-700/60 text-slate-300';
                                @endphp
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold {{ $badgeClass }} whitespace-nowrap">
                                    {{ $log->activity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-white font-bold text-sm">{{ number_format($log->total_count) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-slate-500 text-xs uppercase font-mono tracking-widest">{{ $log->UOM }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-20 text-center">
                                <div class="flex flex-col items-center justify-center gap-4 text-gray-500">
                                    <div class="w-14 h-14 rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center text-gray-400">
                                        <i class="bi bi-journal-text text-2xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-400">No records found for this period</p>
                                        <p class="text-xs text-gray-500 mt-1">Try selecting a different month or year above</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- PAGINATION FOOTER (Same to same as contacts/index.blade.php)  --}}
    {{-- ============================================================ --}}
    @if ($logs->total() > 0)
        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 py-3 bg-slate-900/30 rounded px-4">
            <!-- Left: Showing text -->
            <div class="text-sm text-gray-400">
                Showing
                <span class="font-medium text-white">{{ $logs->firstItem() }}</span>
                to
                <span class="font-medium text-white">{{ $logs->lastItem() }}</span>
                of
                <span class="font-medium text-white">{{ $logs->total() }}</span>
                results
            </div>

            <!-- Right: Pagination Buttons -->
            <div class="flex items-center space-x-1">
                {{-- Previous Button --}}
                @if ($logs->onFirstPage())
                    <span class="px-3 py-1 text-sm text-gray-500 bg-slate-800 rounded cursor-not-allowed">
                        <i class="bi bi-chevron-left"></i>
                    </span>
                @else
                    <button wire:click="previousPage"
                        class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                @endif

                {{-- Page Numbers --}}
                @foreach ($logs->getUrlRange(1, $logs->lastPage()) as $page => $url)
                    @if ($page == $logs->currentPage())
                        <span class="px-3 py-1 text-sm font-bold text-white bg-blue-600 rounded">
                            {{ $page }}
                        </span>
                    @else
                        <button wire:click="gotoPage({{ $page }})"
                            class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach

                {{-- Next Button --}}
                @if ($logs->hasMorePages())
                    <button wire:click="nextPage"
                        class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                @else
                    <span class="px-3 py-1 text-sm text-gray-500 bg-slate-800 rounded cursor-not-allowed">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                @endif
            </div>
        </div>
    @endif

</div>
