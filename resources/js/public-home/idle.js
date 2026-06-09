export const createIdleMethods = (chartService) => ({
    clearChartTimeouts() {
        this.chartTimeouts.forEach((t) => clearTimeout(t));
        this.chartTimeouts = [];

        if (chartService) {
            chartService.clearScanTimeout();
        }

        const container = document.getElementById('idle-cards-container');
        if (container) {
            container.innerHTML = '';
        }
    },

    resetIdle() {
        clearTimeout(this.idleTimeout);
        this.clearChartTimeouts();

        if (this.activeTab === 'grafik' && chartService) {
            const chart = chartService.getDailyChart();
            if (chart) {
                chart.setActiveElements([]);
                chart.tooltip.setActiveElements([], { x: 0, y: 0 });
                chart.update('none');
            }
        }

        this.isIdleMode = false;

        if (!this.openLogin) {
            this.idleTimeout = setTimeout(() => {
                this.isIdleMode = true;
            }, 120000);
        }
    },

    runChartScan() {
        this.clearChartTimeouts();
    },

    startIdleCycle() {
        return;
    },
});