@php
    $tabs = [
        ['key' => 'beranda', 'label' => 'BERANDA', 'mobile_label' => 'BERANDA'],
        ['key' => 'laporan', 'label' => 'RINGKASAN PENERIMAAN', 'mobile_label' => 'RINGKASAN', 'title' => 'Ringkasan Penerimaan Zakat'],
        ['key' => 'grafik', 'label' => 'GRAFIK HARIAN', 'mobile_label' => 'GRAFIK', 'title' => 'Grafik Penerimaan Harian'],
    ];
@endphp

<nav class="relative z-[100] px-3 sm:px-4">
    <div class="mx-auto max-w-7xl rounded-[1.5rem] border border-white/[0.85] bg-white/[0.85] px-3 py-2.5 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-900/5 backdrop-blur-xl relative sm:px-4">
        <div class="grid grid-cols-[minmax(132px,1fr)_auto_minmax(132px,1fr)] items-center gap-2 sm:grid-cols-[minmax(180px,1fr)_auto_minmax(180px,1fr)] sm:gap-3">
            <div class="flex justify-start min-w-0">
                <a href="{{ route('home') }}" class="shrink-0">
                    <x-application-logo class="text-slate-900" />
                </a>
            </div>

            <div class="hidden lg:flex items-center gap-1 rounded-xl bg-emerald-950/[0.035] p-1 shrink-0">
                @foreach ($tabs as $tab)
                    <button @click="activeTab = '{{ $tab['key'] }}'"
                        :class="activeTab === '{{ $tab['key'] }}' ? 'public-tab-chip-active' : 'public-tab-chip-inactive'"
                        class="public-tab-chip"
                        @if(isset($tab['title'])) title="{{ $tab['title'] }}" @endif>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="flex justify-end min-w-0">
                @auth
                    <a href="{{ route('dashboard') }}"
                        :class="activeTab === 'beranda' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                        class="inline-flex items-center justify-center sm:justify-start h-9 w-9 rounded-xl bg-emerald-700 text-white shadow-md shadow-emerald-700/[0.15] transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-800 shrink-0 sm:w-auto sm:px-4 sm:py-2" title="MASUK">
                @else
                    <button @click="openLogin = true" type="button"
                        :class="activeTab === 'beranda' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                        class="inline-flex items-center justify-center sm:justify-start h-9 w-9 rounded-xl bg-emerald-700 text-white shadow-md shadow-emerald-700/[0.15] transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-800 shrink-0 sm:w-auto sm:px-4 sm:py-2" title="MASUK">
                @endauth
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-3.5 sm:w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden sm:inline-block ml-2 text-[10px] font-black uppercase tracking-wider">MASUK</span>
                @auth
                    </a>
                @else
                    </button>
                @endauth
            </div>
        </div>

        <div class="mt-2 flex items-center justify-center gap-1 border-t border-emerald-900/5 pt-2 lg:hidden">
            @foreach ($tabs as $tab)
                <button @click="activeTab = '{{ $tab['key'] }}'"
                    :class="activeTab === '{{ $tab['key'] }}' ? 'public-tab-chip-mobile-active' : 'public-tab-chip-mobile-inactive'"
                    class="public-tab-chip-mobile">
                    {{ $tab['mobile_label'] }}
                </button>
            @endforeach
        </div>
    </div>
</nav>
