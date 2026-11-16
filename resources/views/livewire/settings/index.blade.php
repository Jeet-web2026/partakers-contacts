{{-- Settings Page — DS Drive-style sidebar, dark theme matching bg-[#021420] --}}
<div class="flex min-h-[calc(100vh-52px)]" style="background-color: #0a1628;">

    {{-- ================================================================ --}}
    {{-- FIXED SIDEBAR                                                      --}}
    {{-- ================================================================ --}}
    {{-- DESKTOP SIDEBAR (hidden on mobile) --}}
    <aside class="hidden md:flex flex-shrink-0 flex-col border-r border-slate-700/60 transition-all duration-300"
           style="background-color: #0d1f3c;"
           :class="globalSidebarOpen ? 'w-72' : 'w-10 items-center'">

        {{-- Sidebar Header --}}
        <div class="w-full flex items-center border-b border-slate-700/60 py-3" :class="globalSidebarOpen ? 'justify-start px-4' : 'justify-center px-0'">
            <span x-show="globalSidebarOpen" class="text-xs font-bold tracking-widest text-gray-400 uppercase px-1">Settings</span>
        </div>

        {{-- ---- Overview Nav Item ---- --}}
      

        {{-- Divider --}}
        <div class="mx-4 my-1 border-t border-slate-700/50"></div>

        {{-- ---- Navigation Group 1: Feature Access ---- --}}
        <div class="px-3 pt-2 pb-1 w-full">
            <p x-show="globalSidebarOpen" class="text-[10px] font-bold tracking-widest text-gray-500 mb-3 px-2">Feature Access</p>

            @php
                $navGroup1 = [
                    'user'       => ['icon' => 'bi-person-plus',        'label' => 'User',       'color' => 'text-blue-400'],
                    'groups'     => ['icon' => 'bi-diagram-3',          'label' => 'Groups',     'color' => 'text-blue-400'],
                    'tag'        => ['icon' => 'bi-tags',               'label' => 'Tags',       'color' => 'text-blue-400'],
                    'notes'      => ['icon' => 'bi-card-text',          'label' => 'Notes',      'color' => 'text-blue-400'],
                    'invite'     => ['icon' => 'bi-link-45deg',         'label' => 'Links',      'color' => 'text-blue-400'],
                    'import'     => ['icon' => 'bi-box-arrow-in-down',  'label' => 'Import',     'color' => 'text-blue-400'],
                    'export'     => ['icon' => 'bi-box-arrow-up',       'label' => 'Export',     'color' => 'text-blue-400'],
                    'attachment' => ['icon' => 'bi-paperclip',          'label' => 'Attachment', 'color' => 'text-blue-400'],
                ];
            @endphp

            @foreach($navGroup1 as $key => $item)
                <button wire:click="switchTab('{{ $key }}')" title="{{ $item['label'] }}"
                    class="w-full flex items-center gap-3 py-2.5 rounded-lg text-[13.5px] mb-1 transition-all duration-150 group
                        {{ $activeTab === $key
                            ? 'bg-blue-600/20 text-white ring-1 ring-blue-500/30'
                            : 'text-gray-300 hover:bg-slate-700/50 hover:text-white' }}"
                    :class="globalSidebarOpen ? 'px-4' : 'px-0 justify-center'">
                    <i class="bi {{ $item['icon'] }} w-5 text-center flex-shrink-0 text-base
                        {{ $activeTab === $key ? $item['color'] : 'text-gray-500' }}"></i>
                    <span x-show="globalSidebarOpen" class="truncate font-medium">{{ $item['label'] }}</span>
                    @if($activeTab === $key)
                        <span x-show="globalSidebarOpen" class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Divider --}}
        <div class="mx-4 my-3 border-t border-slate-700/50"></div>

        {{-- ---- Navigation Group 2: Billing ---- --}}
        <div class="px-3 pb-3 w-full">
            <p x-show="globalSidebarOpen" class="text-[10px] font-bold tracking-widest text-gray-500 mb-3 px-2">Billing</p>

            @php
                $navGroup2 = [
                    'history' => ['icon' => 'bi-clock-history', 'label' => 'Past Record', 'color' => 'text-slate-300'],
                ];
            @endphp

            @foreach($navGroup2 as $key => $item)
                <button wire:click="switchTab('{{ $key }}')" title="{{ $item['label'] }}"
                    class="w-full flex items-center gap-3 py-2.5 rounded-lg text-[13.5px] mb-1 transition-all duration-150 group
                        {{ $activeTab === $key
                            ? 'bg-blue-600/20 text-white ring-1 ring-blue-500/30'
                            : 'text-gray-300 hover:bg-slate-700/50 hover:text-white' }}"
                    :class="globalSidebarOpen ? 'px-4' : 'px-0 justify-center'">
                    <i class="bi {{ $item['icon'] }} w-5 text-center flex-shrink-0 text-base
                        {{ $activeTab === $key ? $item['color'] : 'text-gray-500' }}"></i>
                    <span x-show="globalSidebarOpen" class="truncate font-medium">{{ $item['label'] }}</span>
                    @if($activeTab === $key)
                        <span x-show="globalSidebarOpen" class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Spacer --}}
        <div class="flex-1"></div>

        {{-- Bottom: Subscription Badge (Clickable link to open subscription & add-ons) --}}
        <div class="px-4 py-4 border-t border-slate-700/60">
            @php
                $expiryText = $activePlan && isset($activePlan->Sbcr_Last_Dt) 
                    ? \Carbon\Carbon::parse($activePlan->Sbcr_Last_Dt)->format('jS M Y') 
                    : 'No Active Plan';
            @endphp
            <button wire:click="switchTab('subscription')"
                class="w-full text-left flex items-center gap-2.5 rounded-lg px-3 py-2.5 transition-all duration-150 group cursor-pointer
                    {{ $activeTab === 'subscription' 
                        ? 'bg-blue-600/20 ring-1 ring-blue-500/30' 
                        : 'bg-slate-800/60 hover:bg-slate-700/60 hover:ring-1 hover:ring-slate-600/50' }}">
                <i class="bi bi-shield-check text-blue-400 text-base flex-shrink-0"></i>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-white leading-tight group-hover:text-blue-300 transition-colors">{{ $planName ?? 'Basic Plan' }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5 truncate">Expires {{ $expiryText }}</p>
                </div>
                <i class="bi bi-chevron-right text-xs text-gray-500 group-hover:text-white transition-colors ml-auto flex-shrink-0"></i>
            </button>
        </div>
    </aside>

    {{-- MOBILE SIDEBAR OVERLAY BACKDROP --}}
    <div x-show="mobileSidebarOpen" x-transition.opacity @click="mobileSidebarOpen = false" class="fixed inset-0 bg-black/60 z-40 md:hidden" style="display: none;"></div>

    {{-- MOBILE SIDEBAR (full overlay with labels, shown only on mobile) --}}
    <aside class="md:hidden fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 flex flex-col border-r border-slate-700/60 overflow-y-auto py-3 transform transition-all duration-300 -translate-x-full"
           :class="mobileSidebarOpen ? '!translate-x-0' : ''"
           style="background-color: #0d1f3c;">

        {{-- Sidebar Header --}}
        <div class="w-full flex items-center justify-between border-b border-slate-700/60 py-3 px-4">
            <span class="text-xs font-bold tracking-widest text-gray-400 uppercase px-1">Settings</span>
            <button @click="mobileSidebarOpen = false" class="text-gray-400 hover:text-white p-1">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Divider --}}
        <div class="mx-4 my-1 border-t border-slate-700/50"></div>

        {{-- Navigation Group 1: Feature Access --}}
        <div class="px-3 pt-2 pb-1 w-full">
            <p class="text-[10px] font-bold tracking-widest text-gray-500 mb-3 px-2">Feature Access</p>
            @foreach($navGroup1 as $key => $item)
                <button wire:click="switchTab('{{ $key }}')" @click="mobileSidebarOpen = false" title="{{ $item['label'] }}"
                    class="w-full flex items-center gap-3 py-2.5 px-4 rounded-lg text-[13.5px] mb-1 transition-all duration-150 group
                        {{ $activeTab === $key
                            ? 'bg-blue-600/20 text-white ring-1 ring-blue-500/30'
                            : 'text-gray-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="bi {{ $item['icon'] }} w-5 text-center flex-shrink-0 text-base
                        {{ $activeTab === $key ? $item['color'] : 'text-gray-500' }}"></i>
                    <span class="truncate font-medium">{{ $item['label'] }}</span>
                    @if($activeTab === $key)
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Divider --}}
        <div class="mx-4 my-3 border-t border-slate-700/50"></div>

        {{-- Navigation Group 2: Billing --}}
        <div class="px-3 pb-3 w-full">
            <p class="text-[10px] font-bold tracking-widest text-gray-500 mb-3 px-2">Billing</p>
            @foreach($navGroup2 as $key => $item)
                <button wire:click="switchTab('{{ $key }}')" @click="mobileSidebarOpen = false" title="{{ $item['label'] }}"
                    class="w-full flex items-center gap-3 py-2.5 px-4 rounded-lg text-[13.5px] mb-1 transition-all duration-150 group
                        {{ $activeTab === $key
                            ? 'bg-blue-600/20 text-white ring-1 ring-blue-500/30'
                            : 'text-gray-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="bi {{ $item['icon'] }} w-5 text-center flex-shrink-0 text-base
                        {{ $activeTab === $key ? $item['color'] : 'text-gray-500' }}"></i>
                    <span class="truncate font-medium">{{ $item['label'] }}</span>
                    @if($activeTab === $key)
                        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Spacer --}}
        <div class="flex-1"></div>

        {{-- Bottom: Subscription Badge --}}
        <div class="px-4 py-4 border-t border-slate-700/60">
            <button wire:click="switchTab('subscription')" @click="mobileSidebarOpen = false"
                class="w-full text-left flex items-center gap-2.5 rounded-lg px-3 py-2.5 transition-all duration-150 group cursor-pointer
                    {{ $activeTab === 'subscription'
                        ? 'bg-blue-600/20 ring-1 ring-blue-500/30'
                        : 'bg-slate-800/60 hover:bg-slate-700/60 hover:ring-1 hover:ring-slate-600/50' }}">
                <i class="bi bi-shield-check text-blue-400 text-base flex-shrink-0"></i>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-white leading-tight group-hover:text-blue-300 transition-colors">{{ $planName ?? 'Basic Plan' }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5 truncate">Expires {{ $expiryText }}</p>
                </div>
                <i class="bi bi-chevron-right text-xs text-gray-500 group-hover:text-white transition-colors ml-auto flex-shrink-0"></i>
            </button>
        </div>
    </aside>

    {{-- ================================================================ --}}
    {{-- MAIN CONTENT PANEL                                                 --}}
    {{-- ================================================================ --}}
    <div class="flex-1 flex flex-col" style="background-color: #0a1628;">

        {{-- Tab Content --}}
        <div class="flex-1">
        <div wire:key="settings-content-{{ $activeTab }}">

            @if($activeTab === 'overview')
                {{-- Overview Dashboard --}}
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- 1. Total Users --}}
                        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-people text-blue-400 text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-semibold">Total Users</p>
                                <p class="text-sm font-medium text-white mt-0.5">{{ $totalUsers }}</p>
                                <p class="text-xs text-gray-400">Onboarded members</p>
                            </div>
                        </div>
                        
                        {{-- 2. Space Used --}}
                        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-database text-blue-400 text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-semibold">Space Used</p>
                                <p class="text-sm font-medium text-white mt-0.5">{{ $spaceUsedMb }}</p>
                                <p class="text-xs text-gray-400">Storage consumed</p>
                            </div>
                        </div>

                        {{-- 3. Last Login --}}
                        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-clock text-blue-400 text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-semibold">Last Login</p>
                                <p class="text-sm font-medium text-white mt-0.5">{{ now()->format('d M Y') }}</p>
                                <p class="text-xs text-gray-400">{{ now()->format('h:i A') }}</p>
                            </div>
                        </div>
                        
                        {{-- 4. Plan --}}
                        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-award text-blue-400 text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-semibold">Active Plan</p>
                                <p class="text-sm font-medium text-white mt-0.5">{{ $planName ?? 'Basic Plan' }}</p>
                                <p class="text-xs text-gray-400">Current subscription</p>
                            </div>
                        </div>

                        {{-- 5. Expiry --}}
                        <div class="bg-slate-800/60 border border-slate-700/60 rounded-xl p-4 flex items-center gap-4 col-span-1 sm:col-span-2 lg:col-span-1">
                            <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-calendar-x text-blue-400 text-lg"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 uppercase tracking-wide font-semibold">Plan Expiry</p>
                                <p class="text-sm font-medium text-white mt-0.5">{{ $expiryText }}</p>
                                <p class="text-xs text-gray-400">Renewal date</p>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($activeTab === 'user')
                <livewire:settings.user key="settings-user" />
            @elseif($activeTab === 'groups')
                <livewire:settings.groups key="settings-groups" />
            @elseif($activeTab === 'tag')
                <livewire:settings.tag key="settings-tag" />
            @elseif($activeTab === 'notes')
                <livewire:settings.notes key="settings-notes" />
            @elseif($activeTab === 'invite')
                <livewire:settings.invite key="settings-invite" />
            @elseif($activeTab === 'import')
                <livewire:settings.import key="settings-import" />
            @elseif($activeTab === 'export')
                <livewire:settings.export key="settings-export" />
            @elseif($activeTab === 'attachment')
                <livewire:settings.attachment key="settings-attachment" />
            @elseif($activeTab === 'history')
                <livewire:settings.history key="settings-history" />
            @elseif($activeTab === 'wallet')
                <livewire:settings.wallet key="settings-wallet" />
            @elseif($activeTab === 'recharge')
                <livewire:settings.recharge key="settings-recharge" />
            @elseif($activeTab === 'subscription')
                <livewire:settings.subscription key="settings-subscription" />
            @endif
        </div>
        </div>{{-- /flex-1 overflow-y-auto --}}
    </div>

</div>
