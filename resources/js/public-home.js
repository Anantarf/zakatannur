import Alpine from 'alpinejs';
import { createPublicHomeApp } from './public-home/app';
import { createChartService } from './public-home/charts';
import { loadPublicHomeConfig } from './public-home/config';

const publicHomeConfig = loadPublicHomeConfig();

if (publicHomeConfig) {
    const chartService = createChartService(publicHomeConfig);

    Alpine.data('publicHome', () => createPublicHomeApp(publicHomeConfig, chartService)());
}