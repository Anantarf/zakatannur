export const createIdleMethods = () => ({
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

            const index = validIndices[currentIndex];
            const activeElements = [
                { datasetIndex: 0, index },
                { datasetIndex: 1, index },
            ];

            chart.setActiveElements(activeElements);
            chart.tooltip.setActiveElements(activeElements, {
                x: meta0.data[index].x,
                y: meta0.data[index].y,
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
});
