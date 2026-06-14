# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: public-ui-design-audit.spec.js >> Audit UI Design Publik >> home memenuhi standar copywriting, warna, layout, UI, UX, responsive - desktop
- Location: tests\e2e\public-ui-design-audit.spec.js:27:13

# Error details

```
Error: Required copy is not visible: Portal Transparansi Zakat

expect(received).toBeGreaterThan(expected)

Expected: > 0
Received:   0
```

```
Error: Required copy is not visible: Laporan penerimaan zakat real-time yang transparan dan akuntabel.

expect(received).toBeGreaterThan(expected)

Expected: > 0
Received:   0
```

```
Error: Required copy is not visible: Transparansi Penerimaan Zakat

expect(received).toBeGreaterThan(expected)

Expected: > 0
Received:   0
```

```
Error: Critical UI overlap found:
nav "Zakat Annur Masjid An-Nur Komplek BPK V Gandul Zakat Annur Beranda Ringkasan Pen" overlaps main h2 "Zakat yang tercatat, amanah yang terlihat."

expect(received).toEqual(expected) // deep equality

- Expected  - 1
+ Received  + 3

- Array []
+ Array [
+   "nav \"Zakat Annur Masjid An-Nur Komplek BPK V Gandul Zakat Annur Beranda Ringkasan Pen\" overlaps main h2 \"Zakat yang tercatat, amanah yang terlihat.\"",
+ ]
```

```
Error: expect(locator).toBeVisible() failed

Locator: getByRole('button', { name: /Ringkasan/ })
Expected: visible
Error: strict mode violation: getByRole('button', { name: /Ringkasan/ }) resolved to 2 elements:
    1) <button @click="activeTab = 'laporan'" title="Ringkasan Penerimaan Zakat" class="px-5 py-2 rounded-full text-sm font-bold transition-all duration-300 text-white/80 hover:text-white hover:bg-white/10" :class="activeTab === 'laporan' ? (scrolled ? 'bg-brand-600 text-white shadow-md' : 'bg-white text-brand-900 shadow-lg') : (scrolled ? 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50' : 'text-white/80 hover:text-white hover:bg-white/10')">↵                        Ringkasan Penerimaan↵   …</button> aka getByRole('button', { name: 'Ringkasan Penerimaan', exact: true })
    2) <button type="button" x-show="!isIdleMode" @click="activeTab = 'laporan'" class="mt-5 inline-flex items-center rounded-full bg-white px-5 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-black/15 transition hover:bg-brand-50 hover:scale-105">↵                Lihat Ringkasan Penerimaan↵     …</button> aka getByRole('button', { name: 'Lihat Ringkasan Penerimaan' })

Call log:
  - Expect "toBeVisible" with timeout 5000ms
  - waiting for getByRole('button', { name: /Ringkasan/ })

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
  2  | import {
  3  |     expectClickTargetsUsable,
  4  |     expectCopywritingQuality,
  5  |     expectNoCriticalOverlap,
  6  |     expectNoHorizontalOverflow,
  7  |     expectReadableTextContrast,
  8  | } from './helpers/ui-audit';
  9  | 
  10 | const viewports = [
  11 |     { name: 'desktop', width: 1440, height: 900 },
  12 |     { name: 'tablet', width: 768, height: 1024 },
  13 |     { name: 'mobile', width: 390, height: 844 },
  14 | ];
  15 | 
  16 | const requiredCopy = {
  17 |     required: [
  18 |         'Portal Transparansi Zakat',
  19 |         'Masjid An-Nur',
  20 |         'Laporan penerimaan zakat real-time yang transparan dan akuntabel.',
  21 |         'Transparansi Penerimaan Zakat',
  22 |     ],
  23 | };
  24 | 
  25 | test.describe('Audit UI Design Publik', () => {
  26 |     for (const viewport of viewports) {
  27 |         test(`home memenuhi standar copywriting, warna, layout, UI, UX, responsive - ${viewport.name}`, async ({ page }) => {
  28 |             const consoleErrors = [];
  29 | 
  30 |             page.on('console', (message) => {
  31 |                 if (message.type() === 'error') {
  32 |                     consoleErrors.push(message.text());
  33 |                 }
  34 |             });
  35 | 
  36 |             await page.setViewportSize({
  37 |                 width: viewport.width,
  38 |                 height: viewport.height,
  39 |             });
  40 | 
  41 |             await page.goto('/');
  42 | 
  43 |             await expect(page.getByRole('navigation')).toBeVisible();
  44 |             await expect(page.getByRole('main')).toBeVisible();
  45 |             await expect(page.getByRole('contentinfo')).toBeVisible();
  46 | 
  47 |             await expectCopywritingQuality(page, requiredCopy);
  48 |             await expectNoHorizontalOverflow(page);
  49 |             await expectNoCriticalOverlap(page, [
  50 |                 'nav',
  51 |                 'main h1',
  52 |                 'main h2',
  53 |                 'main h3',
  54 |                 'a',
  55 |                 'footer',
  56 |             ]);
  57 | 
  58 |             await expectReadableTextContrast(page, 'main h1, main h2, main h3, main h4, main p, button, a');
  59 |             await expectClickTargetsUsable(page, 'button, a');
  60 | 
  61 |             const tabBeranda = page.getByRole('button', { name: 'Beranda' });
  62 |             const tabRingkasan = page.getByRole('button', { name: /Ringkasan/ });
  63 |             const tabGrafik = page.getByRole('button', { name: /Grafik/ });
  64 | 
  65 |             await expect(tabBeranda).toBeVisible();
> 66 |             await expect(tabRingkasan).toBeVisible();
     |                                        ^ Error: expect(locator).toBeVisible() failed
  67 |             await expect(tabGrafik).toBeVisible();
  68 | 
  69 |             await tabRingkasan.click();
  70 |             await expect(page.getByRole('heading', { name: /Kategori utama dalam satu panel ringkas|Lihat total utama per kategori zakat/ })).toBeVisible();
  71 | 
  72 |             await tabGrafik.click();
  73 |             await expect(page.getByRole('heading', { name: 'Grafik penerimaan harian' })).toBeVisible();
  74 | 
  75 |             await expect(page.getByRole('button', { name: /Masuk/i })).toBeVisible();
  76 |             expect(consoleErrors, `Browser console errors:\n${consoleErrors.join('\n')}`).toEqual([]);
  77 |         });
  78 |     }
  79 | });
  80 | 
```