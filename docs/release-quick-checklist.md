# Release Quick Checklist

Checklist ini dipakai sebelum merge besar, handoff, atau rilis kecil yang menyentuh behavior aplikasi.

## Minimum Check

1. Jalankan `composer test`
2. Jika ada perubahan Blade, route, atau summary logic, jalankan `composer verify`
3. Jika ada perubahan frontend, jalankan `npm run build`
4. Jika ada migration baru, pastikan sudah dibaca ulang:
   - struktur kolom/index
   - data backfill/cleanup
   - dampak rollback

## Manual Spot Check

- login internal berhasil
- `Riwayat Transaksi` tetap bisa dibuka dan difilter
- `Review Anomali` tetap konsisten antara list, detail, dan update status
- input transaksi dan cetak receipt tetap berjalan
- halaman publik utama tetap render tanpa error

## Diff Hygiene

- pastikan file scratch tidak ikut masuk diff source utama
- pastikan artefak `public/build/` hanya berubah jika memang ada perubahan source frontend yang relevan
- hindari menggabungkan refactor, migration, dan build output besar dalam satu batch kalau tidak perlu

## Decision Gate

Tahan merge atau release jika salah satu ini masih ada:

- test gagal
- behavior list/detail tidak konsisten
- migration belum jelas dampaknya
- worktree terlalu noisy sampai sulit membedakan source dan generated output
