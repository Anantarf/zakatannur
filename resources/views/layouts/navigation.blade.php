@php use Illuminate\Support\Facades\View; @endphp
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 pt-4">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @php
                        /** @var \App\Models\User|null $user */
                        $user = auth()->user();
                        $canInputTransaksi = $user?->canInputTransactions() ?? false;
                    @endphp

                    @if ($canInputTransaksi)
                        <x-nav-link :href="route('internal.muzakki.index')" :active="request()->routeIs('internal.muzakki.*')">
                            {{ __('Muzakki') }}
                        </x-nav-link>
                        <x-nav-link :href="route('internal.transactions.index')" :active="request()->routeIs('internal.transactions.index') || request()->routeIs('internal.transactions.trash')">
                            {{ __('Riwayat Transaksi') }}
                        </x-nav-link>
                        <x-nav-link :href="route('internal.transactions.create')" :active="request()->routeIs('internal.transactions.create')">
                            {{ __('Input Transaksi') }}
                        </x-nav-link>

                        <x-nav-link :href="route('internal.mustahik.index')" :active="request()->routeIs('internal.mustahik.*')">
                            {{ __('Mustahik') }}
                        </x-nav-link>
                    @endif

                    @php
                        $isAdmin = $user?->isAdminOrAbove() ?? false;
                    @endphp

                    @if ($isAdmin)
                        <div class="hidden sm:flex sm:items-center sm:ml-4">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center text-xs font-black tracking-widest text-slate-400 hover:text-emerald-600 transition-colors uppercase gap-1 h-full py-6">
                                        ADMIN ONLY
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="px-4 py-2 border-b border-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sistem & Pengguna</div>
                                    <x-dropdown-link :href="route('internal.users.index')">
                                        {{ __('Manajemen Pengguna') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('internal.audit_logs.index')">
                                        {{ __('Audit Logs') }}
                                    </x-dropdown-link>

                                    @if ($user->isSuperAdmin())
                                        <div class="px-4 py-2 border-t border-b border-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Super Admin Only</div>
                                        <x-dropdown-link :href="route('internal.settings.period.edit')">
                                            {{ __('Konfigurasi Periode') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('internal.templates.letterhead')">
                                            {{ __('Template Kop Surat') }}
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-4 py-2 border border-emerald-100 text-sm leading-4 font-bold rounded-full text-emerald-700 bg-emerald-50 hover:bg-emerald-100 focus:outline-none transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <div>{{ auth()->user()->name }}</div>

                            <div class="ml-1.5 opacity-70">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('internal.profile.edit')">
                            {{ __('Pengaturan Akun') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}" x-data>
                            @csrf
                            <x-dropdown-link :href="route('logout')" @click.prevent="$el.closest('form').submit()">
                                {{ __('Keluar') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" aria-label="Toggle navigation" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @php
                /** @var \App\Models\User|null $user */
                $user = auth()->user();
                $canInputTransaksi = $user?->canInputTransactions() ?? false;
            @endphp

            @if ($canInputTransaksi)
                <x-responsive-nav-link :href="route('internal.muzakki.index')" :active="request()->routeIs('internal.muzakki.*')">
                    {{ __('Muzakki') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('internal.transactions.index')" :active="request()->routeIs('internal.transactions.index') || request()->routeIs('internal.transactions.trash')">
                    {{ __('Riwayat Transaksi') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('internal.transactions.create')" :active="request()->routeIs('internal.transactions.create')">
                    {{ __('Input Transaksi') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('internal.mustahik.index')" :active="request()->routeIs('internal.mustahik.*')">
                    {{ __('Mustahik') }}
                </x-responsive-nav-link>
            @endif

            @if ($isAdmin)
                <div class="pt-4 pb-1 border-t border-gray-100 bg-slate-50/50">
                    <div class="px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Admin Only</div>
                    <x-responsive-nav-link :href="route('internal.users.index')" :active="request()->routeIs('internal.users.*')">
                        {{ __('Manajemen Pengguna') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('internal.audit_logs.index')" :active="request()->routeIs('internal.audit_logs.index')">
                        {{ __('Audit Logs') }}
                    </x-responsive-nav-link>

                    @if ($user->isSuperAdmin())
                        <x-responsive-nav-link :href="route('internal.settings.period.edit')" :active="request()->routeIs('internal.settings.period.*')">
                            {{ __('Konfigurasi Periode') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('internal.templates.letterhead')" :active="request()->routeIs('internal.templates.*')">
                            {{ __('Template Kop Surat') }}
                        </x-responsive-nav-link>
                    @endif
                </div>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 truncate">{{ auth()->user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 truncate">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('internal.profile.edit')">
                    {{ __('Pengaturan Akun') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" @click.prevent="$el.closest('form').submit()">
                        {{ __('Keluar') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
