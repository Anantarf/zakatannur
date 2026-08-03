import { test, expect } from '@playwright/test';

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 390, height: 844 },
];

const states = [
    { name: 'beranda', desktopTab: 'Beranda', mobileTab: 'Beranda', heading: /Informasi zakat yang ringkas, terbuka, dan mudah dipantau\./ },
    { name: 'ringkasan', desktopTab: 'Ringkasan Penerimaan', mobileTab: 'Ringkasan', heading: 'Ringkasan penerimaan', readyText: 'Zakat Fitrah' },
    { name: 'grafik', desktopTab: 'Grafik Harian', mobileTab: 'Grafik', heading: /Grafik penerimaan harian/, readyText: 'Total penerimaan uang' },
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

const useE2eThrottleKey = async (page, key) => {
    await page.route('http://127.0.0.1:8000/**', async (route) => {
        await route.continue({
            headers: {
                ...route.request().headers(),
                'X-E2E-Test-Run': key,
            },
        });
    });
};

const expectVisibleText = async (page, text) => {
    await expect.poll(async () => page.getByText(text, { exact: true }).evaluateAll((elements) => (
        elements.filter((element) => {
            const rect = element.getBoundingClientRect();
            const style = window.getComputedStyle(element);

            return rect.width > 0
                && rect.height > 0
                && style.visibility !== 'hidden'
                && style.display !== 'none';
        }).length
    ))).toBeGreaterThan(0);
};

const dynamicMasks = (page) => [
    page.locator('[x-text="clock"]'),
    page.locator('.tabular-nums'),
    page.locator('#chart-range-label'),
    page.locator('[x-text="latestTransactionAgeLabel"]'),
    page.locator('.public-chart-metric'),
    page.locator('.public-chart-card'),
    page.locator('[data-chatbot-widget]'),
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
            await useE2eThrottleKey(page, `public-visual-${viewport.name}`);

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
                if (state.readyText) {
                    await expectVisibleText(page, state.readyText);
                }
                await page.waitForTimeout(650);

                await expect(page.locator('main')).toHaveScreenshot(`public-home-${state.name}-${viewport.name}.png`, {
                    animations: 'disabled',
                    caret: 'hide',
                    maxDiffPixels: 8000,
                    mask: dynamicMasks(page),
                });
            }
        });
    }
});
