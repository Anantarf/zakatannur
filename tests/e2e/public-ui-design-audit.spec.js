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
        'Portal Zakat Masjid An-Nur',
        'Informasi zakat yang ringkas, terbuka, dan mudah dipantau.',
        'Transparan',
        'Pengingat',
    ],
};

const tabButton = (page, viewport, desktopName, mobileName) => (
    viewport.width >= 1024
        ? page.getByRole('button', { name: desktopName, exact: true })
        : page.getByRole('button', { name: mobileName, exact: true })
);

test.describe('Audit UI Design Publik', () => {
    for (const viewport of viewports) {
        test(`home memenuhi standar copywriting, warna, layout, UI, UX, responsive - ${viewport.name}`, async ({ page }) => {
            const consoleErrors = [];

            page.on('console', (message) => {
                if (message.type() === 'error') {
                    const text = message.text();
                    if (!text.includes('429 (Too Many Requests)')) {
                        consoleErrors.push(text);
                    }
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

            await expectReadableTextContrast(page, 'main h1, main h2, main h3, main h4, main p, button:not(.public-tab-chip-active):not(.public-tab-chip-mobile-active), a');
            await expectClickTargetsUsable(page, 'button, a');

            const tabBeranda = tabButton(page, viewport, 'Beranda', 'Beranda');
            const tabRingkasan = tabButton(page, viewport, 'Ringkasan Penerimaan', 'Ringkasan');
            const tabGrafik = tabButton(page, viewport, 'Grafik Harian', 'Grafik');

            await expect(tabBeranda).toBeVisible();
            await expect(tabRingkasan).toBeVisible();
            await expect(tabGrafik).toBeVisible();

            await tabRingkasan.click();
            await expect(page.getByRole('heading', { name: 'Ringkasan penerimaan', exact: true })).toBeVisible();

            await tabGrafik.click();
            await expect(page.getByRole('heading', { name: 'Grafik penerimaan harian' })).toBeVisible();

            await expect(page.getByTitle('MASUK')).toBeVisible();
            expect(consoleErrors, `Browser console errors:\n${consoleErrors.join('\n')}`).toEqual([]);
        });
    }
});
