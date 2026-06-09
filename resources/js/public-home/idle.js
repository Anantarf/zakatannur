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
        this.clearChartTimeouts();
    },

    startIdleCycle() {
        return;
    },
});
