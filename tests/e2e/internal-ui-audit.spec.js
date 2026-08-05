import { test, expect } from '@playwright/test';
import {
    expectClickTargetsUsable,
    expectNoCriticalOverlap,
    expectNoHorizontalOverflow,
} from './helpers/ui-audit';

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 390, height: 844 },
];

const pages = [
    { name: 'dashboard', path: '/dashboard' },
    { name: 'riwayat-transaksi', path: '/internal/history' },
    { name: 'muzakki', path: '/internal/muzakki' },
    { name: 'anomali', path: '/internal/anomalies' },
    { name: 'audit-log', path: '/internal/audit-logs' },
    { name: 'pengguna', path: '/internal/users' },
    { name: 'knowledge-base', path: '/internal/knowledge-base' },
];

const login = async (page) => {
    await page.goto('/login');
    await page.fill('#username', 'superadmin');
    await page.fill('#password', 'password');
    await page.getByRole('button', { name: 'Masuk' }).click();
    await expect(page).toHaveURL(/dashboard/);
};

test.describe('Audit Responsivitas Halaman Internal', () => {
    for (const viewport of viewports) {
        test(`halaman internal tidak overflow/overlap - ${viewport.name}`, async ({ page }) => {
            await page.setViewportSize({ width: viewport.width, height: viewport.height });
            await login(page);

            for (const target of pages) {
                await page.goto(target.path);
                await expect(page.getByRole('main')).toBeVisible();

                await expectNoHorizontalOverflow(page);
                await expectNoCriticalOverlap(page, ['nav', 'main h1', 'main h2', 'main h3', 'table', 'a.ui-btn']);
                await expectClickTargetsUsable(page, 'main button, main a.ui-btn');
            }
        });
    }
});
