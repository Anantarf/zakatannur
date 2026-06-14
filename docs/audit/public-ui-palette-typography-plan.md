# Public UI Palette & Typography Plan

Status: di-ACC dan dieksekusi

Scope:
- Halaman publik: Beranda, Ringkasan Penerimaan, Grafik Harian.
- Fokus perubahan: color palette dan typography tuning.
- Tidak mengubah alur data, route, validasi, atau logic Alpine.

## Rekomendasi Palet

Pilihan: Opsi A - Deep Teal + Warm Gold

Alasan:
- Tetap dekat dengan identitas hijau masjid, tapi lebih matang dari emerald terang.
- Teal memberi kesan amanah, tenang, dan profesional.
- Gold/amber tetap cocok untuk aksen zakat, beras, highlight, dan status sekunder.
- Bisa dieksekusi lewat token `brand-*`, sehingga perubahan lebih lean dan maintainable.

### Token Brand

```text
Brand 50   #ECFDF5
Brand 100  #D1FAE5
Brand 200  #A7F3D0
Brand 300  #5EEAD4
Brand 400  #2DD4BF
Brand 500  #14B8A6
Brand 600  #0D9488
Brand 700  #0F766E
Brand 800  #115E59
Brand 900  #134E4A
Brand 950  #042F2E
```

### Supporting Colors

```text
Accent Gold #D97706
Gold Soft   #FEF3C7
Background  #F6FAF8
Text Dark   #10201D
```

## Typography Direction

Font family saat ini: Plus Jakarta Sans

Keputusan:
- Tetap pakai Plus Jakarta Sans.
- Jangan ganti font family dulu.
- Rapikan weight, tracking, dan hierarchy agar halaman publik tidak terasa terlalu SaaS/landing-page.

## Typography Cleanup

Yang perlu dirapikan:

- Kurangi `font-black` di halaman publik.
- Hero headline cukup `font-extrabold`.
- Section title cukup `font-bold`.
- Card title cukup `font-bold`, bukan `font-black`.
- Body text gunakan `font-medium` atau normal, jangan terlalu banyak bold.
- Kicker uppercase kurangi tracking dari sekitar `0.24em-0.3em` ke `0.14em-0.18em`.
- Negative tracking besar seperti `-0.05em` diturunkan ke sekitar `-0.02em` sampai `-0.035em`.
- Angka/data di Ringkasan dan Grafik tetap tegas dengan `font-semibold` atau `font-bold`.

## Hierarchy Target

- Hero: elemen visual paling kuat, tapi tidak terlalu poster.
- Intro title: level dua, jelas tapi tidak mengalahkan hero.
- Value card title: level tiga, ringkas.
- Description: tenang dan mudah dibaca.
- CTA: jelas, natural, tidak terasa internal/admin.
- Data area: angka dan kategori tetap paling mudah discan.

## File Terdampak Jika Di-ACC

- `resources/css/app.css`
- `resources/views/public/partials/beranda-hero.blade.php`
- `resources/views/public/partials/beranda-intro.blade.php`
- `resources/views/public/partials/beranda-feature-card.blade.php`
- `resources/views/public/partials/beranda-cta.blade.php`
- `resources/views/public/partials/laporan-tab.blade.php`
- `resources/views/public/partials/grafik-tab.blade.php`

## Verifikasi Setelah Eksekusi

- `npm run build`
- `php artisan view:cache`
- Preview halaman publik desktop dan mobile.
- Cek tab Beranda, Ringkasan Penerimaan, dan Grafik Harian.
