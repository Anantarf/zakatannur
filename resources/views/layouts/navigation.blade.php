@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $canInputTransaksi = $user?->canInputTransactions() ?? false;
    $isAdmin = $user?->isAdminOrAbove() ?? false;
    $roleLabels = [
        \App\Models\User::ROLE_STAFF => 'Petugas',
        \App\Models\User::ROLE_ADMIN => 'Admin',
        \App\Models\User::ROLE_SUPER_ADMIN => 'Super Admin',
    ];
    $userMeta = $user?->username ? '@' . $user->username : ($roleLabels[$user?->role] ?? 'Pengguna Sistem');
    $isAdminAreaActive = request()->routeIs('internal.users.*')
        || request()->routeIs('internal.audit_logs.*')
        || request()->routeIs('internal.anomalies.*')
        || request()->routeIs('internal.settings.period.*')
        || request()->routeIs('internal.templates.*');
@endphp

<nav x-data="{ open: false }" class="ui-topbar px-4 pt-4 sm:px-6 lg:px-8">
    <div class="ui-topbar-panel">
        <div class="flex flex-1 items-center gap-4">
            <div class="shrink-0">
                <a href="{{ route('dashboard') }}" class="ui-brand-lockup">
                    <x-application-logo class="block h-11 w-auto fill-current text-gray-800" />
                </a>
            </div>

            <div class="hidden flex-1 justify-center xl:flex">
                <div class="flex items-center gap-2 rounded-full border border-emerald-100/70 bg-slate-50/85 p-1.5">
                    @include('layouts.partials.internal-nav-links', ['mobile' => false, 'user' => $user, 'canInputTransaksi' => $canInputTransaksi, 'isAdmin' => $isAdmin])
                    @if ($isAdmin)
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="{{ $isAdminAreaActive ? 'ui-nav-link ui-nav-link-active' : 'ui-nav-link ui-nav-link-inactive' }}">
                                    {{ __('Pengaturan Admin') }}
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="border-b border-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Pengaturan Admin</div>
                                <x-dropdown-link :href="route('internal.users.index')">
                                    {{ __('Manajemen Pengguna') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('internal.audit_logs.index')">
                                    {{ __('Audit Logs') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('internal.anomalies.index')">
                                    {{ __('Review Anomali') }}
                                </x-dropdown-link>

                                @if ($user->isSuperAdmin())
                                    <div class="mt-1 border-y border-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Khusus Super Admin</div>
                                    <x-dropdown-link :href="route('internal.settings.period.edit')">
                                        {{ __('Konfigurasi Periode') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('internal.templates.letterhead')">
                                        {{ __('Template Kop Surat') }}
                                    </x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <div class="hidden items-center sm:flex">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-full border border-emerald-100/80 bg-emerald-50/70 px-3 py-2 text-left shadow-sm transition hover:bg-emerald-50">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-bold text-slate-900">{{ auth()->user()->name }}</div>
                                <div class="truncate text-[11px] font-semibold text-emerald-700">{{ $userMeta }}</div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Akun</div>
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

            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" aria-label="Toggle navigation" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-100 bg-white/90 text-slate-500 shadow-sm transition hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:bg-emerald-50 focus:text-emerald-700">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="mx-auto hidden max-w-7xl pt-3 sm:hidden">
        <div class="rounded-[1.5rem] border border-white/80 bg-white/90 p-3 shadow-[0_18px_55px_-34px_rgba(15,23,42,0.4)] backdrop-blur">
            <div class="mb-3 rounded-2xl border border-emerald-100/80 bg-emerald-50/70 px-4 py-3">
                <div class="font-bold text-slate-900 truncate">{{ auth()->user()->name }}</div>
                <div class="mt-1 text-xs font-semibold text-emerald-700 truncate">{{ $userMeta }}</div>
            </div>

            <div class="space-y-1">
                @include('layouts.partials.internal-nav-links', ['mobile' => true, 'user' => $user, 'canInputTransaksi' => $canInputTransaksi, 'isAdmin' => $isAdmin])
            </div>

            <div class="mt-3 space-y-1 border-t border-slate-100 pt-3">
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
