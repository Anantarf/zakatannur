import { createPublicHomeApp } from './public-home/app';
import { createChartService } from './public-home/charts';
import { loadPublicHomeConfig } from './public-home/config';
import { animateValue } from './public-home/format';

const publicHomeConfig = loadPublicHomeConfig();

if (publicHomeConfig) {
    const chartService = createChartService(publicHomeConfig);

    window.animateValue = animateValue;
    window.updateDailyChart = (newData) => chartService.updateDailyChart(newData);
    window.initCharts = () => chartService.initDailyChart();
    window.autoHover = (chart) => chartService.autoHover(chart);
    window.zakatApp = () => createPublicHomeApp(publicHomeConfig, chartService);
}
