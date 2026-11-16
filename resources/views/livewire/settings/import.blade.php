<div class="p-6">

    {{-- ============================================================ --}}
    {{-- HEADER BOX (separate box with icon above toggle)              --}}
    {{-- ============================================================ --}}
    <div class="bg-slate-800/60 border border-slate-700 rounded-xl px-4 py-3 mb-6 flex justify-between items-center">
        <div class="flex items-center gap-2.5">
            <i class="bi bi-box-arrow-in-down text-blue-400 text-lg shrink-0"></i>
            <div class="flex flex-col">
                <h2 class="text-sm font-semibold text-white">Import</h2>
                <p class="text-[10px] text-gray-400">Enabling this button will allow all users to access this feature.</p>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
                <span class="text-xs font-medium {{ $enabled ? 'text-blue-400' : 'text-gray-500' }} w-6 text-right">
                    {{ $enabled ? 'On' : 'Off' }}
                </span>
                <button wire:click="toggle"
                    style="position:relative; width:44px; height:24px; border-radius:12px; cursor:pointer; transition:background 0.2s; outline:none; border:none; padding:0; {{ $enabled ? 'background:#2563eb;' : 'background:#d1d5db;' }}">
                    <span style="position:absolute; top:4px; width:16px; height:16px; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,0.3); transition:all 0.2s; {{ $enabled ? 'left:24px; background:white;' : 'left:4px; background:#6b7280;' }}"></span>
                </button>
            </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MONTHLY CSV FILES IMPORTED SUMMARY                           --}}
    {{-- ============================================================ --}}
    <div>

        {{-- Header: subtitle + month dropdown (no calendar icon) --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <p class="text-xs text-gray-400">Monthly Number of CSV Files Imported</p>

            <div class="flex items-center gap-2 shrink-0">
                <select wire:model.live="selectedMonth"
                    class="bg-slate-700 border border-slate-600 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 py-1.5 px-3">
                    @if(count($this->availableMonths) > 0)
                        @foreach($this->availableMonths as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    @else
                        <option value="{{ now()->format('Y-m') }}">{{ now()->format('M-y') }}</option>
                    @endif
                </select>
            </div>
        </div>

        <div class="bg-slate-900/50 backdrop-blur-sm shadow-md rounded-xl overflow-hidden border border-slate-700/60">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-400">
                    <thead class="text-cyan-400 bg-slate-800/80 text-xs font-medium uppercase tracking-wider border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-4">Import Date</th>
                            <th class="px-6 py-4">Imported By</th>
                            <th class="px-6 py-4 text-right">Nos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40 bg-slate-900/20">
                        @forelse($this->importSummary as $row)
                            <tr wire:key="import-row-{{ $loop->index }}" class="hover:bg-slate-800/40 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <span class="text-slate-400 text-xs font-mono">
                                        {{ \Carbon\Carbon::parse($row->import_date)->format('d M Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-100">{{ $row->imported_by }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-300 border border-green-500/20">
                                        {{ $row->nos }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500 italic">
                                    No imports in {{ $this->selectedMonthLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Totals Footer — always shown when there is data --}}
                    @if($this->importSummary->count() > 0)
                        <tfoot class="border-t border-slate-700 bg-slate-800/60">
                            <tr>
                                <td class="px-6 py-3 text-xs font-semibold text-slate-400 uppercase">
                                    Total Rows: {{ $this->importSummary->count() }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-sm font-bold text-green-300">{{ $this->importSummary->sum('nos') }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>


