<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Contacts' }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}?v=1.0">
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.5.0/css/flag-icons.min.css"
        integrity="sha512-+WVTaUIzUw5LFzqIqXOT3JVAc5SrMuvHm230I9QAZa6s+QRk8NDPswbHo2miIZj3yiFyV9lAgzO1wVrjdoO4tw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    @livewireStyles


    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="font-sans antialiased bg-[#021420]" x-data="{ globalSidebarOpen: true, mobileSidebarOpen: false }">

    <!-- ======================================= -->
    <!-- 0. Main Wrapper                         -->
    <!-- ======================================= -->
    <div class="min-h-screen flex flex-col">
        @php
        $authenticatedUserUIN = session('authenticated_user_uin');
        $userOrganizations = collect();

        if ($authenticatedUserUIN) {
        $userOrganizations = DB::table('tbl_tran_user_assc')
        ->join('admn_orga_mast', 'tbl_tran_user_assc.Orga_UIN', '=', 'admn_orga_mast.Orga_UIN')
        ->where('tbl_tran_user_assc.User_UIN', $authenticatedUserUIN)
        ->where('tbl_tran_user_assc.Stau_UIN', 100201)
        ->select('admn_orga_mast.Orga_UIN', 'admn_orga_mast.Orga_Name')
        ->distinct()
        ->orderBy('admn_orga_mast.Orga_Name', 'asc')
        ->get();
        }
        $currentUser = null;
        if ($authenticatedUserUIN) {
        $currentUser = DB::table('admn_user_logi_mast')
        ->where('User_UIN', $authenticatedUserUIN)
        ->first();
        }
        $selectedOrgaUIN = session('selected_Orga_UIN');
        $organizationName = 'Select Organization';

        if ($selectedOrgaUIN) {
        $org = DB::table('admn_orga_mast')->where('Orga_UIN', $selectedOrgaUIN)->first();
        if ($org) {
        $organizationName = $org->Orga_Name;
        }
        }
        @endphp

        <!-- ======================================= -->
        <!-- 1. HEADER                               -->
        <!-- ======================================= -->
        <header class="relative z-[60] flex justify-between items-center px-4 py-2 border-b border-slate-700/60 bg-[#0a1628]">
            <!-- Left side - Current Organization Display (Read-only) -->
            <div class="flex items-center gap-3">
                @if(request()->routeIs('settings.*'))
                <button @click="globalSidebarOpen = !globalSidebarOpen; mobileSidebarOpen = !mobileSidebarOpen" class="text-gray-300 hover:text-white focus:outline-none p-1 rounded-md hover:bg-slate-700/50 transition-colors">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                @endif
                <div class="text-lg text-gray-200 font-semibold truncate flex flex-col">
                    <span>{{ $organizationName }}</span>
                    @if ($currentUser)
                    <span
                        class="text-sm text-gray-300 truncate italic font-thin">{{ $currentUser->User_Name ?? 'User' }}</span>
                    @else
                    <span>Not Authenticated</span>
                    @endif
                </div>
            </div>

            <!-- Right side - Menu -->
            <div class="flex items-center space-x-4" x-data="{ showMenu: false, showModal: false }">


                <!-- Three-line Menu with Alpine.js -->
                <div class="relative">
                    <!-- Menu Button -->
                    <div class="flex items-center gap-2">
                        <button @click="showMenu = !showMenu"
                            class="p-2 rounded-full hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="bi bi-three-dots-vertical text-gray-300 text-lg"></i>
                        </button>
                    </div>

                    <!-- Dropdown Menu -->
                    <div x-show="showMenu" x-transition @click.away="showMenu = false"
                        class="absolute top-full right-0 mt-2 bg-slate-800 border border-slate-600 rounded-md shadow-lg z-50 min-w-[200px]"
                        style="display: none;">
                        <a href="{{ config('services.hr_app_url') }}/dashboard"
                            class="w-full text-left px-4 py-2 text-gray-200 hover:bg-slate-700 flex items-center space-x-2">
                            <i class="bi bi-house"></i>
                            <span>Home</span>
                        </a>
                        @if(\App\Traits\ManagesSettings::isAdmin())
                        <button type="button"
                            x-on:click="$dispatch('open-settings-modal')"
                            class="w-full text-left px-4 py-2 text-gray-200 hover:bg-slate-700 flex items-center space-x-2">
                            <i class="bi bi-gear"></i>
                            <span>Setting</span>
                        </button>
                        @endif
                        <button @click="showModal = true; showMenu = false"
                            class="w-full text-left px-4 py-2 text-gray-200 hover:bg-slate-700 flex items-center space-x-2">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Change</span>
                        </button>
                        <a href="https://partakers.in/dashboard"
                            class="w-full text-left px-4 py-2 text-gray-200 hover:bg-slate-700 flex items-center space-x-2">
                            <i class="bi bi-arrow-left"></i>
                            <span>Main</span>
                        </a>
                        <!-- EXIT BUTTON -->
                        <button @click="handleExit()"
                            class="w-full text-left px-4 py-2 text-red-400 hover:bg-red-500/20 hover:text-red-300 flex items-center space-x-2 transition-colors duration-150">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Exit</span>
                        </button>
                    </div>
                </div>

                <!-- Organization Selection Modal -->
                <div x-show="showModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
                    style="display: none;">
                    <div @click.away="showModal = false"
                        class="bg-slate-800 border border-slate-600 rounded-md shadow-xl p-6 w-full max-w-md">
                        <h3 class="text-lg font-semibold text-gray-200 mb-4">Select Organization</h3>
                        <form method="POST" action="{{ route('organization.switch') }}">
                            @csrf
                            <div class="space-y-3 max-h-80 overflow-y-auto mb-6 pr-2">
                                @forelse ($userOrganizations as $org)
                                <label
                                    class="flex items-center space-x-3 p-3 hover:bg-slate-700/50 border border-slate-700 rounded-md cursor-pointer transition-colors duration-200">
                                    <input type="radio" name="orga_uin" value="{{ $org->Orga_UIN }}"
                                        {{ $selectedOrgaUIN == $org->Orga_UIN ? 'checked' : '' }}
                                        class="h-4 w-4 text-blue-500 bg-slate-600 border-slate-500 focus:ring-blue-500">
                                    <span class="text-gray-200">{{ $org->Orga_Name }}</span>
                                </label>
                                @empty
                                <p class="text-gray-400 text-center py-4">No organizations available.</p>
                                @endforelse
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2 bg-gray-600 text-gray-200 rounded-md hover:bg-gray-500 transition-colors">Cancel</button>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500 transition-colors">Switch</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div x-data="{ show: false }"
            x-on:open-settings-modal.window="show = true"
            x-on:keydown.escape.window="show = false">

            <template x-teleport="body">
                <div x-show="show" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                    x-on:click.self="show = false">

                    <div role="dialog" aria-modal="true"
                        class="w-full max-w-3xl rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-xl">

                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-white">Choose settings</h2>
                                <p class="text-sm text-gray-400">Which application do you want to configure?</p>
                            </div>
                            <button type="button" x-on:click="show = false"
                                class="text-gray-300 hover:text-white" aria-label="Close">
                                <i class="ri-close-line text-2xl"></i>
                            </button>
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <a href="{{ config('services.hr_app_url') }}/settings"
                                class="flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 p-8 text-center transition hover:border-primary hover:bg-white/5">
                                <i class="bi bi-person text-5xl text-white"></i>
                                <span class="text-xl font-semibold text-white">HR Settings</span>
                                <span class="text-xs text-gray-400">Offices, designations, interview steps</span>
                            </a>

                            <a href="{{ route('settings.index') }}"
                                class="flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 p-8 text-center transition hover:border-primary hover:bg-white/5">
                                <i class="bi bi-journal-text text-5xl text-white"></i>
                                <span class="text-xl font-semibold text-white">Contact App Settings</span>
                                <span class="text-xs text-gray-400">Contact application configuration</span>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-data="{ show: false }"
            x-on:open-home-modal.window="show = true"
            x-on:keydown.escape.window="show = false">

            <template x-teleport="body">
                <div x-show="show" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                    x-on:click.self="show = false">

                    <div role="dialog" aria-modal="true"
                        class="w-full max-w-3xl rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-xl">

                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-white">Choose home</h2>
                                <p class="text-sm text-gray-400">Which application do you want to open?</p>
                            </div>
                            <button type="button" x-on:click="show = false"
                                class="text-gray-300 hover:text-white" aria-label="Close">
                                <i class="ri-close-line text-2xl"></i>
                            </button>
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <a href="{{ config('services.hr_app_url') }}/dashboard"
                                class="flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 p-8 text-center transition hover:border-primary hover:bg-white/5">
                                <i class="bi bi-person text-5xl text-white"></i>
                                <span class="text-xl font-semibold text-white">HR Home</span>
                                <span class="text-xs text-gray-400">Talent management dashboard</span>
                            </a>

                            <a href="{{ route('contacts.index') }}"
                                class="flex flex-col items-center justify-center gap-2 rounded-xl border border-white/10 p-8 text-center transition hover:border-primary hover:bg-white/5">
                                <i class="bi bi-journal-text text-5xl text-white"></i>
                                <span class="text-xl font-semibold text-white">Contact App Home</span>
                                <span class="text-xs text-gray-400">Contacts dashboard</span>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Subscription and Plan Alerts -->
        @include('components.plan-notification')

        <!-- Flash Messages & Slot (No changes here) -->
        @if (session('success'))
        <div class="max-w-7xl mx-auto px-4 py-2">
            <div
                class="bg-green-800/50 border border-green-600 text-green-300 px-4 py-3 rounded relative flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.parentElement.remove()"
                    class="text-green-300 hover:text-green-100"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        @endif
        @if (session('error'))
        <div class="max-w-7xl mx-auto px-4 py-2">
            <div
                class="bg-red-800/50 border border-red-600 text-red-300 px-4 py-3 rounded relative flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.parentElement.remove()"
                    class="text-red-300 hover:text-red-100"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        @endif
        {{ $slot }}
        </main>

        @livewireScripts
        @stack('scripts')


        <script>
            function handleExit() {
                if (confirm('Are you sure you want to exit and end your session?')) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    const route = "/api/auth/logout";
                    fetch(route, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                sessionStorage.clear();
                                localStorage.clear();

                                // Try to close
                                window.open('', '_self');
                                window.close();

                                // Fallback to blank page if close didn't work
                                setTimeout(() => {
                                    window.location.replace('about:blank');
                                }, 100);
                            } else {
                                alert('Logout failed. Please try again.');
                            }

                        })
                        .catch(error => {
                            console.error('Logout error:', error);
                            alert('An error occurred during logout. Please check the console and try again.');
                        });
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Your existing script to trim text inputs
                const textInputs = document.querySelectorAll('input[type="text"]');
                textInputs.forEach(function(input) {
                    input.addEventListener('blur', function(event) {
                        const element = event.target;
                        element.value = element.value.trim();
                    });
                });
            });
        </script>
        <script>
            // Surface failed Livewire requests (500/419/etc.) and JS errors in the browser console
            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({
                    fail
                }) => {
                    fail(({
                        status,
                        content
                    }) => {
                        console.error('[Livewire request failed]', status, typeof content === 'string' ? content.substring(0, 2000) : content);
                    });
                });
            });
            window.addEventListener('error', (e) => console.error('[JS error]', e.message, e.filename + ':' + e.lineno));
            window.addEventListener('unhandledrejection', (e) => console.error('[Unhandled promise rejection]', e.reason));
        </script>
</body>

</html>