const dailySuggestedMax = (values, floor, step) => {
    const max = Math.max(...values, 0);

    if (max === 0) {
        return floor;
    }

    return Math.max(floor, Math.ceil((max * 1.1) / step) * step);
};

const dailyDatasetValues = (data, key) => {
    const dataset = data?.datasets?.find((item) => item.key === key);

    return dataset?.values ?? data?.[key] ?? [];
};

const dailyLabels = (data) => data?.labels ?? [];
const publicChartFont = "'Plus Jakarta Sans', sans-serif";

export const createChartService = (config) => {
    let dailyChart = null;
    let scanTimeout = null;
    const clearScanTimeout = () => {
        if (scanTimeout) {
            clearTimeout(scanTimeout);
            scanTimeout = null;
        }
    };

    return {
        updateDailyChart(newData) {
            if (!dailyChart || !newData) {
                return;
            }

            const uangData = dailyDatasetValues(newData, 'uang');
            const berasData = dailyDatasetValues(newData, 'beras');

            dailyChart.data.labels = dailyLabels(newData);
            dailyChart.data.datasets[0].data = uangData;
            dailyChart.data.datasets[1].data = berasData;
            dailyChart.options.scales.y.suggestedMax = dailySuggestedMax(uangData, 1, 1000000);
            dailyChart.options.scales.y1.suggestedMax = dailySuggestedMax(berasData, 1, 25);
            dailyChart.update('none');
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

            const uangData = dailyDatasetValues(config.dailyChartData, 'uang');
            const berasData = dailyDatasetValues(config.dailyChartData, 'beras');
            dailyChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: dailyLabels(config.dailyChartData),
                    datasets: [
                        {
                            label: 'Uang Zakat',
                            data: uangData,
                            backgroundColor: 'rgba(16, 185, 129, 0.78)',
                            borderColor: '#10b981',
                            borderWidth: 1.5,
                            borderRadius: 8,
                            borderSkipped: false,
                            categoryPercentage: 0.56,
                            barPercentage: 0.72,
                            maxBarThickness: 18,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Beras Zakat',
                            data: berasData,
                            backgroundColor: 'rgba(245, 158, 11, 0.62)',
                            borderColor: '#f59e0b',
                            borderWidth: 1.5,
                            borderRadius: 8,
                            borderSkipped: false,
                            categoryPercentage: 0.56,
                            barPercentage: 0.72,
                            maxBarThickness: 18,
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animations: {
                        y: {
                            type: 'number',
                            duration: 900,
                            easing: 'easeOutQuart',
                            from: (ctx) => ctx.type === 'data' ? ctx.chart.scales.y.bottom : null,
                            delay: (ctx) => ctx.datasetIndex * 120,
                        },
                        x: { duration: 0 },
                    },
                    scales: {
                        y: { position: 'left', beginAtZero: true, suggestedMax: dailySuggestedMax(uangData, 1, 1000000), grid: { color: 'rgba(241, 245, 249, 1)', drawBorder: false }, ticks: { font: { family: publicChartFont, weight: '600', size: 12 }, color: '#64748b', callback: (v) => v >= 1000000 ? 'Rp ' + (v / 1000000) + 'jt' : 'Rp ' + v } },
                        y1: { position: 'right', beginAtZero: true, suggestedMax: dailySuggestedMax(berasData, 1, 25), grid: { drawOnChartArea: false }, ticks: { font: { family: publicChartFont, weight: '600', size: 12 }, color: '#f59e0b', callback: (v) => v + ' kg' } },
                        x: {
                            grid: { display: false },
                            ticks: {
                                autoSkip: true,
                                maxRotation: 0,
                                minRotation: 0,
                                font: { family: publicChartFont, weight: '600', size: 12 },
                                color: '#64748b',
                            },
                        },
                    },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: { family: publicChartFont, weight: '600', size: 12 } } },
                        tooltip: { backgroundColor: '#1e293b', padding: 15, cornerRadius: 15, titleFont: { size: 14, weight: 'bold' }, bodyFont: { size: 13 }, callbacks: { label: (ctx) => ctx.datasetIndex === 0 ? 'Total Uang: Rp ' + ctx.parsed.y.toLocaleString('id-ID') : 'Total Beras: ' + ctx.parsed.y + ' Kg' } },
                    },
                },
            });

            clearScanTimeout();
        },

        getDailyChart() {
            return dailyChart;
        },

        clearScanTimeout,

        autoHover(chart) {
            return chart;
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

            const hData = config.historicalChartData?.data ?? [];
            const hLabels = config.historicalChartData?.labels ?? [];
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
    };
};