const parseJsonScript = (id) => {
    const element = document.getElementById(id);

    if (!element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch (error) {
        console.error(`Failed to parse JSON config from #${id}`, error);
        return null;
    }
};

const publicHomeConfig = parseJsonScript('public-home-config');

if (publicHomeConfig) {
    const easeOutExpo = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));
    const seenTransactionsStorageKey = 'seen_tx_ids';

    const readSeenTransactionIds = () => {
        try {
            return JSON.parse(localStorage.getItem(seenTransactionsStorageKey) || '[]');
        } catch {
            return [];
        }
    };

    const writeSeenTransactionIds = (ids) => {
        localStorage.setItem(seenTransactionsStorageKey, JSON.stringify(ids.slice(-50)));
    };

    const buildNotificationMessage = (items, formatCategory, joinGrammatically) => {
        const categories = [...new Set(items.map((item) => formatCategory(item.category)))];
        const parts = [];
        const sumUang = items.reduce((sum, item) => sum + (item.uang || 0), 0);
        const sumBeras = items.reduce((sum, item) => sum + (item.beras || 0), 0);

        if (sumUang > 0) {
            parts.push('Rp ' + sumUang.toLocaleString('id-ID'));
        }

        if (sumBeras > 0) {
            parts.push(sumBeras.toFixed(2).replace('.', ',') + ' Kg');
        }

        return `Alhamdulillah! Diperoleh ${joinGrammatically(categories)}: ${parts.join(' dan ')}`;
    };

    const bootstrapRealtime = () => {
        if (window.__zakatEcho) {
            return window.__zakatEcho;
        }

        const realtime = publicHomeConfig.realtime ?? {};
        if (!realtime.enabled || typeof window.Pusher === 'undefined' || typeof window.Echo === 'undefined') {
            return null;
        }

        const EchoConstructor = window.Echo;
        window.__zakatEcho = new EchoConstructor({
            broadcaster: 'pusher',
            key: realtime.key,
            cluster: realtime.cluster,
            forceTLS: true,
        });

        return window.__zakatEcho;
    };

    window.animateValue = function animateValue(obj, start, end, duration = 2000, type = 'uang') {
        if (!obj) {
            return;
        }

        let startTimestamp = null;

        const step = (timestamp) => {
            if (!startTimestamp) {
                startTimestamp = timestamp;
            }

            const elapsed = timestamp - startTimestamp;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easeOutExpo(progress);
            const current = easedProgress * (end - start) + start;

            if (type === 'uang') {
                obj.textContent = 'Rp ' + Math.floor(current).toLocaleString('id-ID');
            } else if (type === 'beras') {
                obj.textContent = current.toFixed(2).replace('.', ',') + ' Kg';
            } else if (type === 'jiwa') {
                obj.textContent = Math.floor(current).toLocaleString('id-ID') + ' Jiwa';
            } else {
                obj.textContent = Math.floor(current).toLocaleString('id-ID');
            }

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };

        window.requestAnimationFrame(step);
    };

    window.updateDailyChart = function updateDailyChart(newData) {
        if (!window.myDailyChart || !newData) {
            return;
        }

        window.myDailyChart.data.labels = newData.labels;
        window.myDailyChart.data.datasets[0].data = newData.uang;
        window.myDailyChart.data.datasets[1].data = newData.beras;

        const maxUang = Math.max(...newData.uang, 0);
        const maxBeras = Math.max(...newData.beras, 0);

        window.myDailyChart.options.scales.y.suggestedMax = Math.max(350000000, Math.ceil((maxUang * 1.1) / 100000000) * 100000000);
        window.myDailyChart.options.scales.y1.suggestedMax = Math.max(1300, Math.ceil((maxBeras * 1.1) / 250) * 250);
        window.myDailyChart.update('none');
    };

    window.initCharts = function initCharts() {
        const canvas = document.getElementById('dailyChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        Chart.getChart(canvas)?.destroy();

        const dailyCtx = canvas.getContext('2d');
        if (!dailyCtx) {
            return;
        }

        const uangData = publicHomeConfig.dailyChartData?.uang ?? [];
        const berasData = publicHomeConfig.dailyChartData?.beras ?? [];
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
                labels: publicHomeConfig.dailyChartData?.labels ?? [],
                datasets: [
                    { label: 'Uang Zakat', data: uangData, borderColor: '#10b981', borderWidth: 6, backgroundColor: uangGradient, fill: true, tension: 0, pointBackgroundColor: '#ffffff', pointBorderColor: '#10b981', pointBorderWidth: 4, pointRadius: 6, yAxisID: 'y' },
                    { label: 'Beras Zakat', data: berasData, borderColor: '#f59e0b', borderWidth: 6, backgroundColor: berasGradient, fill: true, tension: 0, pointBackgroundColor: '#ffffff', pointBorderColor: '#f59e0b', pointBorderWidth: 4, pointRadius: 6, yAxisID: 'y1' },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animations: { y: { type: 'number', duration: 1000, easing: 'easeOutQuart', from: (ctx) => ctx.type === 'data' ? ctx.chart.scales.y.bottom : null, delay: (ctx) => ctx.datasetIndex * 300 }, x: { duration: 0 } },
                scales: {
                    y: { position: 'left', beginAtZero: true, suggestedMax: suggestMaxUang, grid: { color: 'rgba(241, 245, 249, 1)', drawBorder: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#64748b', callback: (v) => v >= 1000000 ? 'Rp ' + (v / 1000000) + 'jt' : 'Rp ' + v } },
                    y1: { position: 'right', beginAtZero: true, suggestedMax: suggestMaxBeras, grid: { drawOnChartArea: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#f59e0b', callback: (v) => v + ' kg' } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#64748b' } },
                },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 } } },
                    tooltip: { backgroundColor: '#1e293b', padding: 15, cornerRadius: 15, titleFont: { size: 14, weight: 'bold' }, bodyFont: { size: 13 }, callbacks: { label: (ctx) => ctx.datasetIndex === 0 ? 'Total Uang: Rp ' + ctx.parsed.y.toLocaleString('id-ID') : 'Total Beras: ' + ctx.parsed.y + ' Kg' } },
                },
            },
        });

        const initialWait = 2800;
        clearTimeout(window.chartScanTimeout);
        window.chartScanTimeout = setTimeout(() => {
            if (typeof window.autoHover === 'function') {
                window.autoHover(window.myDailyChart);
            }
        }, initialWait);
    };

    window.autoHover = function autoHover(chart) {
        if (!chart || !chart.data.labels?.length) {
            return;
        }

        const validIndices = [];

        for (let i = 0; i < chart.data.labels.length; i += 1) {
            const uang = chart.data.datasets[0].data[i];
            const beras = chart.data.datasets[1].data[i];

            if (uang > 0 || beras > 0) {
                validIndices.push(i);
            }
        }

        if (validIndices.length === 0) {
            return;
        }

        let currentIndex = 0;

        const runStep = () => {
            if (currentIndex >= validIndices.length) {
                window.chartScanTimeout = setTimeout(() => {
                    currentIndex = 0;
                    runStep();
                }, 5000);
                return;
            }

            const i = validIndices[currentIndex];
            const activeElements = [{ datasetIndex: 0, index: i }, { datasetIndex: 1, index: i }];
            chart.setActiveElements(activeElements);
            chart.tooltip.setActiveElements(activeElements, {
                x: chart.getDatasetMeta(0).data[i].x,
                y: chart.getDatasetMeta(0).data[i].y,
            });
            chart.update('none');

            window.chartScanTimeout = setTimeout(() => {
                chart.setActiveElements([]);
                chart.tooltip.setActiveElements([], { x: 0, y: 0 });
                chart.update('none');
                window.chartScanTimeout = setTimeout(() => {
                    currentIndex += 1;
                    runStep();
                }, 1000);
            }, 1500);
        };

        runStep();
    };

    const initHistoricalChart = () => {
        const hCanvas = document.getElementById('historicalChart');
        if (!hCanvas || typeof Chart === 'undefined') {
            return;
        }

        Chart.getChart(hCanvas)?.destroy();

        const hCtx = hCanvas.getContext('2d');
        if (!hCtx) {
            return;
        }

        const hData = publicHomeConfig.historicalChartData?.data ?? [12000000, 19000000, 15000000, 25000000, 22000000];
        const hLabels = publicHomeConfig.historicalChartData?.labels ?? ['2021', '2022', '2023', '2024', '2025'];
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
                        backgroundColor: '#0f172a',
                        titleColor: '#94a3b8',
                        bodyColor: '#f0fdf4',
                        borderColor: 'rgba(16,185,129,0.4)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 14,
                        displayColors: false,
                        titleFont: { size: 11, weight: '600' },
                        bodyFont: { size: 17, weight: 'bold' },
                        callbacks: { label: (ctx) => ctx.parsed.y >= 1000000 ? 'Rp ' + (ctx.parsed.y / 1000000).toFixed(1).replace('.', ',') + ' Juta' : 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') },
                    },
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false }, border: { display: false }, ticks: { callback: (v) => v >= 1000000 ? 'Rp ' + (v / 1000000).toFixed(0) + ' Jt' : 'Rp ' + v, font: { size: 11, weight: '600' }, color: '#94a3b8', maxTicksLimit: 6, padding: 8 } },
                    x: { grid: { display: false, drawBorder: false }, border: { display: false }, ticks: { font: { size: 12, weight: 'bold' }, color: '#334155', padding: 6 } },
                },
            },
        });
    };

    window.zakatApp = () => ({
        openLogin: publicHomeConfig.openLogin,
        activeTab: 'beranda',
        selectedYear: publicHomeConfig.selectedYear,
        items: publicHomeConfig.items ?? [],
        totals: publicHomeConfig.totals ?? {},
        dailyChartData: publicHomeConfig.dailyChartData ?? {},
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
        carouselImages: [
            '/images/beranda_annur_new.webp',
            '/images/dokumentasi_1.webp',
        ],
        updateClock() {
            const options = { timeZone: 'Asia/Jakarta', weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const formatter = new Intl.DateTimeFormat('id-ID', options);
            const parts = formatter.formatToParts(new Date());
            const d = parts.reduce((acc, part) => ({ ...acc, [part.type]: part.value }), {});
            this.clock = `${d.weekday}, ${d.day} ${d.month} ${d.year} ${d.hour}:${d.minute}:${d.second} WIB`;
        },
        clearChartTimeouts() {
            this.chartTimeouts.forEach((t) => clearTimeout(t));
            this.chartTimeouts = [];

            if (window.chartScanTimeout) {
                clearTimeout(window.chartScanTimeout);
                window.chartScanTimeout = null;
            }

            const container = document.getElementById('idle-cards-container');
            if (container) {
                container.innerHTML = '';
            }
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
                window.myDailyChart.tooltip.setActiveElements([], { x: 0, y: 0 });
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
            if (!chart) {
                return;
            }

            this.clearChartTimeouts();
            if (window.chartScanTimeout) {
                clearTimeout(window.chartScanTimeout);
            }

            const container = document.getElementById('idle-cards-container');
            if (container) {
                container.innerHTML = '';
            }

            const datasets = chart.data.datasets;
            const labels = chart.data.labels;
            const validIndices = [];

            for (let i = 0; i < labels.length; i += 1) {
                const uang = datasets[0].data[i];
                const beras = datasets[1].data[i];

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
                const activeElements = [
                    { datasetIndex: 0, index: i },
                    { datasetIndex: 1, index: i },
                ];

                chart.setActiveElements(activeElements);
                chart.tooltip.setActiveElements(activeElements, {
                    x: meta0.data[i].x,
                    y: meta0.data[i].y,
                });
                chart.update('none');

                this.chartTimeouts.push(setTimeout(() => {
                    chart.setActiveElements([]);
                    chart.tooltip.setActiveElements([], { x: 0, y: 0 });
                    chart.update('none');

                    this.chartTimeouts.push(setTimeout(() => {
                        currentIndex += 1;
                        showStep();
                    }, 2000));
                }, 5000));
            };

            showStep();
        },
        startIdleCycle() {
            if (!this.isIdleMode || this.openLogin) {
                return;
            }

            if (this.activeTab === 'beranda' || this.activeTab === 'laporan') {
                this.activeTab = 'grafik';
                setTimeout(() => this.runChartScan(), 1500);
            } else if (this.activeTab === 'grafik') {
                this.runChartScan();
            }
        },
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

                if (data.dailyChartData && typeof window.updateDailyChart === 'function') {
                    window.updateDailyChart(data.dailyChartData);
                }

                this.$nextTick(() => {
                    this.animateSummaryCards(oldTotals, this.totals);
                    this.isFirstLoad = false;
                });
            } catch (error) {
                clearTimeout(timeoutId);
                this.error = error.name === 'AbortError' ? 'Koneksi lambat.' : 'Gagal menghubungi server.';
            }
        },
        animateSummaryCards(oldTotals, newTotals) {
            if (this.isFirstLoad) {
                return;
            }

            const animate = (id, start, end, type) => {
                const element = document.getElementById(id);
                if (element && end > start && typeof window.animateValue === 'function') {
                    window.animateValue(element, start, end, 2000, type);
                }
            };

            animate('live-total-uang', oldTotals.total_uang || 0, newTotals.total_uang || 0, 'uang');
            animate('live-total-beras', oldTotals.total_beras_kg || 0, newTotals.total_beras_kg || 0, 'beras');
            animate('totalUang', oldTotals.total_uang || 0, newTotals.total_uang || 0, 'uang');
            animate('totalBeras', oldTotals.total_beras_kg || 0, newTotals.total_beras_kg || 0, 'beras');
            animate('totalJiwa', oldTotals.total_jiwa || 0, newTotals.total_jiwa || 0, 'jiwa');
        },
        formatCat(category) {
            const labels = { fitrah: 'Zakat Fitrah', fidyah: 'Fidyah', mal: 'Zakat Mal', infaq: 'Infaq Shodaqoh' };
            return labels[category] || category;
        },
        joinGrammatically(items) {
            if (items.length === 0) {
                return '';
            }
            if (items.length === 1) {
                return items[0];
            }
            if (items.length === 2) {
                return items[0] + ' dan ' + items[1];
            }
            return items.slice(0, -1).join(', ') + ', dan ' + items.slice(-1);
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

                setTimeout(() => {
                    this.notification.show = false;
                    this.notification.processing = false;
                    setTimeout(() => this.processQueue(), 1000);
                }, 7000);
            }, 1000);
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

                let seenIds = readSeenTransactionIds();
                const newItems = items.filter((item) => !seenIds.includes(item.id));

                if (newItems.length > 0) {
                    seenIds = [...new Set([...seenIds, ...newItems.map((item) => item.id)])];
                    writeSeenTransactionIds(seenIds);
                    this.notification.queue.push(buildNotificationMessage(newItems, this.formatCat, this.joinGrammatically));
                    this.processQueue();
                }
            } catch {
                // silent fail for background poll
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
        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);

            const refreshSecs = Number(publicHomeConfig.refreshIntervalSeconds || 0);
            if (refreshSecs > 0) {
                setInterval(() => {
                    this.refreshSummary();
                    this.pollLatest();
                }, refreshSecs * 1000);
            }

            this.pollLatest();
            this.refreshSummary();
            setInterval(() => {
                this.carouselIndex = (this.carouselIndex + 1) % this.carouselImages.length;
            }, 7000);

            this.loadChartJs().then((success) => {
                if (success) {
                    initHistoricalChart();
                }
            });

            ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
                window.addEventListener(eventName, () => this.resetIdle(), { passive: true });
            });

            this.resetIdle();

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

            const echo = bootstrapRealtime();
            if (echo) {
                echo.channel('public-transactions').listen('.transaction.created', (event) => {
                    const items = event.items || [];
                    if (items.length === 0) {
                        return;
                    }

                    const now = Date.now();
                    if (now - this.lastFetchTime > 2000) {
                        this.refreshSummary();
                    }

                    const seenIds = [...new Set([...readSeenTransactionIds(), ...items.map((item) => item.id)])];
                    writeSeenTransactionIds(seenIds);
                    this.notification.queue.push(buildNotificationMessage(items, this.formatCat, this.joinGrammatically));
                    this.processQueue();
                });
            }

            this.$watch('activeTab', (value) => {
                if (window.chartScanTimeout) {
                    clearTimeout(window.chartScanTimeout);
                    window.chartScanTimeout = null;
                }

                if (value === 'grafik') {
                    window.scrollTo({ top: 0, behavior: 'instant' });
                    this.loadChartJs().then((success) => {
                        if (success && typeof window.initCharts === 'function') {
                            window.initCharts();
                        }
                    });
                }
            });
        },
    });
}
