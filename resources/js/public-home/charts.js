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

const PALETTES = {
    uang: {
        type: 'uang',
        border: '#10b981',
        fillFrom: 'rgba(16, 185, 129, 0.32)',
        fillTo: 'rgba(16, 185, 129, 0)',
        pointFill: '#10b981',
        pointDim: 'rgba(16, 185, 129, 0.35)',
        labelColor: '#047857',
        borderLabel: 'rgba(16, 185, 129, 0.35)',
        axisColor: '#334155',
        axisColorSecondary: '#64748b',
        gridColor: 'rgba(148, 163, 184, 0.22)',
        tickFormatter: (v) => formatRupiahCompact(v),
        datalabelFormatter: (v) => formatRupiahCompact(v),
        label: 'Uang Zakat',
        yStep: 1000000,
        yFloor: 1,
    },
    beras: {
        type: 'beras',
        border: '#f59e0b',
        fillFrom: 'rgba(245, 158, 11, 0.30)',
        fillTo: 'rgba(245, 158, 11, 0)',
        pointFill: '#f59e0b',
        pointDim: 'rgba(245, 158, 11, 0.35)',
        labelColor: '#b45309',
        borderLabel: 'rgba(245, 158, 11, 0.28)',
        axisColor: '#334155',
        axisColorSecondary: '#64748b',
        gridColor: 'rgba(148, 163, 184, 0.22)',
        tickFormatter: (v) => v + ' Kg',
        datalabelFormatter: (v) => formatBerasCompact(v),
        label: 'Beras Zakat',
        yStep: 25,
        yFloor: 1,
    },
};

const buildChart = (canvas, type, config, palette) => {
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

    const gradient = ctx.createLinearGradient(0, 0, 0, 380);
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
                    fill: true,
                    tension: 0.36,
                    pointBackgroundColor: pointColors,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2.5,
                    pointRadius: pointSizes,
                    pointHoverRadius: 7,
                    datalabels: {
                        display: (c) => Number(c.dataset.data[c.dataIndex]) > 0,
                        align: 'top',
                        anchor: 'end',
                        offset: 6,
                        color: palette.labelColor,
                        backgroundColor: 'rgba(255, 255, 255, 0.94)',
                        borderColor: palette.borderLabel,
                        borderWidth: 1,
                        borderRadius: 6,
                        padding: { top: 3, bottom: 3, left: 6, right: 6 },
                        font: { family: publicChartFont, weight: '700', size: 10 },
                        formatter: (value) => palette.datalabelFormatter(value),
                        clamp: true,
                    },
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 1100, easing: 'easeOutQuart' },
            layout: { padding: { top: 40, right: 20, bottom: 8, left: 4 } },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: dailySuggestedMax(data, palette.yFloor, palette.yStep),
                    grid: { color: palette.gridColor, drawBorder: false },
                    ticks: {
                        font: { family: publicChartFont, weight: '600', size: 11 },
                        color: palette.axisColor,
                        padding: 8,
                        callback: (v) => palette.tickFormatter(v),
                    },
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: true,
                        maxRotation: 0,
                        minRotation: 0,
                        font: { family: publicChartFont, weight: '600', size: 11 },
                        color: palette.axisColorSecondary,
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
                            const res = [];
                            
                            if (items && items.length) {
                                const idx = items[0].dataIndex;
                                if (idx > 0) {
                                    const current = data[idx] || 0;
                                    const prev = data[idx - 1] || 0;
                                    const diff = current - prev;
                                    let diffStr = '';
                                    if (diff > 0) {
                                        diffStr = '↑ ' + (type === 'uang' ? new Intl.NumberFormat('id-ID').format(diff) : diff.toFixed(2) + ' Kg');
                                    } else if (diff < 0) {
                                        diffStr = '↓ ' + (type === 'uang' ? new Intl.NumberFormat('id-ID').format(Math.abs(diff)) : Math.abs(diff).toFixed(2) + ' Kg');
                                    } else {
                                        diffStr = '-';
                                    }
                                    res.push('Selisih Harian: ' + diffStr);
                                } else {
                                    res.push('Selisih Harian: -');
                                }
                            }
                            return res;
                        },
                    },
                },
            },
        },
    });
};

export const createChartService = (config) => {
    let dailyChart = null;
    let dailyChartType = 'uang';
    let historicalChart = null;
    let scanTimeout = null;

    const clearScanTimeout = () => {
        if (scanTimeout) {
            clearTimeout(scanTimeout);
            scanTimeout = null;
        }
    };

    return {
        updateDailyChart(newData) {
            if (!newData) {
                return;
            }

            const data = dailyDatasetValues(newData, dailyChartType);
            const labels = dailyLabels(newData);

            if (dailyChart) {
                dailyChart.data.labels = labels;
                dailyChart.data.datasets[0].data = data;
                const palette = PALETTES[dailyChartType];
                dailyChart.options.scales.y.suggestedMax = dailySuggestedMax(data, palette.yFloor, palette.yStep);
                dailyChart.update();
            }
        },

        initDailyChart() {
            const canvas = document.getElementById('dailyChart');
            if (!canvas) {
                return;
            }

            dailyChartType = 'uang';
            dailyChart = buildChart(canvas, dailyChartType, config, PALETTES[dailyChartType]);

            const rangeLabel = config.dailyChartData?.range?.label || '';
            const rangeEl = document.getElementById('chart-range-label');
            if (rangeEl) {
                rangeEl.textContent = rangeLabel;
            }

            clearScanTimeout();
        },

        setSlide(slideIndex) {
            const type = slideIndex === 1 ? 'beras' : 'uang';
            if (type === dailyChartType && dailyChart) {
                return;
            }

            dailyChartType = type;
            const canvas = document.getElementById('dailyChart');
            dailyChart = buildChart(canvas, type, config, PALETTES[type]);
        },

        getDailyChart() {
            return dailyChart;
        },

        clearScanTimeout,

        applyFilter(slideIndex) {
            this.setSlide(slideIndex);
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
