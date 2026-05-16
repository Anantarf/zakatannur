<nav class="sticky top-0 z-[100] bg-white/90 backdrop-blur-xl border-b border-gray-100 shadow-sm px-4 pt-4">
    <div class="mx-auto max-w-7xl px-2 py-2 relative">
        <div class="flex items-center">
            <div class="flex-1 flex justify-start min-w-[150px] sm:min-w-[200px]">
                <a href="{{ route('home') }}" class="shrink-0">
                    <x-application-logo class="text-slate-900" />
                </a>
            </div>

            <div class="hidden lg:flex items-center bg-slate-100/50 p-1 rounded-xl relative shrink-0">
                <div class="absolute inset-y-1 bg-emerald-500/10 rounded-lg nav-indicator z-0"
                    :style="activeTab === 'beranda' ? 'left: 4px; width: 100px;' : (activeTab === 'laporan' ? 'left: 108px; width: 260px;' : 'left: 372px; width: 240px;')">
                </div>

                <button @click="activeTab = 'beranda'"
                    :class="activeTab === 'beranda' ? 'text-emerald-700' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 rounded-lg text-[12px] font-bold tracking-widest transition-colors duration-200 flex items-center gap-2 relative z-10 w-[100px] justify-center text-center">
                    BERANDA
                </button>
                <button @click="activeTab = 'laporan'"
                    :class="activeTab === 'laporan' ? 'text-emerald-700' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 rounded-lg text-[12px] font-bold tracking-widest transition-colors duration-200 flex items-center gap-2 relative z-10 w-[260px] justify-center text-center">
                    LAPORAN PENERIMAAN ZAKAT
                </button>
                <button @click="activeTab = 'grafik'"
                    :class="activeTab === 'grafik' ? 'text-emerald-700' : 'text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 rounded-lg text-[12px] font-bold tracking-widest transition-colors duration-200 flex items-center gap-2 relative z-10 w-[240px] justify-center text-center">
                    GRAFIK PENERIMAAN ZAKAT
                </button>
            </div>

            <div class="flex-1 flex justify-end min-w-[150px] sm:min-w-[200px]">
                @auth
                    <a href="{{ route('dashboard') }}"
                        :class="activeTab === 'beranda' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                        class="inline-flex items-center justify-center sm:justify-start w-9 h-9 sm:w-auto sm:px-4 sm:py-2 rounded-lg bg-emerald-600 text-white shadow-md shadow-emerald-600/10 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all duration-300 shrink-0" title="MASUK">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-3.5 sm:w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden sm:inline-block ml-2 text-[10px] font-black uppercase tracking-wider">MASUK</span>
                    </a>
                @else
                    <button @click="openLogin = true" type="button"
                        :class="activeTab === 'beranda' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                        class="inline-flex items-center justify-center sm:justify-start w-9 h-9 sm:w-auto sm:px-4 sm:py-2 rounded-lg bg-emerald-600 text-white shadow-md shadow-emerald-600/10 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all duration-300 shrink-0" title="MASUK">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-3.5 sm:w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden sm:inline-block ml-2 text-[10px] font-black uppercase tracking-wider">MASUK</span>
                    </button>
                @endauth
            </div>
        </div>

        <div class="flex lg:hidden items-center justify-center gap-1 mt-2 pb-1 border-t border-slate-50 pt-1">
            <button @click="activeTab = 'beranda'"
                :class="activeTab === 'beranda' ? 'bg-emerald-100 text-emerald-800' : 'text-slate-500'"
                class="flex-1 py-2 rounded-lg text-[10px] font-black tracking-wide transition-all text-center">
                BERANDA
            </button>
            <button @click="activeTab = 'laporan'"
                :class="activeTab === 'laporan' ? 'bg-emerald-100 text-emerald-800' : 'text-slate-500'"
                class="flex-1 py-2 rounded-lg text-[10px] font-black tracking-wide transition-all text-center">
                LAPORAN
            </button>
            <button @click="activeTab = 'grafik'"
                :class="activeTab === 'grafik' ? 'bg-emerald-100 text-emerald-800' : 'text-slate-500'"
                class="flex-1 py-2 rounded-lg text-[10px] font-black tracking-wide transition-all text-center">
                GRAFIK
            </button>
        </div>
    </div>
</nav>
