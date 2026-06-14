import { test, expect } from '@playwright/test';
import {
    expectClickTargetsUsable,
    expectCopywritingQuality,
    expectNoCriticalOverlap,
    expectNoHorizontalOverflow,
    expectReadableTextContrast,
} from './helpers/ui-audit';

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 390, height: 844 },
];

const requiredCopy = {
    required: [
        'Portal Transparansi Zakat',
        'Masjid An-Nur',
        'Laporan penerimaan zakat real-time yang transparan dan akuntabel.',
        'Transparansi Penerimaan Zakat',
    ],
};

test.describe('Audit UI Design Publik', () => {
    for (const viewport of viewports) {
        test(`home memenuhi standar copywriting, warna, layout, UI, UX, responsive - ${viewport.name}`, async ({ page }) => {
            const consoleErrors = [];

            page.on('console', (message) => {
                if (message.type() === 'error') {
                    consoleErrors.push(message.text());
                }
            });

            await page.setViewportSize({
                width: viewport.width,
                height: viewport.height,
            });

            await page.goto('/');

            await expect(page.getByRole('navigation')).toBeVisible();
            await expect(page.getByRole('main')).toBeVisible();
            await expect(page.getByRole('contentinfo')).toBeVisible();

            await expectCopywritingQuality(page, requiredCopy);
            await expectNoHorizontalOverflow(page);
            await expectNoCriticalOverlap(page, [
                'nav',
                'main h1',
                'main h2',
                'main h3',
                'a',
                'footer',
            ]);

            await expectReadableTextContrast(page, 'main h1, main h2, main h3, main h4, main p, button, a');
            await expectClickTargetsUsable(page, 'button, a');

            const tabBeranda = page.getByRole('button', { name: 'Beranda' });
            const tabRingkasan = page.getByRole('button', { name: /Ringkasan/ });
            const tabGrafik = page.getByRole('button', { name: /Grafik/ });

            await expect(tabBeranda).toBeVisible();
            await expect(tabRingkasan).toBeVisible();
            await expect(tabGrafik).toBeVisible();

            await tabRingkasan.click();
            await expect(page.getByRole('heading', { name: /Kategori utama dalam satu panel ringkas|Lihat total utama per kategori zakat/ })).toBeVisible();

            await tabGrafik.click();
            await expect(page.getByRole('heading', { name: 'Grafik penerimaan harian' })).toBeVisible();

            await expect(page.getByRole('button', { name: /Masuk/i })).toBeVisible();
            expect(consoleErrors, `Browser console errors:\n${consoleErrors.join('\n')}`).toEqual([]);
        });
    }
});
