# Checklist Risk Review

Checklist ini melacak progres implementasi **Paket 1: Transaction Review Assistant untuk History dan Detail**.

## Sudah

- [x] Buat tabel `transaction_risk_reviews`
- [x] Buat model `TransactionRiskReview`
- [x] Buat `TransactionRiskAnalyzer`
- [x] Buat `DuplicateTransactionDetector`
- [x] Buat `TransactionAnomalyDetector`
- [x] Buat `TransactionReviewAssistantService`
- [x] Integrasi analisis ke flow create/update di `ZakatService`
- [x] Simpan hasil analisis ke tabel review risiko
- [x] Tambah badge `risk_level` di history
- [x] Tambah badge `review_status` di history
- [x] Tambah filter `risk_level` di history
- [x] Tambah filter `review_status` di history
- [x] Tambah panel `Review Risiko` di detail transaksi
- [x] Tampilkan alasan deteksi di detail
- [x] Tampilkan kandidat transaksi mirip di detail
- [x] Tambah update `review_status` operator di detail
- [x] Catat `reviewed_by` dan `reviewed_at`
- [x] Tambah endpoint `GET /risk-review`
- [x] Tambah endpoint `PATCH /risk-review-status`
- [x] Tambah test fitur untuk review risiko
- [x] Verifikasi full test suite hijau

## Belum

- [x] Backfill review risiko untuk transaksi lama yang belum pernah disentuh
- [x] Rapikan visual hierarchy history setelah kolom risiko ditambahkan
- [x] Rapikan visual hierarchy panel review di detail supaya lebih product-finished
- [x] Tambah summary kecil jumlah transaksi berisiko di area atas history
- [x] Tambah authorization test spesifik untuk endpoint `risk-review-status`
- [x] Tambah audit log khusus saat operator mengubah `review_status` jika memang diinginkan
- [x] Dokumentasi user flow operator untuk fitur review risiko

## Catatan

- Fitur review sudah aktif untuk transaksi baru dan transaksi yang di-update.
- Transaksi lama yang belum punya review bisa dibackfill lewat command `php artisan transactions:backfill-risk-reviews`.
