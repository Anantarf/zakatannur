import ChartDataLabels from 'chartjs-plugin-datalabels';

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

const formatRupiahCompact = (value) => {
    if (!value) {
        return 'Rp 0';
    }
    if (value >= 1000000) {
        return 'Rp ' + (value / 1000000).toFixed(value >= 10000000 ? 0 : 1).replace('.', ',') + 'jt';
    }
    if (value >= 1000) {
        return 'Rp ' + Math.round(value / 1000) + 'rb';
    }
    return 'Rp ' + Math.round(value);
};

const formatBerasCompact = (value) => {
    if (!value) {
        return '0 Kg';
    }
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: value < 10 ? 2 : 1, maximumFractionDigits: 2 }).format(value) + ' Kg';
};

const sumRange = (values) => values.reduce((acc, v) => acc + (Number(v) || 0), 0);

const buildLineConfig = ({ canvasId, type, config, palette }) => {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') {
        return null;
    }

    if (typeof Chart.registry !== 'undefined' && Chart.registry.plugins && !Chart.registry.plugins.get('datalabels')) {
        Chart.register(ChartDataLabels);
    }

    Chart.getChart(canvas)?.destroy();

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return null;
    }

    const data = dailyDatasetValues(config.dailyChartData, type);
    const labels = dailyLabels(config.dailyChartData);

    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, palette.fillFrom);
    gradient.addColorStop(1, palette.fillTo);

    const pointColors = data.map((v) => (v > 0 ? palette.pointFill : palette.pointDim));
    const pointSizes = data.map((v) => (v > 0 ? 4.5 : 2.5));

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: palette.label,
                    data,
                    borderColor: palette.border,
                    backgroundColor: gradient,
                    borderWidth: 3,
                    borderDash: palette.dash || undefined,
                    fill: true,
                    tension: 0.36,
                    pointBackgroundColor: pointColors,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2.5,
                    pointRadius: pointSizes,
                    pointHoverRadius: 7,
                    datalabels: {
                        display: (c) => Number(c.dataset.data[c.dataIndex]) > 0,
                        align: palette.dash ? 'bottom' : 'top',
                        anchor: palette.dash ? 'start' : 'end',
                        offset: 6,
                        color: palette.labelColor,
                        backgroundColor: 'rgba(255, 255, 255, 0.92)',
                        borderColor: palette.border,
                        borderWidth: 1,
                        borderRadius: 6,
                        padding: { top: 3, bottom: 3, left: 6, right: 6 },
                        font: { family: publicChartFont, weight: '700', size: 10 },
                        formatter: (value) => (type === 'uang' ? formatRupiahCompact(value) : formatBerasCompact(value)),
                        clamp: true,
                    },
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 950, easing: 'easeOutQuart' },
            layout: { padding: { top: 32, right: 18, bottom: 6, left: 4 } },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: type === 'uang' ? dailySuggestedMax(data, 1, 1000000) : dailySuggestedMax(data, 1, 25),
                    grid: { color: 'rgba(226, 232, 240, 0.6)', drawBorder: false },
                    ticks: {
                        font: { family: publicChartFont, weight: '600', size: 11 },
                        color: palette.axisColor,
                        padding: 8,
                        callback: (v) => (type === 'uang' ? formatRupiahCompact(v) : v + ' Kg'),
                    },
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: true,
                        maxRotation: 0,
                        minRotation: 0,
                        font: { family: publicChartFont, weight: '600', size: 11 },
                        color: '#334155',
                        padding: 6,
                    },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.96)',
                    padding: 14,
                    cornerRadius: 12,
                    borderColor: 'rgba(148, 163, 184, 0.2)',
                    borderWidth: 1,
                    titleFont: { family: publicChartFont, size: 12, weight: '700' },
                    titleColor: '#94a3b8',
                    bodyFont: { family: publicChartFont, size: 12, weight: '600' },
                    bodyColor: '#f0fdf4',
                    displayColors: true,
                    boxPadding: 6,
                    callbacks: {
                        title: (items) => items[0]?.label ?? '',
                        label: (c) => {
                            if (type === 'uang') {
                                return ' ' + new Intl.NumberFormat('id-ID').format(c.parsed.y || 0);
                            }
                            return ' ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(c.parsed.y || 0) + ' Kg';
                        },
                        afterBody: (items) => {
                            const total = sumRange(data);
                            const max = Math.max(...data, 0);
                            const avg = data.length ? total / data.length : 0;
                            const last = data[data.length - 1] || 0;
                            const prev = data.length > 1 ? data[data.length - 2] || 0 : 0;
                            const delta = last - prev;
                            const tail = type === 'uang'
                                ? '  ' + new Intl.NumberFormat('id-ID').format(total)
                                : '  ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(total) + ' Kg';
                            return [
                                '',
                                'Total periode:' + tail,
                                'Rata-rata: ' + (type === 'uang' ? new Intl.NumberFormat('id-ID').format(Math.round(avg)) : new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(avg) + ' Kg'),
                                'Tertinggi: ' + (type === 'uang' ? new Intl.NumberFormat('id-ID').format(max) : max + ' Kg'),
                                'Delta: ' + (delta >= 0 ? '+' : '') + (type === 'uang' ? new Intl.NumberFormat('id-ID').format(delta) : delta.toFixed(2) + ' Kg'),
                            ];
                        },
                    },
                },
            },
        },
    });
};

export const createChartService = (config) => {
    let uangChart = null;
    let berasChart = null;
    let historicalChart = null;
    let scanTimeout = null;

    const clearScanTimeout = () => {
        if (scanTimeout) {
            clearTimeout(scanTimeout);
            scanTimeout = null;
        }
    };

    const uangPalette = {
        label: 'Uang Zakat',
        border: '#10b981',
        fillFrom: 'rgba(16, 185, 129, 0.32)',
        fillTo: 'rgba(16, 185, 129, 0)',
        pointFill: '#10b981',
        pointDim: 'rgba(16, 185, 129, 0.35)',
        labelColor: '#047857',
        axisColor: '#334155',
    };

    const berasPalette = {
        label: 'Beras Zakat',
        border: '#f59e0b',
        fillFrom: 'rgba(245, 158, 11, 0.22)',
        fillTo: 'rgba(245, 158, 11, 0)',
        pointFill: '#f59e0b',
        pointDim: 'rgba(245, 158, 11, 0.35)',
        labelColor: '#b45309',
        axisColor: '#92400e',
        dash: [6, 4],
    };

    return {
        updateDailyChart(newData) {
            const uangData = dailyDatasetValues(newData, 'uang');
            const berasData = dailyDatasetValues(newData, 'beras');
            const labels = dailyLabels(newData);

            if (uangChart) {
                uangChart.data.labels = labels;
                uangChart.data.datasets[0].data = uangData;
                uangChart.options.scales.y.suggestedMax = dailySuggestedMax(uangData, 1, 1000000);
                uangChart.update();
            }

            if (berasChart) {
                berasChart.data.labels = labels;
                berasChart.data.datasets[0].data = berasData;
                berasChart.options.scales.y.suggestedMax = dailySuggestedMax(berasData, 1, 25);
                berasChart.update();
            }
        },

        initDailyChart() {
            uangChart = buildLineConfig({ canvasId: 'dailyChartUang', type: 'uang', config, palette: uangPalette });
            berasChart = buildLineConfig({ canvasId: 'dailyChartBeras', type: 'beras', config, palette: berasPalette });

            const rangeLabel = config.dailyChartData?.range?.label || '';
            const uangRange = document.getElementById('chart-uang-range');
            const berasRange = document.getElementById('chart-beras-range');
            if (uangRange) {
                uangRange.textContent = rangeLabel;
            }
            if (berasRange) {
                berasRange.textContent = rangeLabel;
            }

            clearScanTimeout();
        },

        getDailyChart() {
            return uangChart || berasChart;
        },

        getUangChart() {
            return uangChart;
        },

        getBerasChart() {
            return berasChart;
        },

        clearScanTimeout,

        applyFilter(filter) {
            // Both charts render their own canvas; visibility is handled by Alpine x-show.
            // This method stays for API compatibility and re-fits axis when data updates.
            if (uangChart) {
                const data = uangChart.data.datasets[0].data;
                uangChart.options.scales.y.suggestedMax = dailySuggestedMax(data, 1, 1000000);
                uangChart.update();
            }
            if (berasChart) {
                const data = berasChart.data.datasets[0].data;
                berasChart.options.scales.y.suggestedMax = dailySuggestedMax(data, 1, 25);
                berasChart.update();
            }
        },

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

            historicalChart = new Chart(hCtx, {
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
