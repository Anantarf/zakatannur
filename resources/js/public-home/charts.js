const dailySuggestedMax = (values, floor, step) => {
    const max = Math.max(...values, 0);

    return Math.max(floor, Math.ceil((max * 1.1) / step) * step);
};

const chartHasDataAtIndex = (chart, index) => {
    const uang = chart.data.datasets[0].data[index];
    const beras = chart.data.datasets[1].data[index];

    return uang > 0 || beras > 0;
};

export const createChartService = (config) => ({
    updateDailyChart(newData) {
        if (!window.myDailyChart || !newData) {
            return;
        }

        window.myDailyChart.data.labels = newData.labels;
        window.myDailyChart.data.datasets[0].data = newData.uang;
        window.myDailyChart.data.datasets[1].data = newData.beras;
        window.myDailyChart.options.scales.y.suggestedMax = dailySuggestedMax(newData.uang, 350000000, 100000000);
        window.myDailyChart.options.scales.y1.suggestedMax = dailySuggestedMax(newData.beras, 1300, 250);
        window.myDailyChart.update('none');
    },

    initDailyChart() {
        const canvas = document.getElementById('dailyChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        Chart.getChart(canvas)?.destroy();

        const dailyCtx = canvas.getContext('2d');
        if (!dailyCtx) {
            return;
        }

        const uangData = config.dailyChartData?.uang ?? [];
        const berasData = config.dailyChartData?.beras ?? [];
        const uangGradient = dailyCtx.createLinearGradient(0, 0, 0, 400);
        const berasGradient = dailyCtx.createLinearGradient(0, 0, 0, 400);

        uangGradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
        uangGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
        berasGradient.addColorStop(0, 'rgba(245, 158, 11, 0.2)');
        berasGradient.addColorStop(1, 'rgba(245, 158, 11, 0)');

        window.myDailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: config.dailyChartData?.labels ?? [],
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
                    y: { position: 'left', beginAtZero: true, suggestedMax: dailySuggestedMax(uangData, 350000000, 100000000), grid: { color: 'rgba(241, 245, 249, 1)', drawBorder: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#64748b', callback: (v) => v >= 1000000 ? 'Rp ' + (v / 1000000) + 'jt' : 'Rp ' + v } },
                    y1: { position: 'right', beginAtZero: true, suggestedMax: dailySuggestedMax(berasData, 1300, 250), grid: { drawOnChartArea: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#f59e0b', callback: (v) => v + ' kg' } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 }, color: '#64748b' } },
                },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: { family: 'Outfit, sans-serif', weight: 'bold', size: 12 } } },
                    tooltip: { backgroundColor: '#1e293b', padding: 15, cornerRadius: 15, titleFont: { size: 14, weight: 'bold' }, bodyFont: { size: 13 }, callbacks: { label: (ctx) => ctx.datasetIndex === 0 ? 'Total Uang: Rp ' + ctx.parsed.y.toLocaleString('id-ID') : 'Total Beras: ' + ctx.parsed.y + ' Kg' } },
                },
            },
        });

        clearTimeout(window.chartScanTimeout);
        window.chartScanTimeout = setTimeout(() => this.autoHover(window.myDailyChart), 2800);
    },

    autoHover(chart) {
        if (!chart || !chart.data.labels?.length) {
            return;
        }

        const validIndices = chart.data.labels
            .map((_, index) => index)
            .filter((index) => chartHasDataAtIndex(chart, index));

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

            const index = validIndices[currentIndex];
            const activeElements = [{ datasetIndex: 0, index }, { datasetIndex: 1, index }];
            chart.setActiveElements(activeElements);
            chart.tooltip.setActiveElements(activeElements, {
                x: chart.getDatasetMeta(0).data[index].x,
                y: chart.getDatasetMeta(0).data[index].y,
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
    },

    initHistoricalChart() {
        const hCanvas = document.getElementById('historicalChart');
        if (!hCanvas || typeof Chart === 'undefined') {
            return;
        }

        Chart.getChart(hCanvas)?.destroy();

        const hCtx = hCanvas.getContext('2d');
        if (!hCtx) {
            return;
        }

        const hData = config.historicalChartData?.data ?? [12000000, 19000000, 15000000, 25000000, 22000000];
        const hLabels = config.historicalChartData?.labels ?? ['2021', '2022', '2023', '2024', '2025'];
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
    },
});
