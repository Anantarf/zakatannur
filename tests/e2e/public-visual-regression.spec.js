import { test, expect } from '@playwright/test';

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 390, height: 844 },
];

const states = [
    { name: 'beranda', tab: 'Beranda', heading: /Masjid An-Nur/ },
    { name: 'ringkasan', tab: /Ringkasan/, heading: /Kategori utama dalam satu panel ringkas|Lihat total utama per kategori zakat/ },
    { name: 'grafik', tab: /Grafik/, heading: /Grafik penerimaan harian/ },
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
];

test.describe('Visual Regression Publik', () => {
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
                if (state.tab !== 'Beranda') {
                    await page.getByRole('button', { name: state.tab }).click();
                } else {
                    await page.getByRole('button', { name: 'Beranda' }).click();
                }

                await expect(page.getByRole('heading', { name: state.heading })).toBeVisible();

                await expect(page).toHaveScreenshot(`public-home-${state.name}-${viewport.name}.png`, {
                    animations: 'disabled',
                    caret: 'hide',
                    fullPage: true,
                    mask: dynamicMasks(page),
                });
            }
        });
    }
});
