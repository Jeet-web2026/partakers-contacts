<div class="p-6">

    {{-- ============================================================ --}}
    {{-- HEADER BOX (like Tags box style)                              --}}
    {{-- ============================================================ --}}
    <div class="flex items-center justify-between mb-5 bg-slate-800/60 border border-slate-700 rounded-xl px-5 py-4">

        {{-- Left: Icon + Title --}}
        <div class="flex items-center gap-3">
            <i class="bi bi-person-plus text-blue-400 text-2xl"></i>
            <h2 class="text-lg font-semibold text-white">Users</h2>
        </div>

        {{-- Right: Add User button --}}
        <button wire:click="openSearchModal"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
            <i class="bi bi-plus-circle"></i>
            Add User
        </button>
    </div>

    <div class="space-y-6">
        @if(session()->has('success'))
            <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-xl flex items-start gap-3">
                <i class="bi bi-check-circle-fill text-green-400 mt-0.5"></i>
                <span class="text-green-400 text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        {{-- User List Table --}}
        <div class="bg-slate-900/50 border border-slate-700 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-cyan-400 capitalize bg-slate-800/50 border-b border-slate-700">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">Users' Names</th>
                            <th scope="col" class="px-6 py-4 font-medium">Email / Mobile</th>
                            <th scope="col" class="px-6 py-4 font-medium">Permission Level</th>
                            <th scope="col" class="px-6 py-4 font-medium">Valid Upto</th>
                            <th scope="col" class="px-6 py-4 font-medium">Permissions</th>
                            <th scope="col" class="px-6 py-4 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($onboardedUsers as $u)
                            <tr class="hover:bg-slate-800/20 transition-colors">
                                {{-- Users' Names --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-white font-medium overflow-hidden">
                                            @if($u->User_Logo)
                                                <img src="{{ Storage::url($u->User_Logo) }}" class="w-full h-full object-cover">
                                            @else
                                                {{ substr($u->User_Name, 0, 1) }}
                                            @endif
                                        </div>
                                        <div class="font-medium text-white">{{ $u->User_Name }}</div>
                                    </div>
                                </td>

                                {{-- Email / Mobile --}}
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400">
                                    {{ $u->Prmy_Emai ?? $u->Prmy_MoNo }}
                                </td>

                                {{-- Permission Level --}}
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400">
                                    {{ $u->User_Assc_Lbel_Name ?? ($u->is_admin ? 'Administrator' : '—') }}
                                </td>

                                {{-- Valid Upto --}}
                                <td class="px-6 py-4 whitespace-nowrap text-gray-400">
                                    {{ ($u->valid_upto ?? null) ? \Carbon\Carbon::parse($u->valid_upto)->format('d M Y') : 'Lifetime' }}
                                </td>

                                {{-- Permissions as symbols --}}
                                <td class="px-6 py-4">
                                    @php
                                        $permIcons = [
                                            ['field' => 'can_add',    'icon' => 'bi-plus-circle',            'title' => 'Add Entry'],
                                            ['field' => 'can_edit',   'label' => 'bi-pencil',                'title' => 'Edit Entry'],
                                            ['field' => 'can_del',    'icon' => 'bi-trash',                  'title' => 'Delete Entry'],
                                            ['field' => 'can_atch',   'icon' => 'bi-paperclip',              'title' => 'Attachments'],
                                            ['field' => 'can_link',   'icon' => 'bi-link-45deg',             'title' => 'Links'],
                                            ['field' => 'can_rept',   'icon' => 'bi-eye',                    'title' => 'Reports'],
                                            ['field' => 'can_impt',   'icon' => 'bi-box-arrow-in-down',      'title' => 'Import'],
                                            ['field' => 'can_expt',   'icon' => 'bi-box-arrow-up',           'title' => 'Export'],
                                            ['field' => 'mast_grp',   'icon' => 'bi-diagram-3',              'title' => 'Master Groups'],
                                            ['field' => 'mast_tag',   'icon' => 'bi-tags',                   'title' => 'Master Tags'],
                                            ['field' => 'mast_notes', 'icon' => 'bi-card-text',              'title' => 'Master Notes'],
                                        ];
                                    @endphp
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($permIcons as $p)
                                            @if($u->{$p['field']})
                                                <i class="bi {{ $p['icon'] ?? $p['label'] }} text-blue-400 text-base" title="{{ $p['title'] }}"></i>
                                            @else
                                                <i class="bi {{ $p['icon'] ?? $p['label'] }} text-slate-700 text-base" title="{{ $p['title'] }} (Disabled)"></i>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-400 font-medium">
                                    <button wire:click="openPermissionModal('{{ $u->User_UIN }}')" class="hover:text-blue-400 transition-colors" title="Edit Permissions">
                                        <i class="bi bi-pencil-square text-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    <div class="flex flex-col items-center gap-2">
                                        <i class="bi bi-people text-3xl text-gray-600"></i>
                                        <p>No users onboarded yet.</p>
                                        <button wire:click="openSearchModal" class="text-blue-400 hover:text-blue-300 mt-1">Add your first user</button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Search Modal --}}
        @if($isSearchModalOpen)
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between bg-slate-800/50">
                        <h3 class="text-lg font-semibold text-white">Find User to Onboard</h3>
                        <button wire:click="closeSearchModal" class="text-gray-400 hover:text-white transition-colors">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1.5">Email or Mobile Number</label>
                            <input type="text" wire:model.defer="searchQuery" wire:keydown.enter="searchUser" class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm" placeholder="Enter email or mobile...">
                            @error('searchQuery') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <button wire:click="searchUser" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>

                        @if($searchError)
                            <div class="mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg flex items-start gap-2 text-red-400">
                                <i class="bi bi-exclamation-circle mt-0.5"></i>
                                <span class="text-sm">{{ $searchError }}</span>
                            </div>
                        @endif

                        @if($searchedUser)
                            <div class="mt-4 p-4 bg-slate-800/70 border border-slate-700 rounded-xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 font-medium">
                                        {{ substr($searchedUser->User_Name, 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-white font-medium truncate">{{ $searchedUser->User_Name }}</p>
                                        <p class="text-sm text-gray-400 truncate">{{ $searchedUser->Prmy_Emai ?? $searchedUser->Prmy_MoNo }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button wire:click="proceedToPermissions" class="px-4 py-1.5 bg-green-600/20 border border-green-500/30 hover:bg-green-600/30 text-green-400 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                        Proceed <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Permission Modal --}}
        @if($isPermissionModalOpen)
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-700/50 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-700/50 flex items-center justify-between bg-slate-800/50">
                        <div>
                            @if($selectedUserName)
                                <p class="text-xs text-gray-400 mb-0.5">{{ $selectedUserName }}</p>
                            @endif
                            <h3 class="text-lg font-semibold text-white">Assign Permissions</h3>
                        </div>
                        <button wire:click="closePermissionModal" class="text-gray-400 hover:text-white transition-colors">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-5 space-y-4">
                        {{-- Permission Cards Grid (8 items — 3 columns, scrollable) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-[35vh] overflow-y-auto pr-2 custom-scrollbar">
                            @php
                                $permissions = [
                                    ['field' => 'can_add',    'label' => 'Add Entry',    'desc' => 'Allow adding records'],
                                    ['field' => 'can_edit',   'label' => 'Edit Entry',   'desc' => 'Allow modifying records'],
                                    ['field' => 'can_delete', 'label' => 'Delete Entry', 'desc' => 'Allow deleting records'],
                                    ['field' => 'can_atch',   'label' => 'Attachments',  'desc' => 'Manage file attachments'],
                                    ['field' => 'can_link',   'label' => 'Links',        'desc' => 'Manage linked items'],
                                    ['field' => 'can_rept',   'label' => 'Reports',      'desc' => 'Access and run reports'],
                                    ['field' => 'can_impt',   'label' => 'Import',       'desc' => 'Import data files'],
                                    ['field' => 'can_expt',   'label' => 'Export',       'desc' => 'Export data'],
                                ];
                            @endphp

                            @foreach($permissions as $perm)
                            <div class="flex items-center justify-between p-2.5 bg-slate-800/60 rounded-xl border border-slate-700 hover:bg-slate-800 transition-colors">
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $perm['label'] }}</h4>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $perm['desc'] }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-medium {{ ${$perm['field']} ? 'text-blue-400' : 'text-gray-500' }} w-5 text-right">
                                        {{ ${$perm['field']} ? 'On' : 'Off' }}
                                    </span>
                                    <button wire:click="$toggle('{{ $perm['field'] }}')"
                                        style="position:relative; width:40px; height:20px; border-radius:10px; cursor:pointer; transition:background 0.2s; outline:none; border:none; padding:0; {{ ${$perm['field']} ? 'background:#2563eb;' : 'background:#d1d5db;' }}">
                                        <span style="position:absolute; top:3px; width:14px; height:14px; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,0.3); transition:all 0.2s; {{ ${$perm['field']} ? 'left:23px; background:white;' : 'left:3px; background:#6b7280;' }}"></span>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Divider --}}
                        <div class="h-px bg-slate-700/50"></div>

                        {{-- Master Permissions — always 3 columns in one row --}}
                        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0.75rem;">
                            @php
                                $masterPerms = [
                                    ['field' => 'mast_grp',   'label' => 'Master Groups', 'desc' => 'Manage master groups'],
                                    ['field' => 'mast_tag',   'label' => 'Master Tag',    'desc' => 'Manage master tags'],
                                    ['field' => 'mast_notes', 'label' => 'Master Notes',  'desc' => 'Manage master notes'],
                                ];
                            @endphp
                            @foreach($masterPerms as $perm)
                            <div class="flex items-center justify-between p-2.5 bg-slate-800/60 rounded-xl border border-slate-700 hover:bg-slate-800 transition-colors">
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $perm['label'] }}</h4>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $perm['desc'] }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-medium {{ ${$perm['field']} ? 'text-blue-400' : 'text-gray-500' }} w-5 text-right">
                                        {{ ${$perm['field']} ? 'On' : 'Off' }}
                                    </span>
                                    <button wire:click="$toggle('{{ $perm['field'] }}')"
                                        style="position:relative; width:40px; height:20px; border-radius:10px; cursor:pointer; transition:background 0.2s; outline:none; border:none; padding:0; {{ ${$perm['field']} ? 'background:#2563eb;' : 'background:#d1d5db;' }}">
                                        <span style="position:absolute; top:3px; width:14px; height:14px; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,0.3); transition:all 0.2s; {{ ${$perm['field']} ? 'left:23px; background:white;' : 'left:3px; background:#6b7280;' }}"></span>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>


                        {{-- Permission Level & Valid Upto Row --}}
                        <div class="flex flex-col sm:flex-row items-end gap-5">
                            {{-- Permission Level --}}
                            <div class="flex-shrink-0 w-full sm:w-auto min-w-[220px]">
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">
                                    Permission Level <span class="text-red-400">*</span>
                                </label>
                                <select wire:model.live="user_type"
                                    class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                    <option value="">— Select Permission Level —</option>
                                    @foreach($userTypeLabels as $label)
                                        <option value="{{ $label->Orga_User_Assc_UIN }}">
                                            {{ $label->User_Assc_Lbel_Name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_type') <span class="text-red-400 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Valid Upto / Lifetime access --}}
                            @if($user_type)
                                <div class="flex-1 w-full sm:w-auto">
                                    @if($selectedLabelName !== 'employee')
                                    <div class="h-[38px] flex items-center">
                                        <label class="block text-sm font-medium text-gray-300 mb-1.5">
                                           Validity End Date <span class="text-red-400">*</span>
                                            <span class="text-gray-500 font-normal"></span>
                                        </label>
                                        <input type="date" wire:model.defer="valid_upto" min="{{ date('Y-m-d') }}" max="{{ $this->planEndDate }}"
                                            class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                                        @error('valid_upto') <span class="text-red-400 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                    </div>
                                    @else
                                        <div class="h-[38px] flex items-center">
                                            <div class="inline-flex items-center gap-2.5 text-gray-300 font-semibold text-[13px] bg-slate-500/10 px-4 py-2 rounded-lg border border-slate-500/20">
                                                <i class="bi bi-infinity text-base"></i>
                                                <span>Lifetime — No expiry date</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button wire:click="closePermissionModal" class="px-4 py-2 border border-slate-600 text-gray-300 hover:bg-slate-700/50 text-sm font-medium rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button wire:click="savePermissions" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <i class="bi bi-check-lg"></i> Save Permissions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
