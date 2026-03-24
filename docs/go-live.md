# Go-Live / Ops Checklist (ZakatAnNur)

Dokumen ini fokus ke hal-hal minimum biar aplikasi aman dan stabil saat dipakai.

## 1) Konfigurasi Production

- Set environment:
    - `APP_ENV=production`
    - `APP_DEBUG=false`
    - `APP_KEY` terisi (generate kalau belum)
- Set base URL:
    - `APP_URL=https://...`
- Pastikan timezone aplikasi konsisten (aturan bisnis pakai WIB/Asia/Jakarta).

## 2) Cache / Optimize (wajib setelah ubah env/config)

Jalankan:

- `php artisan optimize:clear`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`

Catatan:

- Rate limiting akan lebih stabil bila cache driver di production bukan `array` (gunakan `file` atau lebih baik `redis`).

## 3) Database Migration

- Pastikan backup sudah ada.
- Jalankan migration (production):
    - `php artisan migrate --force`

## 4) Storage & Permission

- Pastikan folder writable:
    - `storage/`
    - `bootstrap/cache/`
- Untuk file template kop surat (PDF) dan kebutuhan file lainnya, pastikan disk `local` berfungsi sesuai setup server.

## 5) Akun & Akses

- Self-registration nonaktif; siapkan akun awal (admin/super_admin) lewat seeding/manual sesuai prosedur internal.
- Pastikan role user sesuai: `staff`, `admin`, `super_admin`.

## 6) Backup & Restore (minimum viable)

Backup rutin minimal:

- File database (sesuai DB yang dipakai di server)
- Folder `storage/app/templates/` (template kop surat)

Uji restore minimal (staging atau lokal):

- Restore DB
- Restore `storage/app/templates/`
- Jalankan app dan pastikan:
    - halaman publik summary OK
    - cetak receipt PDF OK

## 7) Verifikasi Cepat Setelah Go-Live

- Login berhasil (cek limiter login tidak mengganggu penggunaan normal)
- `/summary` tampil dan auto-refresh sesuai setting
- `/api/public/summary` mengembalikan JSON valid
- Input transaksi & cetak receipt berjalan
- Trash/restore transaksi berjalan
- Rekap internal & export PDF berjalan
