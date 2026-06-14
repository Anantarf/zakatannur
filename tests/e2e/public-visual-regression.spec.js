import { test, expect } from '@playwright/test';

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 390, height: 844 },
];

const states = [
    { name: 'beranda', desktopTab: 'Beranda', mobileTab: 'Beranda', heading: /Informasi zakat yang ringkas, terbuka, dan mudah dipantau\./ },
    { name: 'ringkasan', desktopTab: 'Ringkasan Penerimaan', mobileTab: 'Ringkasan', heading: 'Ringkasan penerimaan' },
    { name: 'grafik', desktopTab: 'Grafik Harian', mobileTab: 'Grafik', heading: /Grafik penerimaan harian/ },
];

const freezeBrowserDate = async (page) => {
    await page.addInitScript(() => {
        const fixedTime = new Date('2026-06-13T10:00:00+07:00').valueOf();
        const NativeDate = Date;

        class FixedDate extends NativeDate {
            constructor(...args) {
                super(...(args.length ? args : [fixedTime]));
            }

            static now() {
                return fixedTime;
            }
        }

        FixedDate.UTC = NativeDate.UTC;
        FixedDate.parse = NativeDate.parse;
        globalThis.Date = FixedDate;
    });
};

const dynamicMasks = (page) => [
    page.locator('[x-text="clock"]'),
    page.locator('.tabular-nums'),
    page.locator('#chart-range-label'),
    page.locator('canvas'),
    page.locator('img'),
];

test.describe('Visual Regression Publik', () => {
    test.describe.configure({ mode: 'serial' });

    for (const viewport of viewports) {
        test(`home konsisten secara visual - ${viewport.name}`, async ({ page }) => {
            await freezeBrowserDate(page);
            await page.setViewportSize({
                width: viewport.width,
                height: viewport.height,
            });

            await page.goto('/');
            await expect(page.getByRole('navigation')).toBeVisible();
            await expect(page.getByRole('main')).toBeVisible();

            for (const state of states) {
                const tabName = viewport.width >= 1024 ? state.desktopTab : state.mobileTab;
                if (tabName !== 'Beranda') {
                    await page.getByRole('button', { name: tabName, exact: true }).click();
                } else {
                    await page.getByRole('button', { name: 'Beranda', exact: true }).click();
                }

                await expect(page.getByRole('heading', { name: state.heading, exact: typeof state.heading === 'string' })).toBeVisible();

                await expect(page).toHaveScreenshot(`public-home-${state.name}-${viewport.name}.png`, {
                    animations: 'disabled',
                    caret: 'hide',
                    fullPage: false,
                    maxDiffPixels: 300,
                    mask: dynamicMasks(page),
                });
            }
        });
    }
});
