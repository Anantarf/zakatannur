@php
    $tabs = [
        ['key' => 'beranda', 'label' => 'Beranda', 'mobile_label' => 'Beranda'],
        ['key' => 'laporan', 'label' => 'Ringkasan Penerimaan', 'mobile_label' => 'Ringkasan', 'title' => 'Ringkasan Penerimaan Zakat'],
        ['key' => 'grafik', 'label' => 'Grafik Harian', 'mobile_label' => 'Grafik', 'title' => 'Grafik Penerimaan Harian'],
    ];
@endphp

<nav x-data="{ scrolled: window.scrollY > 12 }"
    x-init="scrolled = window.scrollY > 12"
    @scroll.window="scrolled = window.scrollY > 12"
    class="sticky top-3 z-[100] px-3 sm:px-4">
    <div class="public-nav-shell"
        :class="scrolled ? 'bg-white/95 shadow-md shadow-slate-900/5' : 'bg-white/75 shadow-sm'">
        <div class="flex items-center justify-between gap-2 lg:grid lg:grid-cols-[minmax(180px,1fr)_auto_minmax(180px,1fr)] lg:items-center lg:gap-2">
            <div class="flex justify-start min-w-0">
                <a href="{{ route('home') }}" class="shrink-0">
                    <x-application-logo class="text-slate-900" />
                </a>
            </div>

            <div class="public-nav-tabs">
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
                        class="public-nav-action" title="MASUK">
                @else
                    <button @click="openLogin = true" type="button"
                        :class="activeTab === 'beranda' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                        class="public-nav-action" title="MASUK">
                @endauth
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-3.5 sm:w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span class="ml-2 hidden text-[10px] font-semibold tracking-[0.04em] sm:inline-block">Masuk</span>
                @auth
                    </a>
                @else
                    </button>
                @endauth
            </div>
        </div>

        <div class="public-nav-mobile-tabs">
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
