import { createDataMethods } from './data';
import { formatCategory, joinGrammatically, formatUang, formatBeras, formatJiwa, formatJiwaPlain, animateValue } from './format';
import { createIdleMethods } from './idle';
import { createRealtime } from './realtime';

const scrollToTop = () => {
    if (typeof globalThis !== 'undefined' && typeof globalThis.scrollTo === 'function') {
        globalThis.scrollTo({ top: 0, behavior: 'instant' });
    }
};

const initialState = (config) => ({
    openLogin: config.openLogin,
    activeTab: 'beranda',
    isLoading: false,
    selectedYear: config.selectedYear,
    items: config.items ?? [],
    totals: config.totals ?? {},
    latestTransactionAt: config.latestTransactionAt ?? null,
    latestTransactionAgeLabel: '',
    dailyChartData: config.dailyChartData ?? {},
    isFirstLoad: true,
    error: null,
    lastFetchTime: 0,
    idleTimeout: null,
    isIdleMode: false,
    notification: {
        show: false,
        message: '',
        queue: [],
        processing: false,
    },
    clock: '',
    chartTimeouts: [],
    carouselIndex: 0,
    chartSlide: 0,
    chartSlideInterval: null,
    carouselImages: [
        '/images/beranda_annur_new.webp',
        '/images/dokumentasi_1.webp',
    ],
});

export const createPublicHomeApp = (config, chartService) => {
    const getEcho = createRealtime(config.realtime ?? {});

    return (stateFactory = initialState) => ({
        ...stateFactory(config),
        ...createIdleMethods(chartService),
        ...createDataMethods(config, chartService, animateValue),

        formatUang,
        formatBeras,
        formatJiwa,
        formatJiwaPlain,

        formatCat(category) {
            return formatCategory(category);
        },

        joinGrammatically(items) {
            return joinGrammatically(items);
        },

        updateClock() {
            const options = { timeZone: 'Asia/Jakarta', weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const formatter = new Intl.DateTimeFormat('id-ID', options);
            const parts = formatter.formatToParts(new Date());
            const d = parts.reduce((acc, part) => ({ ...acc, [part.type]: part.value }), {});
            this.clock = `${d.weekday}, ${d.day} ${d.month} ${d.year} ${d.hour}:${d.minute}:${d.second} WIB`;
            this.latestTransactionAgeLabel = this.formatLatestTransactionAge();
        },

        formatLatestTransactionAge() {
            if (!this.latestTransactionAt) {
                return 'Transaksi terakhir: belum ada transaksi';
            }

            const latest = new Date(String(this.latestTransactionAt).replace(' ', 'T') + '+07:00');
            if (Number.isNaN(latest.getTime())) {
                return 'Transaksi terakhir: waktu tidak tersedia';
            }

            let remainingMinutes = Math.max(0, Math.floor((Date.now() - latest.getTime()) / 60000));
            const units = [
                ['tahun', 12 * 30 * 24 * 60],
                ['bulan', 30 * 24 * 60],
                ['hari', 24 * 60],
                ['jam', 60],
                ['menit', 1],
            ];

            const parts = [];
            units.forEach(([label, minutes]) => {
                if (parts.length >= 2) {
                    return;
                }

                const value = Math.floor(remainingMinutes / minutes);
                if (value > 0 || parts.length > 0 || label === 'menit') {
                    parts.push(`${value} ${label}`);
                    remainingMinutes -= value * minutes;
                }
            });

            return `Transaksi terakhir: ${parts.join(', ')} yang lalu`;
        },

        setChartSlide(index) {
            const total = 2;
            const next = ((index % total) + total) % total;
            if (this.chartSlide === next) {
                this.resetChartSlideInterval();
                return;
            }

            this.chartSlide = next;
            if (this.activeTab === 'grafik') {
                this.ensureDailyChart().then(() => chartService.setSlide(next));
            } else {
                chartService.setSlide(next);
            }
            this.resetChartSlideInterval();
        },

        resetChartSlideInterval() {
            if (this.chartSlideInterval) {
                clearInterval(this.chartSlideInterval);
            }
            if (this.activeTab !== 'grafik') {
                this.chartSlideInterval = null;
                return;
            }
            this.chartSlideInterval = setInterval(() => {
                this.setChartSlide(this.chartSlide + 1);
            }, 15000);
        },

        initTimers() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);

            const refreshSecs = Number(config.refreshIntervalSeconds || 0);
            if (refreshSecs > 0) {
                setInterval(() => {
                    this.refreshSummary();
                    this.pollLatest();
                }, refreshSecs * 1000);
            }

            setInterval(() => {
                this.carouselIndex = (this.carouselIndex + 1) % this.carouselImages.length;
            }, 15000);
        },

        initWatchers() {
            ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
                globalThis.addEventListener(eventName, () => this.resetIdle(), { passive: true });
            });

            globalThis.addEventListener('public-home:set-tab', (event) => {
                const tab = event?.detail?.tab;
                if (!['beranda', 'laporan', 'grafik'].includes(tab)) {
                    return;
                }

                this.activeTab = tab;
                scrollToTop();
            });

            this.$watch('openLogin', (value) => {
                if (value) {
                    clearTimeout(this.idleTimeout);
                    this.isIdleMode = false;
                } else {
                    this.resetIdle();
                }
            });

            this.$watch('isIdleMode', (value) => {
                if (value) {
                    this.startIdleCycle();
                }
            });

            this.$watch('activeTab', (value) => {
                chartService.clearScanTimeout();

                if (value === 'grafik') {
                    scrollToTop();
                    this.ensureDailyChart();
                    return;
                }

                this.resetChartSlideInterval();
            });
        },

        ensureDailyChart() {
            return this.loadChartJs().then((success) => {
                if (!success) {
                    return false;
                }

                chartService.initDailyChart();
                chartService.setSlide(this.chartSlide);
                this.resetChartSlideInterval();

                return true;
            });
        },

        playPopSound() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                
                if (!window.zakkyAudioCtx) {
                    window.zakkyAudioCtx = new AudioContext();
                }
                const ctx = window.zakkyAudioCtx;
                if (ctx.state === 'suspended') ctx.resume();

                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(800, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(300, ctx.currentTime + 0.1);

                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.1, ctx.currentTime + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.1);
            } catch (e) {}
        },

        async initRealtime() {
            const echo = await getEcho();
            if (!echo) {
                return;
            }

            echo.channel('public-transactions').listen('.transaction.created', (event) => {
                const items = event.items || [];
                if (items.length === 0) {
                    return;
                }

                const now = Date.now();
                if (now - this.lastFetchTime > 2000) {
                    this.refreshSummary();
                }

                this.playPopSound();
                this.pushTransactionNotification(items);
            });
        },

        init() {
            this.initTimers();
            this.pollLatest();
            this.refreshSummary();

            this.resetIdle();
            this.initWatchers();
            this.initRealtime();
        },
    });
};
