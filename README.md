# ZakatAnNur (ZAKAT TRIAL)

Aplikasi pencatatan Zakat/Infaq untuk operasional masjid + halaman transparansi publik.

## Stack

- Laravel 9 (PHP 8.0)
- Breeze (Blade)
- Tailwind (via Vite)
- PDF receipt + overlay kop surat (FPDI/TCPDF)

## Fitur MVP (ringkas)

- Input transaksi zakat + cetak receipt PDF
- Rekap internal + export PDF
- Halaman publik ringkasan + API publik ringkasan
- Trash Bin (soft delete) transaksi + restore + audit metadata
- User management sederhana (role-based)
- Template kop surat (PDF-only) dengan versi + aktivasi

## Aturan Bisnis Penting

- Nomor transaksi: `TRX-YYYYMMDD-####` (reset harian)
- Waktu referensi WIB (Asia/Jakarta)
- Rekap menampilkan total campuran: "Rp X + Y Kg"
- Refresh publik: 0 (mati) atau 30–60 detik (default 45)

## Setup Lokal

Prereq: PHP 8.x, Composer, Node.js + npm.

1. Install dependency

- `composer install`
- `npm install`

2. Environment

- `copy .env.example .env`
- `php artisan key:generate`

3. DB + seed

- `php artisan migrate`
- `php artisan db:seed`

4. Jalankan

- Terminal 1: `php artisan serve`
- Terminal 2: `npm run dev`

## Akun Default (Seeder)

Seeder membuat super admin:

- Email: `superadmin@zakatanur.local`
- Password: `password`

Catatan: ganti password ini untuk production.

## Testing

- `php artisan test`

## Ops / Go-Live

Lihat checklist production di [docs/go-live.md](docs/go-live.md).
