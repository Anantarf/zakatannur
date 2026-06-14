# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: public-visual-regression.spec.js >> Visual Regression Publik >> home konsisten secara visual - mobile
- Location: tests\e2e\public-visual-regression.spec.js:45:13

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: getByRole('heading', { name: /Masjid An-Nur/ })
Expected: visible
Timeout: 5000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" with timeout 5000ms
  - waiting for getByRole('heading', { name: /Masjid An-Nur/ })

```

```yaml
- navigation:
  - link "Logo Zakat Annur Zakat Annur Masjid An-Nur Komplek BPK V Gandul Zakat Annur":
    - /url: http://127.0.0.1:8000
    - img "Logo Zakat Annur"
    - text: Zakat Annur Masjid An-Nur Komplek BPK V Gandul Zakat Annur
  - button "MASUK":
    - img
  - button "Beranda"
  - button "Ringkasan"
  - button "Grafik"
- main:
  - img "Dokumentasi Masjid An-Nur"
  - heading "Zakat yang tercatat, amanah yang terlihat." [level=2]
  - paragraph: Informasi penerimaan zakat Masjid An-Nur disajikan terbuka untuk jamaah Komplek BPK V Gandul.
  - button "Lihat Ringkasan Penerimaan"
  - paragraph: Tentang Portal
  - heading "Satu pintu informasi zakat untuk jamaah" [level=3]
  - paragraph: Portal ini membantu jamaah melihat informasi zakat Masjid An-Nur secara terbuka, ringkas, dan mudah dipantau.
  - article:
    - heading "Transparan" [level=4]
    - paragraph: Jamaah dapat membuka ringkasan penerimaan tanpa menunggu rekap manual.
  - article:
    - heading "Mudah Dipantau" [level=4]
    - paragraph: Kategori zakat, total uang, beras, dan jiwa disusun dalam tampilan ringkas.
  - article:
    - heading "Amanah" [level=4]
    - paragraph: Dikelola panitia zakat Masjid An-Nur untuk kebutuhan periode berjalan.
  - img
  - paragraph: Ringkasan Penerimaan
  - paragraph: Lihat total dan kategori penerimaan zakat dalam ringkasan yang mudah dibaca.
  - img
  - article:
    - img
    - paragraph: Pengingat
    - paragraph: "\"Ambillah zakat dari harta mereka guna membersihkan dan mensucikan mereka, dan berdoalah untuk mereka...\""
    - paragraph: "— QS. At-Taubah: 103"
- contentinfo:
  - text: Powered by Ikatan Remaja Komplek BPK V Gandul
  - img "Logo IRK"
- button "Buka chatbot Zakky"
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | const viewports = [
  4  |     { name: 'desktop', width: 1440, height: 900 },
  5  |     { name: 'tablet', width: 768, height: 1024 },
  6  |     { name: 'mobile', width: 390, height: 844 },
  7  | ];
  8  | 
  9  | const states = [
  10 |     { name: 'beranda', tab: 'Beranda', heading: /Masjid An-Nur/ },
  11 |     { name: 'ringkasan', tab: /Ringkasan/, heading: /Kategori utama dalam satu panel ringkas|Lihat total utama per kategori zakat/ },
  12 |     { name: 'grafik', tab: /Grafik/, heading: /Grafik penerimaan harian/ },
  13 | ];
  14 | 
  15 | const freezeBrowserDate = async (page) => {
  16 |     await page.addInitScript(() => {
  17 |         const fixedTime = new Date('2026-06-13T10:00:00+07:00').valueOf();
  18 |         const NativeDate = Date;
  19 | 
  20 |         class FixedDate extends NativeDate {
  21 |             constructor(...args) {
  22 |                 super(...(args.length ? args : [fixedTime]));
  23 |             }
  24 | 
  25 |             static now() {
  26 |                 return fixedTime;
  27 |             }
  28 |         }
  29 | 
  30 |         FixedDate.UTC = NativeDate.UTC;
  31 |         FixedDate.parse = NativeDate.parse;
  32 |         globalThis.Date = FixedDate;
  33 |     });
  34 | };
  35 | 
  36 | const dynamicMasks = (page) => [
  37 |     page.locator('[x-text="clock"]'),
  38 |     page.locator('.tabular-nums'),
  39 |     page.locator('#chart-range-label'),
  40 |     page.locator('canvas'),
  41 | ];
  42 | 
  43 | test.describe('Visual Regression Publik', () => {
  44 |     for (const viewport of viewports) {
  45 |         test(`home konsisten secara visual - ${viewport.name}`, async ({ page }) => {
  46 |             await freezeBrowserDate(page);
  47 |             await page.setViewportSize({
  48 |                 width: viewport.width,
  49 |                 height: viewport.height,
  50 |             });
  51 | 
  52 |             await page.goto('/');
  53 |             await expect(page.getByRole('navigation')).toBeVisible();
  54 |             await expect(page.getByRole('main')).toBeVisible();
  55 | 
  56 |             for (const state of states) {
  57 |                 if (state.tab !== 'Beranda') {
  58 |                     await page.getByRole('button', { name: state.tab }).click();
  59 |                 } else {
  60 |                     await page.getByRole('button', { name: 'Beranda' }).click();
  61 |                 }
  62 | 
> 63 |                 await expect(page.getByRole('heading', { name: state.heading })).toBeVisible();
     |                                                                                  ^ Error: expect(locator).toBeVisible() failed
  64 | 
  65 |                 await expect(page).toHaveScreenshot(`public-home-${state.name}-${viewport.name}.png`, {
  66 |                     animations: 'disabled',
  67 |                     caret: 'hide',
  68 |                     fullPage: true,
  69 |                     mask: dynamicMasks(page),
  70 |                 });
  71 |             }
  72 |         });
  73 |     }
  74 | });
  75 | 
```