<div class="p-6">

    {{-- Flash messages --}}
    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg flex items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm rounded-lg flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- HEADER BOX (separate box with icon above toggle)              --}}
    {{-- ============================================================ --}}
    <div class="bg-slate-800/60 border border-slate-700 rounded-xl px-4 py-3 mb-6 flex justify-between items-center">
        <div class="flex items-center gap-2.5">
            <i class="bi bi-card-text text-blue-400 text-lg shrink-0"></i>
            <div class="flex flex-col">
                <h2 class="text-sm font-semibold text-white">Notes</h2>
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
    {{-- NOTES LIST TABLE (Month/Year Filtered)                         --}}
    {{-- ============================================================ --}}
    <div>

        {{-- Header Row: Title + Month Dropdown --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <p class="text-xs text-gray-400">Monthly Notes Summary</p>

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
                            <th class="px-6 py-4">Note / Comment</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Creation Date</th>
                            <th class="px-6 py-4">Created By</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40 bg-slate-900/20">
                        @forelse($this->allNotes as $note)
                            <tr wire:key="note-row-{{ $note->Admn_Cnta_Note_Comnt_UIN }}" class="hover:bg-slate-800/40 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-100">{{ \Illuminate\Support\Str::limit($note->Comnt_Text, 60) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($note->Category)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-700 text-slate-300">
                                            {{ $note->Category }}
                                        </span>
                                    @else
                                        <span class="text-slate-600 text-xs italic">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-xs">
                                    {{ $note->CrOn ? \Carbon\Carbon::parse($note->CrOn)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-slate-300 text-xs">
                                    {{ $note->created_by }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center">
                                        <button wire:click="toggleNoteStatus({{ $note->Admn_Cnta_Note_Comnt_UIN }})"
                                            title="{{ $note->Stau_UIN == 100201 ? 'Deactivate' : 'Activate' }}"
                                            style="position:relative; width:36px; height:20px; border-radius:10px; cursor:pointer; transition:background 0.2s; outline:none; border:none; padding:0; {{ $note->Stau_UIN == 100201 ? 'background:#2563eb;' : 'background:#475569;' }}">
                                            <span style="position:absolute; top:3px; width:14px; height:14px; border-radius:50%; box-shadow:0 1px 2px rgba(0,0,0,0.3); transition:all 0.2s; {{ $note->Stau_UIN == 100201 ? 'left:19px; background:white;' : 'left:3px; background:#94a3b8;' }}">
                                            </span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                    No notes found for {{ $this->selectedMonthLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


</div>


