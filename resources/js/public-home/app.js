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
    selectedYear: config.selectedYear,
    items: config.items ?? [],
    totals: config.totals ?? {},
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
        },

        setChartSlide(index) {
            const total = 2;
            const next = ((index % total) + total) % total;
            if (this.chartSlide === next) {
                this.resetChartSlideInterval();
                return;
            }

            this.chartSlide = next;
            chartService.setSlide(next);
            this.resetChartSlideInterval();
        },

        resetChartSlideInterval() {
            if (this.chartSlideInterval) {
                clearInterval(this.chartSlideInterval);
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
                    this.loadChartJs().then((success) => {
                        if (success) {
                            chartService.initDailyChart();
                            chartService.setSlide(this.chartSlide);
                            this.resetChartSlideInterval();
                        }
                    });
                }
            });
        },

        initRealtime() {
            const echo = getEcho();
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

                this.pushTransactionNotification(items);
            });
        },

        init() {
            this.initTimers();
            this.pollLatest();
            this.refreshSummary();

            this.loadChartJs().then((success) => {
                if (!success) {
                    return;
                }
                chartService.initHistoricalChart();
                chartService.initDailyChart();
            });

            this.resetIdle();
            this.initWatchers();
            this.initRealtime();
            this.resetChartSlideInterval();
        },
    });
};