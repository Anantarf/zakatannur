# Dokumentasi Sistem Anomaly Review

## Ringkasan

Sistem **Anomaly Review** adalah mekanisme deteksi risiko otomatis yang berjalan di balik layar setiap kali transaksi zakat disimpan, diperbarui, atau di-restore. Sistem ini dirancang untuk menangkap aktivitas yang tidak wajar — seperti transaksi ganda, manipulasi data setelah kwitansi dicetak, atau nominal yang jauh di atas kebiasaan — sehingga petugas dan admin dapat meninjau dan mengambil tindakan sebelum menjadi masalah nyata.

---

## Arsitektur & Alur Data

```
┌─────────────────────────┐
│   User Action (Trigger) │
│  ┌───────────────────┐  │
│  │ Input Transaksi   │  │  ZakatService::syncTransactions()
│  │ Edit Transaksi    │──┼──────────────────────────────────┐
│  │ Restore Transaksi │  │  TransactionGroupLifecycleService │
│  └───────────────────┘  │  ::restoreGroup()                 │
└─────────────────────────┘                                   │
                                                              ▼
                              ┌────────────────────────────────────────┐
                              │  TransactionReviewAssistantService     │
                              │  ::syncForTransactions($transactions)  │
                              │                                        │
                              │  Untuk setiap transaksi:               │
                              │  ┌──────────────────────────────────┐  │
                              │  │ TransactionRiskAnalyzer::analyze │  │
                              │  │ ┌──────────────────────────────┐ │  │
                              │  │ │ DuplicateTransactionDetector │ │  │
                              │  │ │ (deteksi transaksi ganda)    │ │  │
                              │  │ ├──────────────────────────────┤ │  │
                              │  │ │ TransactionAnomalyDetector   │ │  │
                              │  │ │ (deteksi anomali operasional │ │  │
                              │  │ │  + statistical outlier)      │ │  │
                              │  │ └──────────────────────────────┘ │  │
                              │  └──────────────────────────────────┘  │
                              │                                        │
                              │  Hasil disimpan ke tabel:              │
                              │  transaction_risk_reviews              │
                              └────────────────────────────────────────┘
                                                              │
                                                              ▼
                              ┌────────────────────────────────────────┐
                              │  UI: Halaman Anomaly Review            │
                              │  (internal.anomalies.index / .show)    │
                              │                                        │
                              │  Admin meninjau dan memberi status:     │
                              │  - Belum Ditinjau                      │
                              │  - Aman                                │
                              │  - Perlu Tindak Lanjut                 │
                              └────────────────────────────────────────┘
```

---

## Kapan Sistem Ini Ter-trigger?

Sistem deteksi anomali **TIDAK** berjalan secara terjadwal (cron). Ia ter-trigger secara **real-time** saat terjadi aksi operasional berikut:

| Aksi Pengguna | Lokasi Kode (Trigger Point) | Apa yang Terjadi |
|---|---|---|
| **Input transaksi baru** | `ZakatService::syncTransactions()` (line 94) | Setiap row transaksi baru dianalisis untuk duplikasi dan outlier. |
| **Edit transaksi** | `ZakatService::syncTransactions()` (line 83-94) | Context `updated_after_receipt_printed` dan `significant_nominal_change` disisipkan, lalu dianalisis ulang. |
| **Restore transaksi** dari trash | `TransactionGroupLifecycleService::restoreGroup()` (line 110-116) | Context `restored_after_delete` disisipkan, lalu dianalisis. |
| **Backfill manual** (CLI) | `php artisan transactions:backfill-risk-reviews` | Menganalisis semua transaksi lama yang belum punya record review. Berguna untuk data migrasi. |

---

## Jenis Deteksi (Flag Types)

Setiap transaksi yang terdeteksi anomali akan diberi satu atau lebih **flag** beserta **skor risiko** numerik. Berikut adalah semua jenis flag yang aktif di sistem ini:

### 1. `exact_duplicate` — Potensi Transaksi Ganda
- **Detektor**: `DuplicateTransactionDetector`
- **Skor**: +60 (exact match), +40–50 (partial match)
- **Cara Kerja**: Mencari transaksi lain dalam **window ±30 menit** yang memiliki muzakki, pembayar, metode, kategori, dan nominal yang **identik**.
- **Tujuan**: Menangkap kasus petugas tidak sengaja menekan tombol "Simpan" dua kali atau jamaah yang tercatat ganda.

### 2. `updated_after_receipt_printed` — Diubah Setelah Kwitansi Tercetak
- **Detektor**: `TransactionAnomalyDetector`
- **Skor**: +30
- **Cara Kerja**: Jika transaksi diedit (`noTransaksiOverride != null`) DAN field `receipt_printed_at` sudah terisi, flag ini aktif.
- **Tujuan**: Menandai potensi perbedaan antara bukti fisik yang sudah beredar di tangan jamaah dengan data digital yang berubah.

### 3. `significant_nominal_change` — Perubahan Nominal Signifikan
- **Detektor**: `TransactionAnomalyDetector`
- **Skor**: +35
- **Cara Kerja**: Saat edit, sistem membandingkan total uang/beras sebelum dan sesudah. Jika selisihnya melebihi **50% dari nilai tertinggi** (atau minimal Rp50.000 / 2.5 kg), flag ini aktif.
- **Tujuan**: Menangkap perubahan besar yang bisa mengindikasikan kesalahan input atau manipulasi.

### 5. `statistical_outlier` — Outlier Statistik *(BARU)*
- **Detektor**: `TransactionAnomalyDetector`
- **Skor**: +15
- **Cara Kerja**: Menghitung rata-rata (`AVG`) nominal uang dari **seluruh transaksi aktif** (di-cache 5 menit). Jika nominal transaksi saat ini melebihi **5 kali lipat rata-rata**, flag ini aktif.
- **Tujuan**: Menangkap typo salah ketik angka nol (misal: Rp 10.000.000 padahal maksudnya Rp 100.000) atau donasi besar yang perlu dikonfirmasi.
- **Syarat Minimum**: Hanya aktif jika sudah ada minimal **10 transaksi uang** di database (untuk memastikan rata-rata cukup representatif).

---

## Komponen Kode

### Model & Database

| File | Peran |
|---|---|
| `app/Models/TransactionRiskReview.php` | Eloquent model untuk tabel `transaction_risk_reviews`. Menyimpan hasil analisis per-transaksi: risk_level, risk_score, risk_flags, reasons, review_status. |
| `database/migrations/*_create_transaction_risk_reviews_table.php` | Schema tabel penyimpanan hasil deteksi. |

### Service Layer (Inti)

| File | Peran |
|---|---|
| `app/Services/Transactions/TransactionRiskAnalyzer.php` | **Orchestrator**. Memanggil kedua detektor (Duplicate + Anomaly), menggabungkan skor dan flag, menentukan level risiko (`normal` / `warning`). |
| `app/Services/Transactions/DuplicateTransactionDetector.php` | Mendeteksi transaksi ganda berdasarkan kecocokan muzakki, pembayar, nominal, dan jendela waktu ±30 menit. |
| `app/Services/Transactions/TransactionAnomalyDetector.php` | Mendeteksi anomali operasional: edit setelah cetak, restore setelah hapus, perubahan nominal besar, dan **outlier statistik**. |
| `app/Services/Transactions/TransactionReviewAssistantService.php` | **Penghubung utama** antara detektor dan database. Method `syncForTransactions()` dipanggil oleh trigger point. Juga menyediakan query summary untuk UI dan API status update. |
| `app/Services/Transactions/TransactionAnomalyService.php` | Menyediakan data untuk **halaman UI Anomaly Review**: filtering, pagination, overview KPI, dan detail view. Memiliki `FLAG_META` untuk label & panduan user. |

### Trigger Points

| File | Method | Saat Apa |
|---|---|---|
| `app/Services/ZakatService.php` | `syncTransactions()` | Input baru & edit transaksi |
| `app/Services/Transactions/TransactionGroupLifecycleService.php` | `restoreGroup()` | Restore dari trash |
| `app/Console/Commands/BackfillTransactionRiskReviews.php` | `handle()` | CLI: backfill data lama |

### UI (Blade Views)

| File | Peran |
|---|---|
| `resources/views/internal/anomalies/index.blade.php` | Daftar semua grup transaksi yang ter-flag anomali (Active/Archived). |
| `resources/views/internal/anomalies/show.blade.php` | Detail review satu grup: alasan flag, kandidat duplikat, form update status. |
| `resources/views/layouts/partials/internal-nav-links.blade.php` | Link navigasi ke halaman Anomaly Review di sidebar admin. |

---

## Sistem Skor & Level Risiko

Setiap detektor menghasilkan **skor numerik**. Skor dari semua detektor dijumlahkan:

```
Total Score = DuplicateDetector.score + AnomalyDetector.score
```

Kemudian `TransactionRiskAnalyzer` menentukan level:

| Total Skor | Level | Arti |
|---|---|---|
| `>= 20` | `warning` | Perlu ditinjau oleh admin. Muncul di halaman Anomaly Review. |
| `< 20` | `normal` | Tidak ada risiko berarti. Tidak muncul di dashboard anomaly. |

---

## Status Review (Lifecycle)

Setelah flag muncul, admin dapat mengubah status review melalui UI:

```
belum_ditinjau  ──▶  aman                (Flag ditutup, masuk arsip)
                ──▶  perlu_tindak_lanjut  (Tetap aktif, butuh investigasi)
```

| Status | Keterangan |
|---|---|
| `belum_ditinjau` | Default. Belum ada admin yang meninjau. |
| `aman` | Admin telah memverifikasi dan menyatakan transaksi ini aman. Masuk ke tab "Archived". |
| `perlu_tindak_lanjut` | Ada masalah nyata yang perlu ditangani lebih lanjut. Tetap di tab "Active". |

---

## Contoh Skenario

### Skenario 1: Petugas tidak sengaja double-click Simpan
1. Transaksi pertama tersimpan → analisis berjalan → `normal`.
2. Transaksi kedua tersimpan 5 detik kemudian → `DuplicateTransactionDetector` menemukan kecocokan exact → skor +60 → flag `exact_duplicate` → level `warning`.
3. Muncul di halaman Anomaly Review.
4. Admin meninjau → klik "Aman" jika memang hanya satu pembayaran, atau hapus transaksi duplikat.

### Skenario 2: Petugas salah ketik Rp 10.000.000 (seharusnya Rp 100.000)
1. Rata-rata nominal uang di database: Rp 50.000.
2. Threshold outlier: 5 × Rp 50.000 = Rp 250.000.
3. Nominal Rp 10.000.000 > Rp 250.000 → flag `statistical_outlier` → skor +15.
4. Karena skor < 20 secara mandiri, flag ini sering muncul **bersamaan** dengan flag lain. Namun jika digabung dengan deteksi lainnya, totalnya bisa melewati threshold `warning`.

### Skenario 3: Admin mengedit nominal setelah kwitansi dicetak
1. Kwitansi Rp 100.000 sudah dicetak.
2. Admin mengubah nominal menjadi Rp 200.000.
3. Flag `updated_after_receipt_printed` (+30) + `significant_nominal_change` (+35) → total skor 65 → `warning`.
4. Muncul di Anomaly Review dengan 2 flag sekaligus.
