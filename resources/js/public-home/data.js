import { formatCategory, joinGrammatically } from './format';
import {
    buildNotificationMessage,
    hasSeenTransactionSnapshot,
    readSeenTransactionIds,
    writeSeenTransactionIds,
} from './notifications';

const noopAnimate = () => {};

export const createDataMethods = (config, chartService, animateValue = noopAnimate) => {
    const boundAnimate = typeof animateValue === 'function' ? animateValue : noopAnimate;

    return {
    async refreshSummary() {
        if (document.visibilityState !== 'visible') {
            return;
        }

        const now = Date.now();
        if (now - this.lastFetchTime < 1000) {
            return;
        }

        this.lastFetchTime = now;
        this.error = null;
        this.isLoading = true;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000);

        try {
            const res = await fetch('/api/public/summary?year=' + encodeURIComponent(this.selectedYear), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            if (!res.ok) {
                const body = await res.json().catch(() => null);
                this.error = body?.message || 'Gagal memuat rekap.';
                return;
            }

            const json = await res.json();
            const data = json.data || { items: [] };
            const oldTotals = { ...this.totals };

            this.items = data.items || [];
            this.totals = data.totals || {};
            this.latestTransactionAt = data.latest_transaction_at_wib || null;
            this.latestTransactionAgeLabel = this.formatLatestTransactionAge();

            if (data.dailyChartData) {
                this.dailyChartData = data.dailyChartData;
                chartService.updateDailyChart(data.dailyChartData);
            }

            this.$nextTick(() => {
                this.animateSummaryCards(oldTotals, this.totals);
                this.isFirstLoad = false;
            });
        } catch (error) {
            clearTimeout(timeoutId);
            this.error = error.name === 'AbortError' ? 'Koneksi lambat.' : 'Gagal menghubungi server.';
        } finally {
            this.isLoading = false;
        }
    },

    animateSummaryCards(oldTotals, newTotals) {
        if (this.isFirstLoad) {
            return;
        }

        const animate = (id, start, end, type) => {
            const element = document.getElementById(id);
            if (element && end > start) {
                boundAnimate(element, start, end, 1500, type);
            }
        };

        animate('live-total-uang', oldTotals.total_uang || 0, newTotals.total_uang || 0, 'uang');
        animate('live-total-beras', oldTotals.total_beras_kg || 0, newTotals.total_beras_kg || 0, 'beras');
        animate('totalUang', oldTotals.total_uang || 0, newTotals.total_uang || 0, 'uang');
        animate('totalBeras', oldTotals.total_beras_kg || 0, newTotals.total_beras_kg || 0, 'beras');
        animate('totalJiwa', oldTotals.total_jiwa || 0, newTotals.total_jiwa || 0, 'jiwa');
    },

    processQueue() {
        if (this.notification.show || this.notification.processing || this.notification.queue.length === 0) {
            return;
        }

        this.notification.processing = true;
        const nextMessage = this.notification.queue.shift();

        setTimeout(() => {
            this.notification.message = nextMessage;
            this.notification.show = true;

            this.notification.timeoutId = setTimeout(() => {
                this.dismissNotification();
            }, 7000);
        }, 1000);
    },

    dismissNotification() {
        if (!this.notification.show) return;
        
        if (this.notification.timeoutId) {
            clearTimeout(this.notification.timeoutId);
            this.notification.timeoutId = null;
        }
        
        this.notification.show = false;
        
        setTimeout(() => {
            this.notification.processing = false;
            this.processQueue();
        }, 300);
    },

    async pollLatest() {
        try {
            const res = await fetch('/api/public/latest', { headers: { Accept: 'application/json' } });
            if (!res.ok) {
                return;
            }

            const json = await res.json();
            const items = json.data || [];
            if (items.length === 0) {
                return;
            }

            if (!hasSeenTransactionSnapshot()) {
                writeSeenTransactionIds(items.map((item) => item.id));
                return;
            }

            let seenIds = readSeenTransactionIds();
            const newItems = items.filter((item) => !seenIds.includes(item.id));

            if (newItems.length > 0) {
                seenIds = [...new Set([...seenIds, ...newItems.map((item) => item.id)])];
                writeSeenTransactionIds(seenIds);
                this.notification.queue.push(buildNotificationMessage(newItems, formatCategory, joinGrammatically));
                this.processQueue();
            }
        } catch {
            // Background polling should never interrupt the public page.
        }
    },

    async loadChartJs() {
        if (typeof Chart !== 'undefined') {
            return true;
        }

        if (this._chartJsPromise) {
            return this._chartJsPromise;
        }

        this._chartJsPromise = new Promise((resolve) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => {
                resolve(true);
            };
            script.onerror = () => {
                this._chartJsPromise = null;
                resolve(false);
            };
            document.head.appendChild(script);
        });

        return this._chartJsPromise;
    },

    pushTransactionNotification(items) {
        const seenIds = [...new Set([...readSeenTransactionIds(), ...items.map((item) => item.id)])];
        writeSeenTransactionIds(seenIds);
        this.notification.queue.push(buildNotificationMessage(items, formatCategory, joinGrammatically));
        this.processQueue();
    },
    };
};
