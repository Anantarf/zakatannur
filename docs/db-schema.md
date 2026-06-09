# Database Schema Reference

Dokumen ini merupakan ringkasan skema database utama pada proyek **Zakat Annur**. Dokumen ini dirancang sebagai acuan (*context*) bagi AI Assistant agar tidak perlu menebak-nebak struktur tabel saat mengembangkan fitur.

*(Terakhir diupdate: Juni 2026 berdasarkan file migration.)*

---

## 1. Tabel `users`
Menyimpan data otentikasi dan profil pengguna aplikasi (Petugas / Admin / Super Admin).
- `id` (PK)
- `name` (string)
- `email` (string, unique)
- `password` (string)
- `role` (string) - *admin, super_admin, petugas, dll.*
- `remember_token`
- `timestamps`

## 2. Tabel `muzakki`
Menyimpan data identitas pembayar zakat utama.
- `id` (PK)
- `name` (string, 150) - *Memiliki index*
- `address` (text, nullable)
- `phone` (string, 30, nullable) - *Memiliki index*
- `timestamps`
- `deleted_at` (soft deletes)

## 3. Tabel `zakat_transactions`
Tabel transaksi utama untuk penerimaan Zakat, Infaq, Sedekah, Fidyah, dll.
- `id` (PK)
- `no_transaksi` (string, 30) - *Bisa duplikat jika multiple item dalam 1 struk (lihat revisi migration), atau unique tergantung konfigurasi terbaru*
- `muzakki_id` (FK -> muzakki)
- `pembayar_nama`, `pembayar_no_hp` dsb. *(ditambahkan via alter table)*
- `category` (string, 10) - *zakat_fitrah, zakat_maal, infaq, fidyah, dll.*
- `tahun_zakat` (unsignedInteger)
- `metode` (string, 10) - *uang, beras*
- `is_transfer` (boolean)
- `nominal_uang` (unsignedBigInteger, nullable)
- `jumlah_beras_kg` (decimal 10,2, nullable)
- `jiwa` (unsignedInteger, nullable) - *Jumlah jiwa untuk Zakat Fitrah*
- `hari` (unsignedInteger, nullable) - *Jumlah hari untuk Fidyah*
- `is_khusus` (boolean)
- `default_fitrah_cash_per_jiwa_used` (unsignedInteger, nullable)
- `default_fidyah_per_hari_used` (unsignedInteger, nullable)
- `shift` (string, nullable)
- `petugas_id` (FK -> users)
- `keterangan` (text, nullable)
- `status` (string, 10) - *valid, void, dll.*
- `void_reason` (text, nullable)
- `voided_at` (timestamp, nullable)
- `voided_by` (FK -> users, nullable)
- `waktu_terima` (timestamp, nullable)
- `timestamps`
- `deleted_at` (soft deletes, *trash fields*)

## 4. Tabel `annual_settings`
Menyimpan konfigurasi tarif zakat fitrah & fidyah yang berlaku per tahun.
- `id` (PK)
- `year` (unsignedInteger, unique)
- `default_fitrah_cash_per_jiwa` (unsignedInteger)
- `default_fidyah_per_hari` (unsignedInteger)
- `beras_per_jiwa` (decimal) *(ditambahkan via alter table)*
- `fidyah_beras_per_hari` (decimal) *(ditambahkan via alter table)*
- `chart_window` dsb. *(ditambahkan via alter table)*
- `timestamps`

## 5. Tabel `transaction_risk_reviews`
Menyimpan hasil deteksi anomali/risiko pada transaksi tertentu.
- `id` (PK)
- `zakat_transaction_id` (FK -> zakat_transactions, unique)
- `group_no_transaksi` (string, 30)
- `risk_level` (string, 20) - *misalnya: warning*
- `risk_score` (unsignedInteger)
- `risk_flags` (json, nullable)
- `reasons` (json, nullable)
- `duplicate_candidates` (json, nullable)
- `review_note` (text, nullable)
- `detector_version` (string, 20)
- `review_status` (string, 30) - *belum_ditinjau, aman, dll.*
- `reviewed_by` (FK -> users, nullable)
- `reviewed_at` (timestamp, nullable)
- `checked_at` (timestamp, nullable)
- `timestamps`

---
> **Catatan untuk AI**: Jika perlu membuat join, pastikan relasi `muzakki_id` dari `zakat_transactions` tidak asumsikan data muzakki selalu utuh, perhatikan `softDeletes`. Selalu rujuk kolom di dokumen ini sebelum menulis query raw/Eloquent.
