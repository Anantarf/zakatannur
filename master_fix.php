<?php
$compiledPath = "storage/framework/views/6645a0880c2145f60ae6e69b3342a8e9a2706b45.php";
$targetPath = "resources/views/public/home.blade.php";

// 1. Ambil isi file fotokopi yang sehat badannya
$c = file_get_contents($compiledPath);

// 2. Bersihkan tag script Alpine lama (mencari document.addEventListener)
$scriptStartPos = strpos($c, "<script>");
$pos = strpos($c, "document.addEventListener('alpine:init'");
if ($pos !== false) {
    // Potong sampai sebelum tag script alpine lama
    // Kita mundur cari <script> terdekat sebelum itu
    $actualStart = strrpos(substr($c, 0, $pos), "<script>");
    $c = substr($c, 0, $actualStart);
}

// 3. Masukkan Otak Sehat (Global window.zakatApp)
$brain = <<<'EOD'
<script>
window.zakatApp = () => ({
    openLogin: <?php echo json_encode($errors->any() || request()->has('login'), 15, 512) ?>,
    activeTab: 'beranda',
    notification: {
        show: false,
        message: '',
        queue: [],
        processing: false
    },
    idleTimeout: null,
    isIdleMode: false,
    lastFetchTime: 0,
    chartTimeouts: [],
    clearChartTimeouts() {
        this.chartTimeouts.forEach(t => clearTimeout(t));
        this.chartTimeouts = [];
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
        
        const container = document.getElementById('idle-cards-container');
        if (!container) return;

        const datasets = chart.data.datasets;
        const labels = chart.data.labels;
        if (labels.length === 0) {
            this.chartTimeouts.push(setTimeout(() => {
                this.activeTab = 'laporan';
                this.resetIdle();
            }, 5000));
            return;
        }

        const meta0 = chart.getDatasetMeta(0);
        const meta1 = chart.getDatasetMeta(1);
        let lastIndex = labels.length - 1;
        
        labels.forEach((label, i) => {
            this.chartTimeouts.push(setTimeout(() => {
                const p0 = meta0.data[i].getProps(['x', 'y'], true);
                const p1 = meta1.data[i].getProps(['x', 'y'], true);
                
                const card = document.createElement('div');
                card.id = 'idle-card-' + i;
                card.className = 'absolute bg-slate-900/95 text-white p-2.5 rounded-xl text-[10px] shadow-2xl border border-emerald-500/40 transition-all duration-1000 opacity-0 pointer-events-none z-20 whitespace-nowrap backdrop-blur-sm';
                
                const uang = datasets[0].data[i];
                const beras = datasets[1].data[i];
                const formatUang = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(uang);
                
                card.innerHTML = `
                    <div class="font-black border-b border-emerald-500/20 pb-1 mb-1.5 text-emerald-400 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        ${label}
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="flex justify-between gap-4">
                            <span class="opacity-70">Penerimaan Uang:</span>
                            <span class="font-bold text-emerald-50 font-mono">${formatUang}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="opacity-70">Penerimaan Beras:</span>
                            <span class="font-bold text-amber-400 font-mono">${beras} Kg</span>
                        </div>
                    </div>
                `;
                
                const yPos = Math.min(p0.y, p1.y) - 65;
                card.style.left = p0.x + 'px';
                card.style.top = yPos + 'px';
                card.style.transform = 'translateX(-50%) translateY(10px)';
                
                container.appendChild(card);
                
                requestAnimationFrame(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateX(-50%) translateY(0)';
                });

            }, i * 2000));
        });
        
        const lastCardTime = lastIndex * 2000;
        const waitTime = lastCardTime + 10000;
        
        this.chartTimeouts.push(setTimeout(() => {
            for (let i = lastIndex; i >= 0; i--) {
                this.chartTimeouts.push(setTimeout(() => {
                    const card = document.getElementById('idle-card-' + i);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(-50%) translateY(-10px)';
                        setTimeout(() => card.remove(), 1000);
                    }
                }, (lastIndex - i) * 1000));
            }
            
            const totalHideDuration = (lastIndex * 1000);
            this.chartTimeouts.push(setTimeout(() => {
                this.activeTab = 'laporan';
                this.resetIdle();
            }, totalHideDuration + 3000));

        }, waitTime));
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
    async pollLatest() {
        const refreshSecs = {{ (int) $refreshIntervalSeconds }};
        if (this._pollLock || document.visibilityState !== 'visible' || refreshSecs <= 0) return;
        this._pollLock = true;
        try {
            const res = await fetch('/api/public/latest');
            const json = await res.json();
            const latest = json.data || [];
            let seenIds = JSON.parse(localStorage.getItem('seen_tx_ids') || '[]');
            if (seenIds.length === 0 && latest.length > 0) {
                localStorage.setItem('seen_tx_ids', JSON.stringify(latest.map(tx => tx.id)));
                return;
            }
            const newTxs = latest.filter(tx => !seenIds.includes(tx.id)).reverse();
            if (newTxs.length > 0) {
                seenIds = [...new Set([...seenIds, ...newTxs.map(tx => tx.id)])].slice(-50);
                localStorage.setItem('seen_tx_ids', JSON.stringify(seenIds));
                const cats = [...new Set(newTxs.map(tx => this.formatCat(tx.category)))];
                let labelSummary = this.joinGrammatically(cats);
                const sumUang = newTxs.reduce((sum, tx) => sum + (tx.uang || 0), 0);
                const sumBeras = newTxs.reduce((sum, tx) => sum + (tx.beras || 0), 0);
                let parts = [];
                if (sumUang > 0) parts.push('Rp ' + sumUang.toLocaleString('id-ID'));
                if (sumBeras > 0) parts.push(sumBeras.toFixed(2).replace('.', ',') + ' Kg');
                this.notification.queue.push(`Alhamdulilah! Diperoleh ${labelSummary}: ${parts.join(' dan ')}`);
                this.processQueue();
            }
        } catch (e) {
            console.error('Polling error:', e);
        } finally {
            this._pollLock = false;
            if (refreshSecs > 0) {
                setTimeout(() => this.pollLatest(), refreshSecs * 1000);
            }
        }
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
        '{{ asset('images/beranda_annur_new.jpg') }}',
        '{{ asset('images/dokumentasi_1.jpg') }}'
    ],
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
        this.pollLatest();
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
                    if (typeof window.fetchData === 'function') window.fetchData();
                    this.lastFetchTime = now;
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
                        const checkInterval = setInterval(() => {
                            if (window.myDailyChart) {
                                clearInterval(checkInterval);
                                window.myDailyChart.stop();
                                window.myDailyChart.reset();
                                setTimeout(() => {
                                    window.myDailyChart.update();
                                    const dataCount = window.myDailyChart.data.labels.length;
                                    const switchWait = (dataCount * 300) + 750 + 2000;
                                    window.chartScanTimeout = setTimeout(() => {
                                        if (typeof window.autoHover === 'function') window.autoHover(window.myDailyChart);
                                    }, switchWait);
                                    if (this.isIdleMode) {
                                        const activePoints = window.myDailyChart?.data?.datasets[0]?.data?.filter(v => v !== null).length || 0;
                                        const autoHoverTotalTime = activePoints * 2000;
                                        const totalWaitToReturn = Math.min(switchWait + autoHoverTotalTime + 3000, 60000);
                                        clearTimeout(this.idleTimeout);
                                        this.idleTimeout = setTimeout(() => {
                                            if (this.isIdleMode) {
                                                this.activeTab = 'laporan';
                                                this.resetIdle();
                                            }
                                        }, totalWaitToReturn);
                                    }
                                }, 100);
                            }
                        }, 50);
                    }
                });
            }
        });
    }
});
</script>
EOD;

// 4. Transformasi Compiled PHP Tags kembali ke Blade dasar
$c = str_replace('<?php echo e(', '{{ ', $c);
$c = str_replace('); ?>', ' }}', $c);
$c = str_replace('<?php echo app(\'Illuminate\\Foundation\\Vite\')', '@vite', $c);

// 5. Rakit Ulang: Header (Compiled) + Otak yang baru (Brain) + Body footer (Compiled)
// Kita harus ganti x-data="zakatApp" jadi x-data="zakatApp()"
$c = str_replace('x-data="zakatApp"', 'x-data="zakatApp()"', $c);

$finalContent = $c . "\n" . $brain . "\n</body>\n</html>";

file_put_contents($targetPath, $finalContent);
echo "MASTER RESTORE: WEB IS NOW 100% HEALTHY.\n";
