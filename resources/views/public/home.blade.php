<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($brand); ?></title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/logo_zakatannur.png')); ?>">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/7.0.3/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>
    <script>
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '<?php echo e(config('broadcasting.connections.pusher.key')); ?>',
            cluster: '<?php echo e(config('broadcasting.connections.pusher.options.cluster')); ?>',
            forceTLS: true
        });
    </script>
    <style>
        .marquee-container {
            width: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        .marquee-track {
            display: flex;
            width: max-content;
            animation: marquee 90s linear infinite;
        }
        @keyframes marquee {
            to { transform: translateX(-50%); }
        }
        [x-cloak] { display: none !important; }
        .nav-indicator {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .footer-glow {
            color: #38bdf8 !important;
            text-shadow: 0 0 12px rgba(56, 189, 248, 0.4);
            transition: all 0.3s ease;
        }
        .footer-item-group:hover .footer-glow {
            color: #7dd3fc !important;
            text-shadow: 0 0 20px rgba(56, 189, 248, 0.7);
            transform: translateY(-1px);
        }
        .footer-logo {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .footer-item-group:hover .footer-logo {
            filter: brightness(1.1) drop-shadow(0 5px 4px rgba(0, 0, 0, 0.4));
            transform: translateY(-3px) scale(1.1);
        }

        @keyframes shine-text {
            0% { background-position: 200% 0; }
            23.33% { background-position: -200% 0; }
            100% { background-position: -200% 0; }
        }
        .animate-shine-text {
            background: linear-gradient(110deg, #38bdf8 48%, #f1f5f9 50%, #38bdf8 52%);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shine-text 30s linear infinite;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .footer-item-group:hover .animate-shine-text {
            background: linear-gradient(110deg, #7dd3fc 48%, #f0f9ff 50%, #7dd3fc 52%);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 15px rgba(56, 189, 248, 0.4);
            transform: translateY(-1px);
        }
    </style>
<script>
window.zakatApp = () => ({
    openLogin: <?php echo json_encode($errors->any() || request()->has('login'), 15, 512) ?>,
    activeTab: 'beranda',
    items: <?php echo json_encode($summaryData['items'] ?? []); ?>,
    totals: <?php echo json_encode($summaryData['totals'] ?? []); ?>,
    dailyChartData: <?php echo json_encode($dailyChartData ?? []); ?>,
    historicalChartData: <?php echo json_encode($historicalChartData ?? []); ?>,
    isFirstLoad: true,
    lastRowValues: {},
    error: null,
    lastFetchTime: 0,
    idleTimeout: null,
    isIdleMode: false,
    notification: {
        show: false,
        message: '',
        queue: [],
        processing: false
    },
    clock: '',
    updateClock() {
        const options = { timeZone: 'Asia/Jakarta', weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const formatter = new Intl.DateTimeFormat('id-ID', options);
        const parts = formatter.formatToParts(new Date());
        const d = parts.reduce((acc, part) => ({ ...acc, [part.type]: part.value }), {});
        this.clock = `${d.weekday}, ${d.day} ${d.month} ${d.year} ${d.hour}:${d.minute}:${d.second} WIB`;
    },
    chartTimeouts: [],
    clearChartTimeouts() {
        this.chartTimeouts.forEach(t => clearTimeout(t));
        this.chartTimeouts = [];
        if (window.chartScanTimeout) {
            clearTimeout(window.chartScanTimeout);
            window.chartScanTimeout = null;
        }
        const container = document.getElementById('idle-cards-container');
        if (container) container.innerHTML = '';
    },
    resetIdle() {
        clearTimeout(this.idleTimeout);
        this.clearChartTimeouts();
        if (window.chartScanTimeout) {
            clearTimeout(window.chartScanTimeout);
            window.chartScanTimeout = null;
        }
        
        if (this.activeTab === 'grafik' && window.myDailyChart) {
            window.myDailyChart.setActiveElements([]);
            window.myDailyChart.tooltip.setActiveElements([], {x: 0, y: 0});
            window.myDailyChart.update('none');
        }

        this.isIdleMode = false;
        if (!this.openLogin) {
            this.idleTimeout = setTimeout(() => {
                this.isIdleMode = true;
            }, 120000);
        }
    },
    runChartScan() {
        const chart = window.myDailyChart;
        if (!chart) return;
        this.clearChartTimeouts();
        if (window.chartScanTimeout) clearTimeout(window.chartScanTimeout);
        
        const container = document.getElementById('idle-cards-container');
        if (container) container.innerHTML = '';

        const datasets = chart.data.datasets;
        const labels = chart.data.labels;
        
        // Find indices with real data (skip days with zero/null perolehan)
        const validIndices = [];
        for (let i = 0; i < labels.length; i++) {
            const uang = datasets[0].data[i];
            const beras = datasets[1].data[i];
            // Only show cards for days that have started receiving (skip nulls and zeros)
            if (uang !== null && beras !== null && (uang > 0 || beras > 0)) {
                validIndices.push(i);
            }
        }

        if (validIndices.length === 0) {
            this.chartTimeouts.push(setTimeout(() => {
                if (this.isIdleMode) {
                    this.activeTab = 'laporan';
                    this.resetIdle();
                }
            }, 5000));
            return;
        }

        const meta0 = chart.getDatasetMeta(0);
        let currentIndex = 0;

        const showStep = () => {
            if (!this.isIdleMode || currentIndex >= validIndices.length) {
                this.chartTimeouts.push(setTimeout(() => {
                    if (this.isIdleMode) {
                        this.activeTab = 'laporan';
                        this.resetIdle();
                    }
                }, 5000));
                return;
            }

            const i = validIndices[currentIndex];
            
            // Sync Chart Tooltip & Crosshair (This is the "old card" / native tooltip)
            const activeElements = [
                {datasetIndex: 0, index: i},
                {datasetIndex: 1, index: i}
            ];
            chart.setActiveElements(activeElements);
            chart.tooltip.setActiveElements(activeElements, {
                x: meta0.data[i].x,
                y: meta0.data[i].y
            });
            chart.update('none');

            // Native tooltip is shown for 5 seconds
            this.chartTimeouts.push(setTimeout(() => {
                // Clear tooltip/crosshair during gap
                chart.setActiveElements([]);
                chart.tooltip.setActiveElements([], {x: 0, y: 0});
                chart.update('none');

                // 2 seconds gap before next point
                this.chartTimeouts.push(setTimeout(() => {
                    currentIndex++;
                    showStep();
                }, 2000));
            }, 5000));
        };

        showStep();
    },
    startIdleCycle() {
        if (!this.isIdleMode || this.openLogin) return;
        if (this.activeTab === 'beranda' || this.activeTab === 'laporan') {
            this.activeTab = 'grafik';
            setTimeout(() => this.runChartScan(), 1500);
        } else if (this.activeTab === 'grafik') {
            this.runChartScan();
        }
    },
    async refreshSummary() {
        if (document.visibilityState !== 'visible') return;
        const now = Date.now();
        if (now - this.lastFetchTime < 1000) return;
        this.lastFetchTime = now;
        this.error = null;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000);

        try {
            const dt = new Date();
            const year = dt.getFullYear();
            const res = await fetch('/api/public/summary?year=' + encodeURIComponent(year), {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            if (!res.ok) {
                const body = await res.json().catch(() => null);
                this.error = body?.message || 'Gagal memuat rekap.';
                return;
            }
            const json = await res.json();
            const data = json.data || { items: [] };
            
            // Sync Data
            const oldTotals = { ...this.totals };
            this.items = data.items || [];
            this.totals = data.totals || {};
            
            if (data.dailyChartData && typeof window.updateDailyChart === 'function') {
                window.updateDailyChart(data.dailyChartData);
            }

            // Trigger animations after DOM updates (nextTick)
            this.$nextTick(() => {
                this.animateSummaryCards(oldTotals, this.totals);
                this.isFirstLoad = false;
            });

        } catch (e) {
            clearTimeout(timeoutId);
            this.error = e.name === 'AbortError' ? 'Koneksi lambat.' : 'Gagal menghubungi server.';
        }
    },
    animateSummaryCards(oldT, newT) {
        if (this.isFirstLoad) return;
        const animate = (id, start, end, type) => {
            const el = document.getElementById(id);
            if (el && end > start && typeof window.animateValue === 'function') {
                window.animateValue(el, start, end, 2000, type);
            }
        };
        animate('live-total-uang', oldT.total_uang || 0, newT.total_uang || 0, 'uang');
        animate('live-total-beras', oldT.total_beras_kg || 0, newT.total_beras_kg || 0, 'beras');
        
        // Tab Laporan Totals
        animate('totalUang', oldT.total_uang || 0, newT.total_uang || 0, 'uang');
        animate('totalBeras', oldT.total_beras_kg || 0, newT.total_beras_kg || 0, 'beras');
        animate('totalJiwa', oldT.total_jiwa || 0, newT.total_jiwa || 0, 'jiwa');
    },
    formatCat(c) {
        const labels = { fitrah: 'Zakat Fitrah', fidyah: 'Fidyah', mal: 'Zakat Mal', infaq: 'Infaq Shodaqoh' };
        return labels[c] || c;
    },
    joinGrammatically(items) {
        if (items.length === 0) return '';
        if (items.length === 1) return items[0];
        if (items.length === 2) return items[0] + ' dan ' + items[1];
        return items.slice(0, -1).join(', ') + ', dan ' + items.slice(-1);
    },
    processQueue() {
        if (this.notification.show || this.notification.processing || this.notification.queue.length === 0) return;
        this.notification.processing = true;
        const nextMsg = this.notification.queue.shift();
        setTimeout(() => {
            this.notification.message = nextMsg;
            this.notification.show = true;
            setTimeout(() => {
                this.notification.show = false;
                this.notification.processing = false;
                setTimeout(() => this.processQueue(), 1000);
            }, 7000);
        }, 1000);
    },
    carouselIndex: 0,
    carouselImages: [
        '/images/beranda_annur_new.webp',
        '/images/dokumentasi_1.webp'
    ],
    async pollLatest() {
        try {
            const res = await fetch('/api/public/latest', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const json = await res.json();
            const items = json.data || [];
            if (items.length === 0) return;

            let seenIds = JSON.parse(localStorage.getItem('seen_tx_ids') || '[]');
            const newItems = items.filter(it => !seenIds.includes(it.id));
            
            if (newItems.length > 0) {
                seenIds = [...new Set([...seenIds, ...newItems.map(it => it.id)])].slice(-50);
                localStorage.setItem('seen_tx_ids', JSON.stringify(seenIds));
                
                const cats = [...new Set(newItems.map(it => this.formatCat(it.category)))];
                let labelSummary = this.joinGrammatically(cats);
                const sumUang = newItems.reduce((sum, it) => sum + (it.uang || 0), 0);
                const sumBeras = newItems.reduce((sum, it) => sum + (it.beras || 0), 0);
                
                let parts = [];
                if (sumUang > 0) parts.push('Rp ' + sumUang.toLocaleString('id-ID'));
                if (sumBeras > 0) parts.push(sumBeras.toFixed(2).replace('.', ',') + ' Kg');
                
                this.notification.queue.push(`Alhamdulilah! Diperoleh ${labelSummary}: ${parts.join(' dan ')}`);
                this.processQueue();
            }
        } catch (e) { /* silent fail for background poll */ }
    },
    async loadChartJs() {
        if (typeof Chart !== 'undefined') return true;
        if (this._loadingChart) return false;
        this._loadingChart = true;
        return new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => {
                this._loadingChart = false;
                resolve(true);
            };
            script.onerror = () => {
                this._loadingChart = false;
                resolve(false);
            };
            document.head.appendChild(script);
        });
    },
    init() {
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);
        
        const refreshSecs = <?php echo (int) $refreshIntervalSeconds; ?>;
        if (refreshSecs > 0) {
            setInterval(() => {
                this.refreshSummary();
                this.pollLatest();
            }, refreshSecs * 1000);
        }
        
        this.pollLatest();
        this.refreshSummary();
        setInterval(() => this.carouselIndex = (this.carouselIndex + 1) % this.carouselImages.length, 7000);
        ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(e => {
            window.addEventListener(e, () => this.resetIdle(), { passive: true });
        });
        this.resetIdle();
        this.$watch('openLogin', (val) => {
            if (val) {
                clearTimeout(this.idleTimeout);
                this.isIdleMode = false;
            } else {
                this.resetIdle();
            }
        });
        this.$watch('isIdleMode', (val) => {
            if (val) this.startIdleCycle();
        });
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('public-transactions').listen('.transaction.created', (e) => {
                const items = e.items || [];
                if (items.length === 0) return;
                const now = Date.now();
                if (now - this.lastFetchTime > 2000) {
                    this.refreshSummary();
                }
                let seenIds = JSON.parse(localStorage.getItem('seen_tx_ids') || '[]');
                seenIds = [...new Set([...seenIds, ...items.map(it => it.id)])].slice(-50);
                localStorage.setItem('seen_tx_ids', JSON.stringify(seenIds));
                const cats = [...new Set(items.map(it => this.formatCat(it.category)))];
                let labelSummary = this.joinGrammatically(cats);
                const sumUang = items.reduce((sum, it) => sum + (it.uang || 0), 0);
                const sumBeras = items.reduce((sum, it) => sum + (it.beras || 0), 0);
                let parts = [];
                if (sumUang > 0) parts.push('Rp ' + sumUang.toLocaleString('id-ID'));
                if (sumBeras > 0) parts.push(sumBeras.toFixed(2).replace('.', ',') + ' Kg');
                this.notification.queue.push(`Alhamdulilah! Diperoleh ${labelSummary}: ${parts.join(' dan ')}`);
                this.processQueue();
            });
        }
        this.$watch('activeTab', (val) => {
            if (window.chartScanTimeout) {
                clearTimeout(window.chartScanTimeout);
                window.chartScanTimeout = null;
            }

            if (val === 'grafik') {
                window.scrollTo({ top: 0, behavior: 'instant' });
                this.loadChartJs().then(success => {
                    if (success && typeof window.initCharts === 'function') {
                        window.initCharts();
                    }
                });
            }
        });
    }
});
</script>
</head>
<body class="pb-20 sm:pb-24 min-h-screen bg-slate-100 text-slate-800 flex flex-col font-sans antialiased relative" 
    x-data="zakatApp()" 
    :class="{ 'overflow-hidden': openLogin }">
    <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/10 via-transparent to-emerald-500/10 pointer-events-none"></div>

    
    <div x-show="notification.show" 
         x-cloak
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-y-10 opacity-0 scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-300 transform"
         x-transition:leave-start="translate-y-0 opacity-100 scale-100"
         x-transition:leave-end="-translate-y-10 opacity-0 scale-95"
         class="fixed top-20 right-4 sm:right-6 lg:right-8 z-[60] w-[90%] sm:w-auto max-w-sm origin-top-right">
        <div class="bg-emerald-600 text-white px-5 py-4 rounded-xl shadow-[0_15px_40px_rgba(16,185,129,0.3)] border border-emerald-500/50 flex flex-col gap-1 backdrop-blur-sm bg-opacity-95">
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm animate-bounce mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-[12px] font-extrabold tracking-widest text-emerald-100 uppercase opacity-90">Penerimaan Baru</p>
                    <p class="text-[15px] sm:text-base font-bold leading-normal mt-0.5" x-text="notification.message"></p>
                </div>
            </div>
        </div>
    </div>

    
    <?php echo $__env->make('public._login_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <nav class="sticky top-0 z-[100] bg-white/90 backdrop-blur-xl border-b border-gray-100 shadow-sm px-4 pt-4">
        <div class="mx-auto max-w-7xl px-2 py-2 relative">
            <div class="flex items-center">
                
                <div class="flex-1 flex justify-start min-w-[150px] sm:min-w-[200px]">
                    <a href="<?php echo e(route('home')); ?>" class="shrink-0">
                        <?php if (isset($component)) { $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4 = $component; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'text-slate-900']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-slate-900']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4)): ?>
<?php $component = $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4; ?>
<? unset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4); ?>
<?php endif; ?>
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
                    <?php if(auth()->guard()->check()): ?>
                        <a href="<?php echo e(route('dashboard')); ?>" 
                           :class="activeTab === 'beranda' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                           class="inline-flex items-center justify-center sm:justify-start w-9 h-9 sm:w-auto sm:px-4 sm:py-2 rounded-lg bg-emerald-600 text-white shadow-md shadow-emerald-600/10 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all duration-300 shrink-0" title="MASUK">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-3.5 sm:w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span class="hidden sm:inline-block ml-2 text-[10px] font-black uppercase tracking-wider">MASUK</span>
                        </a>
                    <?php else: ?>
                        <button @click="openLogin = true" type="button" 
                                :class="activeTab === 'beranda' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                class="inline-flex items-center justify-center sm:justify-start w-9 h-9 sm:w-auto sm:px-4 sm:py-2 rounded-lg bg-emerald-600 text-white shadow-md shadow-emerald-600/10 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all duration-300 shrink-0" title="MASUK">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-3.5 sm:w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span class="hidden sm:inline-block ml-2 text-[10px] font-black uppercase tracking-wider">MASUK</span>
                        </button>
                    <?php endif; ?>
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

    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div x-show="activeTab !== 'grafik'" x-collapse.duration.500ms>
            <header class="text-center py-3 sm:py-5">
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 leading-snug sm:leading-tight">
                    Sistem Informasi Penerimaan Zakat<br>
                    <span class="text-emerald-700">Masjid An-Nur</span>
                    <span class="text-[12px] sm:text-[14.5px] text-slate-600 font-black mt-0.5 block tracking-[0.4em] uppercase">Komplek BPK V Gandul</span>
                </h1>
            </header>
        </div>

        <div class="transition-all duration-500 mb-6 relative z-10">
             <div>
            <div x-show="activeTab === 'beranda'" 
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-6 p-3 sm:p-5 lg:p-8 bg-white rounded-[2rem] sm:rounded-[3rem] shadow-2xl shadow-emerald-900/5 border border-slate-100">
                
                
                <div class="relative group h-[280px] sm:h-[450px] rounded-[2.5rem] overflow-hidden shadow-2xl shadow-emerald-900/10">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent z-10"></div>
                    <template x-for="(img, i) in carouselImages" :key="i">
                        <div x-show="carouselIndex === i" 
                             x-transition:enter="transition opacity duration-1000"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition opacity duration-1000"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0">
                            <img :src="img" alt="Dokumentasi Masjid An-Nur" class="w-full h-full object-cover transform hover:scale-105 transition duration-[3s]">
                        </div>
                    </template>
                    
                    
                    <div class="absolute bottom-0 left-0 right-0 p-8 sm:p-14 z-20">
                        <div class="max-w-3xl">
                            <h2 class="text-4xl sm:text-6xl font-black text-white leading-[1.1] drop-shadow-2xl tracking-tighter">
                                Amanah Dalam<br><span class="text-emerald-400">Mengelola Kebaikan.</span>
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="py-4 text-center max-w-3xl mx-auto space-y-4">
                    <h3 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Zakat: Sucikan Harta, Berdayakan Sesama</h3>
                    <p class="text-base sm:text-lg leading-relaxed text-slate-500 font-bold">
                        Zakat adalah kewajiban yang mensucikan jiwa dan harta kita. Melalui pengelolaan yang transparan di <span class="text-emerald-700">Masjid An-Nur</span>, zakat Anda menjadi jembatan harapan bagi mereka yang membutuhkan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    
                    <div class="md:col-span-3 bg-gradient-to-br from-emerald-600 to-teal-800 p-10 sm:p-14 rounded-[2.5rem] text-white relative overflow-hidden group shadow-2xl shadow-emerald-200">
                        <div class="absolute inset-0 opacity-10 flex items-center justify-center group-hover:scale-110 transition duration-1000">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-80 w-80" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017V14H14.017C12.9124 14 12.017 13.1046 12.017 12V3C12.017 1.89543 12.9124 1 14.017 1H21.017C22.1216 1 23.017 1.89543 23.017 3V12C23.017 13.1046 22.1216 14 21.017 14H21.017V16C21.017 17.1046 20.1216 18 19.017 18H19.017V21H14.017ZM3.01699 21L3.01699 18C3.01699 16.8954 3.91242 16 5.01699 16H8.01699V14H3.01699C1.91242 14 1.01699 13.1046 1.01699 12V3C1.01699 1.89543 1.91242 1 3.01699 1H10.017C11.1216 1 12.017 1.89543 12.017 3V12C12.017 13.1046 11.1216 14 10.017 14H10.017V16C10.017 17.1046 9.12157 18 8.01699 18H8.01699V21H3.01699Z"/></svg>
                        </div>
                        <div class="relative z-10 space-y-6 text-center">
                            <p class="text-2xl sm:text-4xl font-bold italic leading-relaxed max-w-4xl mx-auto drop-shadow-md">
                                "Ambillah zakat dari harta mereka guna membersihkan dan mensucikan mereka, dan berdoalah untuk mereka..."
                            </p>
                            <div>
                                <p class="text-[13px] sm:text-[15px] font-black uppercase tracking-[0.4em] text-emerald-200">— QS. At-Taubah: 103</p>
                            </div>
                        </div>
                    </div>

                    
                    <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-emerald-900/5 flex flex-col justify-between group hover:border-emerald-200 transition-all hover:-translate-y-1">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-slate-900">Manajemen Muzakki</h4>
                            <p class="text-sm text-slate-500 font-bold mt-2 leading-relaxed">Pencatatan data jamaah muzakki yang rapi dan amanah.</p>
                        </div>
                    </div>

                    
                    <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-emerald-900/5 flex flex-col justify-between group hover:border-emerald-200 transition-all hover:-translate-y-1">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-slate-900">Laporan Real-Time</h4>
                            <p class="text-sm text-slate-500 font-bold mt-2 leading-relaxed">Transparansi penuh melalui rekapitulasi otomatis publik.</p>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-emerald-900/5 flex flex-col justify-between group hover:border-emerald-200 transition-all hover:-translate-y-1">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-slate-900">Amanah & Profesional</h4>
                            <p class="text-sm text-slate-500 font-bold mt-2 leading-relaxed">Dikelola oleh Panitia Zakat dengan integritas tinggi.</p>
                        </div>
                    </div>

                    
                    <div class="md:col-span-3 bg-slate-900 p-8 rounded-[2rem] text-white flex items-center justify-between group cursor-pointer hover:bg-slate-800 transition-all border border-slate-700 shadow-2xl shadow-emerald-900/20" @click="activeTab = 'laporan'">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 rounded-full bg-emerald-500 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-xl font-black tracking-tight text-white mb-1">Lihat Laporan Perolehan</h4>
                                <p class="text-sm text-slate-400 font-bold">Pantau penyaluran dan penerimaan secara langsung di halaman laporan.</p>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-full border border-slate-700 flex items-center justify-center group-hover:bg-emerald-500 group-hover:border-emerald-500 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </div>
                    </div>
                </div>

            </div>

            
            <div x-show="activeTab === 'laporan'" 
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                
                
                <div class="flex justify-center mb-1 sm:mb-3">
                    <div class="inline-flex items-center gap-3 px-5 py-2.5 rounded-2xl bg-white border border-emerald-500/10 shadow-xl shadow-emerald-900/5 ring-1 ring-emerald-500/5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span x-text="clock" class="text-sm sm:text-lg font-bold text-slate-900 tracking-tight tabular-nums"></span>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar rounded-2xl border border-slate-300/50 shadow-[0_30px_80px_-15px_rgba(6,78,59,0.2)] ring-1 ring-emerald-500/5 mx-1 sm:mx-0 bg-white">
                    <table class="w-full border-collapse bg-white">
                        <thead class="sticky top-0 z-20 shadow-md shadow-emerald-900/5">
                            <tr class="bg-emerald-600 border-b border-emerald-700">
                                <th class="pl-4 py-4 sm:pl-10 text-left text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Kategori Zakat</th>
                                <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Jiwa</th>
                                <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Total Uang</th>
                                <th class="px-2 py-4 sm:px-4 text-center text-[13px] sm:text-[16px] font-bold text-white uppercase tracking-[0.2em]">Total Beras</th>
                            </tr>
                        </thead>                        <tbody class="divide-y divide-slate-50">
                            <template x-for="(item, index) in items" :key="item.category">
                                <tr :class="index % 2 !== 0 ? 'bg-slate-50/50' : 'bg-white'" class="transition-colors">
                                    <td class="pl-4 py-5 sm:pl-10 text-base sm:text-xl font-bold text-slate-900 uppercase tracking-tighter sm:tracking-normal">
                                        <span class="whitespace-nowrap" x-text="formatCat(item.category)"></span>
                                    </td>
                                    <td class="px-2 py-5 sm:px-4 text-center text-[15px] sm:text-2xl text-slate-900 font-bold tabular-nums" x-text="item.total_jiwa.toLocaleString('id-ID') + ' Jiwa'"></td>
                                    <td class="px-2 py-5 sm:px-4 text-center text-[13px] sm:text-2xl text-emerald-600 font-bold tabular-nums" x-text="'Rp ' + (item.total_uang || 0).toLocaleString('id-ID')"></td>
                                    <td class="px-2 py-5 sm:px-4 text-center text-[13px] sm:text-2xl text-amber-600 font-bold tabular-nums" x-text="(item.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></td>
                                </tr>
                            </template>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="4" class="px-8 py-12 text-base text-slate-400 text-center font-medium">Belum ada data penerimaan masuk tahun ini.</td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot x-show="items.length > 0" class="border-t-4 border-emerald-600/20">
                            <tr class="bg-emerald-50 text-center">
                                <td class="pl-4 py-4 sm:pl-10 text-[13px] sm:text-[18px] font-bold tracking-widest uppercase text-emerald-800/60 text-left">TOTAL</td>
                                <td class="px-2 py-4 sm:px-4 text-slate-900 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                                    <span id="totalJiwa" x-text="(totals.total_jiwa || 0).toLocaleString('id-ID') + ' Jiwa'"></span>
                                </td>
                                <td class="px-2 py-4 sm:px-4 text-emerald-600 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                                    <span id="totalUang" x-text="'Rp ' + (totals.total_uang || 0).toLocaleString('id-ID')"></span>
                                </td>
                                <td class="px-2 py-4 sm:px-4 text-amber-600 text-[15px] sm:text-2xl font-bold tabular-nums whitespace-nowrap">
                                    <span id="totalBeras" x-text="(totals.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'"></span>
                                </td>
                            </tr>
                        </tfoot>
                </table>
            </div>
            <p x-show="error" x-html="error" class="mt-4 rounded-xl border border-red-100 bg-red-50/50 p-3 text-[10px] font-bold text-red-500 text-center" role="alert"></p>
        </div>

        
        <div x-show="activeTab === 'grafik'" 
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="p-1 sm:p-4 flex flex-col items-center justify-center">
            <!-- Daily Progress Chart Section -->
            <div class="w-full max-w-6xl relative bg-white rounded-[2rem] border border-slate-100 p-3 sm:px-6 sm:py-4 shadow-xl shadow-emerald-900/5 mb-1">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-emerald-600 rounded-lg text-white shadow-md">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        </div>
                        <h2 class="text-lg sm:text-xl font-black text-slate-800 tracking-tight">Grafik Penerimaan Harian</h2>
                    </div>
                </div>

                <!-- Summary Cards: Slimmer and Better Icons -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 py-4 px-6 rounded-2xl text-white shadow-lg shadow-emerald-200/50 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition duration-700">
                            <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.18v-1.93c-1.39-.14-2.81-.72-3.79-1.6l1.24-1.54c.83.69 1.95 1.18 2.85 1.3.75.1 1.25-.13 1.25-.66 0-.48-.52-.77-1.57-1.1-1.63-.5-3.69-1.35-3.69-3.75 0-1.89 1.24-3.41 3.12-3.86V5h2.18v1.89c1.23.11 2.3.61 3.03 1.22l-1.14 1.58c-.59-.44-1.37-.8-2.14-.85-.75-.05-1.17.21-1.17.61 0 .42.48.66 1.7 1.1 1.79.64 3.56 1.55 3.56 3.82 0 2.21-1.59 3.49-3.72 3.82z"/></svg>
                        </div>                        <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80 mb-0.5">Total Penerimaan Uang</p>
                        <h3 class="text-xl sm:text-2xl font-black truncate drop-shadow-sm font-mono" id="live-total-uang" x-text="'Rp ' + (totals.total_uang || 0).toLocaleString('id-ID')">Rp 0</h3>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-amber-700 py-4 px-6 rounded-2xl text-white shadow-lg shadow-amber-200/50 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition duration-700">
                            
                            <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M5 7h14l-1.5 15h-11L5 7z"/>
                                <path d="M5 7L3 3l5 3h8l5-3-2 4H5z" opacity="0.8"/>
                                <rect x="9" y="12" width="6" height="4" opacity="0.3"/>
                            </svg>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80 mb-0.5">Total Penerimaan Beras</p>
                        <h3 class="text-xl sm:text-2xl font-black truncate drop-shadow-sm font-mono" id="live-total-beras" x-text="(totals.total_beras_kg || 0).toFixed(2).replace('.', ',') + ' Kg'">0 Kg</h3>
                    </div>
                </div>

                <div class="h-[280px] sm:h-[450px] w-full relative">
                    <canvas id="dailyChart"></canvas>
                    <div id="idle-cards-container" class="absolute inset-0 pointer-events-none overflow-hidden mt-8"></div>
                </div>
            </div>
        </div>

    
        <div style="display: none !important;">
            <canvas id="historicalChart"></canvas>
        </div>
    </div>
</div>
</main>

    
    <div class="fixed bottom-0 left-0 right-0 z-50 flex flex-col pointer-events-none">
        <div class="pointer-events-auto">
            
            <div class="bg-slate-900 border-t border-slate-800 shadow-2xl relative w-full shrink-0 overflow-hidden backdrop-blur-md bg-opacity-95">
                <div class="marquee-container h-8 sm:h-10">
                    <div class="marquee-track h-full items-center">
                        
                        <div class="flex shrink-0 items-center whitespace-nowrap px-4 text-[13px] sm:text-[15px] font-bold text-slate-400 tracking-wide">
                            <span class="mx-8"><span class="text-emerald-400">Masjid An-Nur Komplek BPK V Gandul</span> — Melayani dan menyalurkan <span class="text-emerald-400">Zakat Fitrah, Fidyah, Zakat Mal, Infaq Shodaqoh</span>.</span>
                            <span class="mx-8">Tunaikan <span class="text-emerald-400">zakat tepat waktu</span> agar manfaatnya dapat segera dirasakan oleh saudara kita yang membutuhkan.</span>
                            <span class="mx-8">Zakat Anda sangat berarti untuk <span class="text-emerald-400">membantu sesama</span> dan meringankan beban umat.</span>
                            <span class="mx-8">Semoga zakat yang Bapak dan Ibu keluarkan menjadi <span class="text-emerald-400">pembersih harta</span> dan pembuka pintu rezeki.</span>
                            <span class="mx-8"><span class="text-emerald-400">Amanah dan transparan</span> dalam pengelolaan zakat adalah komitmen utama Panitia Zakat Masjid An-Nur.</span>
                            <span class="mx-8">Mari raih <span class="text-emerald-400">keberkahan</span> dengan menyisihkan sebagian harta untuk kemaslahatan umat.</span>
                            <span class="mx-8">Harta yang dizakatkan tidak akan berkurang, melainkan <span class="text-emerald-400">bertambah berkahnya</span>.</span>
                        </div>
                        
                        <div class="flex shrink-0 items-center whitespace-nowrap px-4 text-[13px] sm:text-[15px] font-bold text-slate-400 tracking-wide">
                            <span class="mx-8"><span class="text-emerald-400">Masjid An-Nur Komplek BPK V Gandul</span> — Melayani dan menyalurkan <span class="text-emerald-400">Zakat Fitrah, Fidyah, Zakat Mal, Infaq Shodaqoh</span>.</span>
                            <span class="mx-8">Tunaikan <span class="text-emerald-400">zakat tepat waktu</span> agar manfaatnya dapat segera dirasakan oleh saudara kita yang membutuhkan.</span>
                            <span class="mx-8">Zakat Anda sangat berarti untuk <span class="text-emerald-400">membantu sesama</span> dan meringankan beban umat.</span>
                            <span class="mx-8">Semoga zakat yang Bapak dan Ibu keluarkan menjadi <span class="text-emerald-400">pembersih harta</span> dan pembuka pintu rezeki.</span>
                            <span class="mx-8"><span class="text-emerald-400">Amanah dan transparan</span> dalam pengelolaan zakat adalah komitmen utama Panitia Zakat Masjid An-Nur.</span>
                            <span class="mx-8">Mari raih <span class="text-emerald-400">keberkahan</span> dengan menyisihkan sebagian harta untuk kemaslahatan umat.</span>
                            <span class="mx-8">Harta yang dizakatkan tidak akan berkurang, melainkan <span class="text-emerald-400">bertambah berkahnya</span>.</span>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="bg-slate-950 border-t border-slate-800 pt-2 pb-4 sm:pt-3 sm:pb-8 text-center w-full shrink-0 backdrop-blur-md bg-opacity-95">
                <div class="mx-auto max-w-5xl px-6">
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-2 text-[11px] sm:text-[12px] font-bold tracking-widest sm:tracking-[0.2em] uppercase">
                            <span class="text-slate-600">Powered by</span>
                            <div class="footer-item-group flex items-center gap-2 cursor-pointer">
                                <span class="animate-shine-text font-black">Ikatan Remaja Komplek BPK V Gandul</span>
                                <img src="/images/logo_irk.webp" class="footer-logo h-4 w-auto brightness-110 opacity-60" alt="Logo IRK">
                            </div>
                        </div>
                </div>
            </footer>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const easeOutExpo = (t) => t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
    window.animateValue = function(obj, start, end, duration = 2000, type = 'uang') {
        if (!obj) return;
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const elapsed = timestamp - startTimestamp;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easeOutExpo(progress);
            const current = easedProgress * (end - start) + start;
            if (type === 'uang') obj.textContent = 'Rp ' + Math.floor(current).toLocaleString('id-ID');
            else if (type === 'beras') obj.textContent = current.toFixed(2).replace('.', ',') + ' Kg';
            else if (type === 'jiwa') obj.textContent = Math.floor(current).toLocaleString('id-ID') + ' Jiwa';
            else obj.textContent = Math.floor(current).toLocaleString('id-ID');
            if (progress < 1) window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
    };

    window.updateDailyChart = function(newData) {
        if (!window.myDailyChart || !newData) return;
        window.myDailyChart.data.labels = newData.labels;
        window.myDailyChart.data.datasets[0].data = newData.uang;
        window.myDailyChart.data.datasets[1].data = newData.beras;
        const maxUang = Math.max(...newData.uang, 0);
        const maxBeras = Math.max(...newData.beras, 0);
        window.myDailyChart.options.scales.y.suggestedMax = Math.max(350000000, Math.ceil((maxUang * 1.1) / 100000000) * 100000000);
        window.myDailyChart.options.scales.y1.suggestedMax = Math.max(1300, Math.ceil((maxBeras * 1.1) / 250) * 250);
        window.myDailyChart.update('none');
    };

    window.initCharts = function() {
        const canvas = document.getElementById('dailyChart');
        if (!canvas) return;
        Chart.getChart(canvas)?.destroy();
        const dailyCtx = canvas.getContext('2d');
        if (!dailyCtx) return;
        const uangData = <?php echo json_encode($dailyChartData['uang'] ?? []); ?>;
        const berasData = <?php echo json_encode($dailyChartData['beras'] ?? []); ?>;
        const maxUang = Math.max(...uangData, 0);
        const suggestMaxUang = Math.max(350000000, Math.ceil((maxUang * 1.1) / 100000000) * 100000000);
        const maxBeras = Math.max(...berasData, 0);
        const suggestMaxBeras = Math.max(1300, Math.ceil((maxBeras * 1.1) / 250) * 250);
        const uangGradient = dailyCtx.createLinearGradient(0, 0, 0, 400);
        uangGradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
        uangGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
        const berasGradient = dailyCtx.createLinearGradient(0, 0, 0, 400);
        berasGradient.addColorStop(0, 'rgba(245, 158, 11, 0.2)');
        berasGradient.addColorStop(1, 'rgba(245, 158, 11, 0)');
        window.myDailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dailyChartData['labels'] ?? []); ?>,
                datasets: [
                    { label: 'Uang Zakat', data: uangData, borderColor: '#10b981', borderWidth: 6, backgroundColor: uangGradient, fill: true, tension: 0, pointBackgroundColor: '#ffffff', pointBorderColor: '#10b981', pointBorderWidth: 4, pointRadius: 6, yAxisID: 'y' },
                    { label: 'Beras Zakat', data: berasData, borderColor: '#f59e0b', borderWidth: 6, backgroundColor: berasGradient, fill: true, tension: 0, pointBackgroundColor: '#ffffff', pointBorderColor: '#f59e0b', pointBorderWidth: 4, pointRadius: 6, yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animations: { y: { type: 'number', duration: 1000, easing: 'easeOutQuart', from: (ctx) => ctx.type === 'data' ? ctx.chart.scales.y.bottom : null, delay: (ctx) => ctx.datasetIndex * 300 }, x: { duration: 0 } },
                scales: {
                    y: { position: 'left', beginAtZero: true, suggestedMax: suggestMaxUang, grid: { color: 'rgba(241, 245, 249, 1)', drawBorder: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#64748b', callback: (v) => v >= 1000000 ? 'Rp ' + (v / 1000000) + 'jt' : 'Rp ' + v } },
                    y1: { position: 'right', beginAtZero: true, suggestedMax: suggestMaxBeras, grid: { drawOnChartArea: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#f59e0b', callback: (v) => v + ' kg' } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#64748b' } }
                },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 } } },
                    tooltip: { backgroundColor: '#1e293b', padding: 15, cornerRadius: 15, titleFont: { size: 14, weight: 'bold' }, bodyFont: { size: 13 }, callbacks: { label: (ctx) => ctx.datasetIndex === 0 ? 'Total Uang: Rp ' + ctx.parsed.y.toLocaleString('id-ID') : 'Total Beras: ' + ctx.parsed.y + ' Kg' } }
                }
            }
        });
        const initialWait = 1300 + 1500;
        clearTimeout(window.chartScanTimeout);
        window.chartScanTimeout = setTimeout(() => { if (typeof window.autoHover === 'function') window.autoHover(window.myDailyChart); }, initialWait);
    };

    window.autoHover = function(chart) {
        if (!chart || !chart.data.labels?.length) return;
        const validIndices = [];
        for (let i = 0; i < chart.data.labels.length; i++) {
            const uang = chart.data.datasets[0].data[i];
            const beras = chart.data.datasets[1].data[i];
            if (uang > 0 || beras > 0) validIndices.push(i);
        }
        if (validIndices.length === 0) return;
        let currentIndex = 0;
        const runStep = () => {
            if (currentIndex >= validIndices.length) { window.chartScanTimeout = setTimeout(() => { currentIndex = 0; runStep(); }, 5000); return; }
            const i = validIndices[currentIndex];
            const activeElements = [{ datasetIndex: 0, index: i }, { datasetIndex: 1, index: i }];
            chart.setActiveElements(activeElements);
            chart.tooltip.setActiveElements(activeElements, { x: chart.getDatasetMeta(0).data[i].x, y: chart.getDatasetMeta(0).data[i].y });
            chart.update('none');
            window.chartScanTimeout = setTimeout(() => {
                chart.setActiveElements([]);
                chart.tooltip.setActiveElements([], { x: 0, y: 0 });
                chart.update('none');
                window.chartScanTimeout = setTimeout(() => { currentIndex++; runStep(); }, 1000);
            }, 1500);
        };
        runStep();
    }

    const hCanvas = document.getElementById('historicalChart');
    if (hCanvas) {
        Chart.getChart(hCanvas)?.destroy();
        const hCtx = hCanvas.getContext('2d');
        const hData = <?php echo json_encode($historicalChartData['data'] ?? [12000000, 19000000, 15000000, 25000000, 22000000]); ?>;
        const hLabels = <?php echo json_encode($historicalChartData['labels'] ?? ['2021', '2022', '2023', '2024', '2025']); ?>;
        const grad = hCtx.createLinearGradient(0, 0, 0, 400);
        grad.addColorStop(0, 'rgba(16, 185, 129, 0.9)');
        grad.addColorStop(1, 'rgba(16, 185, 129, 0.3)');
        new Chart(hCtx, {
            type: 'bar',
            data: { labels: hLabels, datasets: [{ label: 'Penerimaan', data: hData, backgroundColor: grad, borderRadius: 12, borderSkipped: false, maxBarThickness: 60 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart', delay: (ctx) => ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 130 : 0 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a', titleColor: '#94a3b8', bodyColor: '#f0fdf4', borderColor: 'rgba(16,185,129,0.4)', borderWidth: 1, cornerRadius: 12, padding: 14, displayColors: false, titleFont: { size: 11, weight: '600' }, bodyFont: { size: 17, weight: 'bold' }, callbacks: { label: (ctx) => ctx.parsed.y >= 1000000 ? 'Rp ' + (ctx.parsed.y / 1000000).toFixed(1).replace('.', ',') + ' Juta' : 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false }, border: { display: false }, ticks: { callback: (v) => v >= 1000000 ? 'Rp ' + (v / 1000000).toFixed(0) + ' Jt' : 'Rp ' + v, font: { size: 11, weight: '600' }, color: '#94a3b8', maxTicksLimit: 6, padding: 8 } },
                    x: { grid: { display: false, drawBorder: false }, border: { display: false }, ticks: { font: { size: 12, weight: 'bold' }, color: '#334155', padding: 6 } }
                }
            }
        });
    }
</script>

</body>
</html>
<?php /**PATH C:\Users\Ananta Raihan\Kuliah\ZAKAT TRIAL\resources\views/public/home.blade.php ENDPATH**/ ?>