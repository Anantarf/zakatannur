import { test, expect } from '@playwright/test';

test.describe('Halaman Utama Publik (Home)', () => {
    test('halaman utama menampilkan judul dan elemen UI dengan benar', async ({ page }) => {
        // Akses halaman utama (baseURL sudah diset di playwright.config.js)
        await page.goto('/');

        // 1. Verifikasi Copywriting & Trust Building
        await expect(page.getByText('Portal Transparansi Zakat')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Masjid An-Nur', level: 1 })).toBeVisible();

        // 2. Verifikasi Tab Navigasi Alpine.js
        const tabBeranda = page.getByRole('button', { name: 'Beranda' });
        const tabLaporan = page.getByRole('button', { name: 'Ringkasan Penerimaan' });
        const tabGrafik = page.getByRole('button', { name: 'Grafik Harian' });

        await expect(tabBeranda).toBeVisible();
        await expect(tabLaporan).toBeVisible();
        await expect(tabGrafik).toBeVisible();

        // 3. Tes Interaksi UI: Pindah ke Tab Laporan
        await tabLaporan.click();
        
        await expect(page.getByRole('heading', { name: 'Kategori utama dalam satu panel ringkas' })).toBeVisible();

        // 4. Verifikasi tombol "Masuk" / Login
        const btnLogin = page.getByRole('button', { name: /Masuk/ });
        await expect(btnLogin).toBeVisible();
    });
});
