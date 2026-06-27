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
        :class="scrolled ? 'bg-[#f8fbf9]/95 shadow-md shadow-brand-950/5' : 'bg-[#f8fbf9]/82 shadow-sm shadow-brand-950/5'">
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
                    <span class="text-sm font-bold tracking-wide">Login</span>
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
