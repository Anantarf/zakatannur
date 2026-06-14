# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: public-home.spec.js >> Halaman Utama Publik (Home) >> halaman utama menampilkan judul dan elemen UI dengan benar
- Location: tests\e2e\public-home.spec.js:4:9

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: getByRole('button', { name: 'Ringkasan Penerimaan' })
Expected: visible
Error: strict mode violation: getByRole('button', { name: 'Ringkasan Penerimaan' }) resolved to 2 elements:
    1) <button @click="activeTab = 'laporan'" title="Ringkasan Penerimaan Zakat" class="px-5 py-2 rounded-full text-sm font-bold transition-all duration-300 text-white/80 hover:text-white hover:bg-white/10" :class="activeTab === 'laporan' ? (scrolled ? 'bg-brand-600 text-white shadow-md' : 'bg-white text-brand-900 shadow-lg') : (scrolled ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50' : 'text-white/80 hover:text-white hover:bg-white/10')">↵                        Ringkasan Penerimaan↵   …</button> aka getByRole('button', { name: 'Ringkasan Penerimaan', exact: true })
    2) <button type="button" x-show="!isIdleMode" @click="activeTab = 'laporan'" class="mt-5 inline-flex items-center rounded-full bg-white px-5 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-black/15 transition hover:bg-brand-50 hover:scale-105">↵                Lihat Ringkasan Penerimaan↵     …</button> aka getByRole('button', { name: 'Lihat Ringkasan Penerimaan' })

Call log:
  - Expect "toBeVisible" with timeout 5000ms
  - waiting for getByRole('button', { name: 'Ringkasan Penerimaan' })

```

# Page snapshot

```yaml
- generic [active] [ref=e1]:
  - navigation [ref=e2]:
    - generic [ref=e4]:
      - link "Logo Zakat Annur Zakat Annur Masjid An-Nur Komplek BPK V Gandul Zakat Annur" [ref=e6] [cursor=pointer]:
        - /url: http://127.0.0.1:8000
        - generic [ref=e7]:
          - img "Logo Zakat Annur" [ref=e8]
          - generic [ref=e9]:
            - generic [ref=e10]: Zakat Annur
            - generic [ref=e11]: Masjid An-Nur Komplek BPK V Gandul
        - generic [ref=e12]: Zakat Annur
      - generic [ref=e13]:
        - button "Beranda" [ref=e14] [cursor=pointer]
        - button "Ringkasan Penerimaan" [ref=e15] [cursor=pointer]
        - button "Grafik Harian" [ref=e16] [cursor=pointer]
      - button "Masuk" [ref=e18] [cursor=pointer]:
        - img [ref=e19]
        - generic [ref=e21]: Masuk
  - main [ref=e22]:
    - generic [ref=e25]:
      - generic [ref=e26]:
        - img "Dokumentasi Masjid An-Nur" [ref=e30]
        - generic [ref=e32]:
          - heading "Zakat yang tercatat, amanah yang terlihat." [level=2] [ref=e33]
          - paragraph [ref=e34]: Informasi penerimaan zakat Masjid An-Nur disajikan terbuka untuk jamaah Komplek BPK V Gandul.
          - button "Lihat Ringkasan Penerimaan" [ref=e35] [cursor=pointer]
      - generic [ref=e36]:
        - generic [ref=e37]:
          - paragraph [ref=e38]: Tentang Portal
          - heading "Satu pintu informasi zakat untuk jamaah" [level=3] [ref=e39]
          - paragraph [ref=e40]: Portal ini membantu jamaah melihat informasi zakat Masjid An-Nur secara terbuka, ringkas, dan mudah dipantau.
        - generic [ref=e41]:
          - article [ref=e42]:
            - img [ref=e44]
            - generic [ref=e46]:
              - heading "Transparan" [level=4] [ref=e47]
              - paragraph [ref=e48]: Jamaah dapat membuka ringkasan penerimaan tanpa menunggu rekap manual.
          - article [ref=e49]:
            - img [ref=e51]
            - generic [ref=e53]:
              - heading "Mudah Dipantau" [level=4] [ref=e54]
              - paragraph [ref=e55]: Kategori zakat, total uang, beras, dan jiwa disusun dalam tampilan ringkas.
          - article [ref=e56]:
            - img [ref=e58]
            - generic [ref=e60]:
              - heading "Amanah" [level=4] [ref=e61]
              - paragraph [ref=e62]: Dikelola panitia zakat Masjid An-Nur untuk kebutuhan periode berjalan.
        - generic [ref=e63] [cursor=pointer]:
          - generic [ref=e64]:
            - img [ref=e66]
            - generic [ref=e68]:
              - paragraph [ref=e69]: Ringkasan Penerimaan
              - paragraph [ref=e70]: Lihat total dan kategori penerimaan zakat dalam ringkasan yang mudah dibaca.
          - img [ref=e72]
        - article [ref=e74]:
          - img [ref=e76]
          - generic [ref=e78]:
            - paragraph [ref=e79]: Pengingat
            - paragraph [ref=e80]: "\"Ambillah zakat dari harta mereka guna membersihkan dan mensucikan mereka, dan berdoalah untuk mereka...\""
            - paragraph [ref=e82]: "— QS. At-Taubah: 103"
  - contentinfo [ref=e83]:
    - generic [ref=e84]:
      - generic [ref=e85]: Powered by
      - generic [ref=e86]:
        - generic [ref=e87]: Ikatan Remaja Komplek BPK V Gandul
        - img "Logo IRK" [ref=e88]
  - button "Buka chatbot Zakky" [ref=e90] [cursor=pointer]:
    - img [ref=e94]
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | test.describe('Halaman Utama Publik (Home)', () => {
  4  |     test('halaman utama menampilkan judul dan elemen UI dengan benar', async ({ page }) => {
  5  |         // Akses halaman utama (baseURL sudah diset di playwright.config.js)
  6  |         await page.goto('/');
  7  | 
  8  |         // 1. Verifikasi Copywriting & Trust Building
  9  |         await expect(page.getByText('Portal Transparansi Zakat')).toBeVisible();
  10 |         await expect(page.getByRole('heading', { name: 'Masjid An-Nur', level: 1 })).toBeVisible();
  11 | 
  12 |         // 2. Verifikasi Tab Navigasi Alpine.js
  13 |         const tabBeranda = page.getByRole('button', { name: 'Beranda' });
  14 |         const tabLaporan = page.getByRole('button', { name: 'Ringkasan Penerimaan' });
  15 |         const tabGrafik = page.getByRole('button', { name: 'Grafik Harian' });
  16 | 
  17 |         await expect(tabBeranda).toBeVisible();
> 18 |         await expect(tabLaporan).toBeVisible();
     |                                  ^ Error: expect(locator).toBeVisible() failed
  19 |         await expect(tabGrafik).toBeVisible();
  20 | 
  21 |         // 3. Tes Interaksi UI: Pindah ke Tab Laporan
  22 |         await tabLaporan.click();
  23 |         
  24 |         await expect(page.getByRole('heading', { name: 'Kategori utama dalam satu panel ringkas' })).toBeVisible();
  25 | 
  26 |         // 4. Verifikasi tombol "Masuk" / Login
  27 |         const btnLogin = page.getByRole('button', { name: /Masuk/ });
  28 |         await expect(btnLogin).toBeVisible();
  29 |     });
  30 | });
  31 | 
```