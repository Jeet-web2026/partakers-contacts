<div class="p-6">

    {{-- ============================================================ --}}
    {{-- HEADER BOX (separate box like Tag)                            --}}
    {{-- ============================================================ --}}
    <div class="bg-slate-800/60 border border-slate-700 rounded-xl px-4 py-3 mb-6 flex justify-between items-center">
        <div class="flex items-center gap-2.5">
            <i class="bi bi-diagram-3 text-blue-400 text-lg shrink-0"></i>
            <div class="flex flex-col">
                <h2 class="text-sm font-semibold text-white">Groups</h2>
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
    {{-- GROUP LIST TABLE (Month/Year Filtered)                        --}}
    {{-- ============================================================ --}}
    <div>

        {{-- Header Row: Title + total count --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <p class="text-xs text-gray-400">All Groups in this Organization</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-500/10 rounded-full text-xs font-medium text-blue-400 border border-blue-500/20">
                {{ $this->allGroups->count() }} {{ $this->allGroups->count() === 1 ? 'Group' : 'Groups' }}
            </span>
        </div>

        <div class="bg-slate-900/50 backdrop-blur-sm shadow-md rounded-xl overflow-hidden border border-slate-700/60">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-400">
                    <thead class="text-cyan-400 bg-slate-800/80 text-xs font-medium uppercase tracking-wider border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-4">Group Name</th>
                            <th class="px-6 py-4">Parent Group</th>
                            <th class="px-6 py-4">Creation Date</th>
                            <th class="px-6 py-4">Created By</th>
                            <th class="px-6 py-4 text-center">Members</th>
                            <th class="px-6 py-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40 bg-slate-900/20">
                        @forelse($this->allGroups as $group)
                            <tr wire:key="group-row-{{ $group->Admn_Grup_Mast_UIN }}" class="hover:bg-slate-800/40 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-100">{{ $group->Name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($group->parent_name)
                                        <span class="text-slate-400">{{ $group->parent_name }}</span>
                                    @else
                                        <span class="text-slate-600 italic text-xs">Root</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-400 text-xs">
                                        {{ $group->CrOn ? \Carbon\Carbon::parse($group->CrOn)->format('d M Y') : '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-300 text-xs">
                                    {{ $group->created_by }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-500/10 rounded-full text-xs font-medium text-blue-400 border border-blue-500/20">
                                        {{ $group->users_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <span class="text-[10px] uppercase font-bold {{ $group->Is_Actv == 100201 ? 'text-blue-400' : 'text-gray-500' }}">
                                            {{ $group->Is_Actv == 100201 ? 'Active' : 'Inactive' }}
                                        </span>
                                        <button wire:click="toggleGroupStatus({{ $group->Admn_Grup_Mast_UIN }})"
                                            title="{{ $group->Is_Actv == 100201 ? 'Deactivate' : 'Activate' }}"
                                            style="position:relative; width:36px; height:20px; border-radius:10px; cursor:pointer; transition:background 0.2s; outline:none; border:none; padding:0; {{ $group->Is_Actv == 100201 ? 'background:#2563eb;' : 'background:#475569;' }}">
                                            <span style="position:absolute; top:3px; width:14px; height:14px; border-radius:50%; box-shadow:0 1px 2px rgba(0,0,0,0.3); transition:all 0.2s; {{ $group->Is_Actv == 100201 ? 'left:19px; background:white;' : 'left:3px; background:#94a3b8;' }}">
                                            </span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">
                                    No groups found for this organization.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


