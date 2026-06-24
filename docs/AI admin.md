# Plan AI Admin - Informasi dari Zakky

## 1. Tujuan

AI Admin tidak dibuat sebagai menu baru. Fitur ini menjadi informasi otomatis dari Zakky yang muncul di halaman admin yang sudah ada, terutama:

- Audit Log
- Deteksi Anomali
- Detail transaksi/anomali

Tujuan utamanya adalah membantu admin memahami kondisi sistem tanpa harus menekan tombol generate, tanpa membuat keputusan otomatis, dan tanpa menggantikan proses review manual.

## 2. Prinsip Produk

- Zakky tampil sebagai fitur pendukung, bukan fitur utama.
- Informasi muncul otomatis berdasarkan kondisi data.
- Tidak ada menu khusus AI Audit.
- Tidak ada tombol generate insight.
- Tidak ada keputusan final dari AI.
- Admin tetap menentukan status akhir seperti aman atau perlu tindak lanjut.
- Informasi harus singkat, jelas, dan relevan dengan konteks halaman.
- Sistem tetap bisa berjalan walaupun layanan AI tidak tersedia.

## 3. Posisi Zakky di Sistem

```text
----------------------+
| Halaman Admin       |
| Audit / Anomali     |
+----------+-----------+
           |
           v
+----------------------+
| Rule-Based Insight   |
| cepat & deterministik|
+----------+-----------+
           |
           v
+----------------------+
| Informasi dari Zakky |
| warning / info       |
+----------+-----------+
           |
           v
+----------------------+
| Optional AI Wording  |
| cached, background   |
+----------------------+
```

Rule-based insight menjadi sumber utama. AI hanya digunakan jika dibutuhkan untuk menyusun bahasa yang lebih natural.

## 4. Nama Tampilan Berdasarkan Kondisi

Gunakan nama yang berubah sesuai tingkat kondisi:

| Kondisi | Label |
| --- | --- |
| Normal / netral | Informasi dari Zakky |
| Perlu diperhatikan | Perhatian dari Zakky |
| Risiko tinggi | Peringatan dari Zakky |
| Detail anomali | Catatan dari Zakky |
| Rekomendasi tindak lanjut | Saran dari Zakky |
| Ringkasan harian | Ringkasan dari Zakky |

Untuk menjaga UI tetap compact, prioritas label cukup 3:

```text
Informasi dari Zakky
Perhatian dari Zakky
Peringatan dari Zakky
```

## 5. Penempatan UI

### 5.1 Audit Log

Zakky muncul sebagai panel ringkas di atas tabel audit log.

Contoh:

```text
Informasi dari Zakky

Aktivitas hari ini masih dalam pola normal. Terdapat 3 aktivitas sensitif yang perlu diperhatikan, terutama perubahan transaksi setelah kwitansi dicetak.
```

Jika ada risiko lebih tinggi:

```text
Perhatian dari Zakky

Terdapat perubahan data penting dalam 24 jam terakhir. Periksa aktivitas perubahan transaksi dan perubahan periode sebelum laporan digunakan.
```

Data yang dipakai:

- jumlah audit log hari ini
- aktivitas sensitif
- user paling aktif
- perubahan transaksi
- hapus/restore transaksi
- perubahan periode
- cetak kwitansi
- update status review

### 5.2 Deteksi Anomali

Zakky muncul di halaman daftar Deteksi Anomali sebagai ringkasan kondisi.

Contoh:

```text
Peringatan dari Zakky

Ada 4 transaksi yang belum ditinjau. Prioritaskan transaksi dengan perubahan setelah kwitansi dicetak karena dapat memengaruhi validitas bukti transaksi.
```

Data yang dipakai:

- total anomali
- jumlah belum ditinjau
- jumlah perlu tindak lanjut
- jenis flag terbanyak
- risk level tertinggi
- transaksi yang paling mendesak

### 5.3 Detail Anomali

Zakky muncul sebagai catatan pemeriksaan otomatis.

Contoh:

```text
Catatan dari Zakky

Transaksi ini perlu dicek ulang karena terdapat perubahan setelah kwitansi dicetak. Cocokkan nominal akhir dengan bukti pembayaran, petugas pencatat, dan histori perubahan sebelum menandai transaksi sebagai aman.
```

Data yang dipakai:

- nomor transaksi
- risk flags
- risk level
- review status
- waktu cetak kwitansi
- waktu update terakhir
- petugas terkait
- alasan deteksi

## 6. Jenis Informasi yang Ditampilkan

Zakky hanya boleh menampilkan:

- ringkasan kondisi
- peringatan
- catatan pemeriksaan
- saran verifikasi
- prioritas review
- informasi aktivitas sensitif

Zakky tidak boleh:

- mengubah status review
- mengubah transaksi
- menghapus data
- membuat keputusan final
- membuat angka yang tidak ada di context
- menampilkan data pribadi yang tidak relevan
- memanggil AI API setiap halaman dibuka

## 7. Arsitektur Teknis

### 7.1 Service Utama

Tambahkan service ringan:

```text
App\Services\Admin\ZakkyAdminInsightService
```

Tanggung jawab:

```text
auditLogInsight()
anomalyListInsight()
anomalyDetailInsight()
resolveTone()
buildMessage()
```

### 7.2 Optional AI Service

Jika tetap memakai AI untuk memperhalus kalimat:

```text
App\Services\Admin\ZakkyAdminAiWordingService
```

Tanggung jawab:

```text
buildSafeContext()
generateWording()
cacheResult()
fallbackToRuleBasedMessage()
```

AI service ini tidak wajib untuk MVP. MVP cukup rule-based.

## 8. Alur Data

### 8.1 Audit Log

```text
Admin membuka Audit Log
        |
        v
Controller mengambil audit logs
        |
        v
ZakkyAdminInsightService membaca ringkasan aktivitas
        |
        v
Service menentukan tone: info / attention / warning
        |
        v
Panel "Informasi dari Zakky" tampil otomatis
```

### 8.2 Deteksi Anomali

```text
Admin membuka Deteksi Anomali
        |
        v
Controller mengambil ringkasan transaction_risk_reviews
        |
        v
ZakkyAdminInsightService membaca jumlah dan flag
        |
        v
Panel Zakky menampilkan prioritas review
```

### 8.3 Detail Anomali

```text
Admin membuka detail anomali
        |
        v
Sistem membaca risk review dan histori transaksi
        |
        v
Zakky membuat catatan pemeriksaan
        |
        v
Admin tetap memilih status review secara manual
```

## 9. Rule-Based Insight

### 9.1 Audit Log

Rule contoh:

```text
Jika tidak ada aktivitas sensitif:
tone = info
message = Aktivitas sistem masih dalam pola normal.

Jika ada aktivitas sensitif:
tone = attention
message = Ada aktivitas penting yang perlu diperhatikan.

Jika ada perubahan periode, force delete, atau banyak perubahan transaksi:
tone = warning
message = Ada aktivitas berdampak tinggi yang perlu ditinjau.
```

### 9.2 Deteksi Anomali

Rule contoh:

```text
Jika pending anomaly = 0:
tone = info
message = Tidak ada anomali yang perlu ditinjau saat ini.

Jika pending anomaly > 0:
tone = attention
message = Ada transaksi yang belum ditinjau.

Jika terdapat updated_after_receipt_printed:
tone = warning
message = Prioritaskan transaksi yang berubah setelah kwitansi dicetak.
```

### 9.3 Detail Anomali

Rule contoh:

```text
Jika flag = updated_after_receipt_printed:
message = Cocokkan nominal akhir dengan bukti pembayaran dan histori perubahan.

Jika flag = significant_nominal_change:
message = Verifikasi alasan perubahan nominal sebelum ditandai aman.

Jika flag = exact_duplicate:
message = Pastikan transaksi bukan input ganda.
```

## 10. Data yang Boleh Dikirim ke AI

Jika optional AI wording dipakai, context harus dibatasi.

Boleh:

- nomor transaksi
- jenis aktivitas
- role user
- waktu aktivitas
- ringkasan perubahan
- risk flag
- risk level
- review status
- alasan deteksi

Tidak boleh:

- password
- token
- nomor telepon
- alamat lengkap
- data pribadi muzakki yang tidak relevan
- metadata mentah yang tidak difilter

## 11. Caching dan Performa

AI tidak boleh dipanggil setiap page load.

Strategi:

- rule-based message dibuat langsung saat request
- optional AI wording disimpan di cache
- cache key berdasarkan konteks
- cache expire 15-60 menit untuk audit log
- cache per transaksi untuk detail anomali
- jika AI gagal, tampilkan rule-based message

Contoh cache key:

```text
zakky:audit-log:{date}:{sensitive_count}:{latest_log_id}
zakky:anomaly-list:{pending_count}:{warning_count}:{latest_review_id}
zakky:anomaly-detail:{no_transaksi}:{risk_flags_hash}:{review_status}
```

## 12. Endpoint

Untuk MVP, tidak perlu endpoint baru jika informasi langsung dihitung di controller.

Jika nanti perlu async refresh:

```text
GET /internal/audit-logs/zakky-insight
GET /internal/anomalies/zakky-insight
GET /internal/anomalies/{noTransaksi}/zakky-insight
```

Tidak disarankan:

```text
POST /generate-ai-audit
POST /ai-audit/generate
```

Karena user tidak ingin tombol generate.

## 13. Database

MVP tidak perlu tabel baru.

Gunakan data existing:

- `audit_logs`
- `transaction_risk_reviews`
- `zakat_transactions`
- `ai_audit_summaries` hanya jika masih ingin menyimpan ringkasan historis

Jika nanti ingin menyimpan insight otomatis:

```text
zakky_admin_insights
```

Kolom opsional:

```text
id
context_type
context_key
tone
title
message
source
context_hash
expires_at
created_at
updated_at
```

Untuk fase awal, hindari tabel baru.

## 14. Integrasi dengan Halaman Existing

### AuditLogController

Tambahkan data:

```text
$zakkyInsight = $zakkyAdminInsightService->auditLogInsight($request);
```

View:

```text
internal.audit_logs.index
```

Tambahkan panel di atas stat cards atau di atas tabel.

### TransactionAnomalyController@index

Tambahkan data:

```text
$zakkyInsight = $zakkyAdminInsightService->anomalyListInsight($filters);
```

View:

```text
internal.anomalies.index
```

Tambahkan panel setelah header, sebelum filter.

### TransactionAnomalyController@show

Tambahkan data:

```text
$zakkyInsight = $zakkyAdminInsightService->anomalyDetailInsight($noTransaksi);
```

View:

```text
internal.anomalies.show
```

Tambahkan panel dekat ringkasan risiko.

## 15. Bentuk Data untuk View

Gunakan struktur sederhana:

```php
[
    'label' => 'Perhatian dari Zakky',
    'tone' => 'attention',
    'message' => 'Ada 4 transaksi yang belum ditinjau...',
    'items' => [
        'Prioritaskan transaksi yang berubah setelah kwitansi dicetak.',
        'Periksa catatan review sebelum menandai aman.',
    ],
]
```

Tone:

```text
info
attention
warning
success
```

## 16. UI Guideline

Panel harus compact:

- satu panel kecil
- maksimal 1 paragraf dan 2-3 bullet
- tidak memakai card besar berlebihan
- tidak terlihat seperti chatbot
- tidak ada input chat
- tidak ada tombol generate
- gunakan warna sesuai severity
- tetap mengikuti gaya UI internal yang sudah ada: rapi, ringan, rounded sedang, border halus, dan tidak terlalu dekoratif
- tampil sebagai system notice, bukan section besar

Contoh:

```text
[icon] Perhatian dari Zakky
Ada 4 transaksi yang belum ditinjau. Prioritaskan transaksi yang berubah setelah kwitansi dicetak.
```

### 16.1 Bentuk Komponen

Gunakan satu komponen reusable untuk semua halaman:

```blade
<x-zakky-insight
    :tone="$zakkyInsight['tone']"
    :label="$zakkyInsight['label']"
    :message="$zakkyInsight['message']"
    :items="$zakkyInsight['items'] ?? []"
    :generated="$zakkyInsight['generated'] ?? false"
/>
```

Struktur visual:

```text
+------------------------------------------------------+
| [icon] Perhatian dari Zakky                          |
| Ada 4 transaksi belum ditinjau. Prioritaskan ...     |
| - 2 transaksi berubah setelah kwitansi dicetak       |
| - 1 transaksi memiliki perubahan nominal besar       |
|                                                      |
| AI generated                                         |
+------------------------------------------------------+
```

Catatan:

- label `AI generated` ditampilkan kecil di bawah isi panel
- posisi label di kiri bawah agar tidak terlihat seperti tombol
- ukuran kecil, misalnya `text-[11px]`
- warna muted, misalnya `text-slate-400`
- hanya tampil jika pesan berasal dari AI wording
- jika pesan masih murni rule-based, gunakan label `Dihitung otomatis` atau tidak perlu label sama sekali

Rekomendasi default:

```text
Rule-based only  -> tidak perlu label bawah
AI wording       -> AI generated
```

### 16.2 Layout per Halaman

#### Audit Log

Letakkan panel setelah header halaman dan sebelum stat cards/tabel.

```text
Log Audit
[Informasi dari Zakky]
[Total Log] [Log Terbaru]
[Filter / Table]
```

Karakter UI:

- paling compact
- satu paragraf
- tidak perlu bullet kecuali ada aktivitas sensitif lebih dari satu jenis
- tone default `info`

Contoh:

```text
Informasi dari Zakky
Aktivitas hari ini masih dalam pola normal. Ada 2 aktivitas sensitif yang perlu diperhatikan, terutama perubahan transaksi setelah kwitansi dicetak.

AI generated
```

#### Deteksi Anomali

Letakkan panel setelah header dan sebelum filter daftar.

```text
Deteksi Anomali
[Peringatan dari Zakky]
[Filter]
[Daftar anomali]
```

Karakter UI:

- boleh memakai 1-2 bullet prioritas
- tone mengikuti risiko tertinggi
- jangan membuat panel lebih dominan daripada daftar anomali

Contoh:

```text
Peringatan dari Zakky
Ada 4 transaksi belum ditinjau. Prioritaskan transaksi yang berubah setelah kwitansi dicetak.

- 2 transaksi berubah setelah kwitansi dicetak
- 1 transaksi memiliki perubahan nominal besar

AI generated
```

#### Detail Anomali

Letakkan panel dekat ringkasan risiko/status review, bukan di paling bawah.

```text
Detail Anomali
[Ringkasan Risiko]
[Catatan dari Zakky]
[Detail transaksi]
[Form review]
```

Karakter UI:

- boleh sedikit lebih panjang
- fokus pada checklist pemeriksaan
- tidak memakai bahasa final seperti "transaksi ini salah"
- gunakan bahasa verifikasi seperti "cocokkan", "periksa", "pastikan"

Contoh:

```text
Catatan dari Zakky
Transaksi ini perlu dicek ulang karena terdapat perubahan setelah kwitansi dicetak. Cocokkan nominal akhir dengan bukti pembayaran dan histori perubahan sebelum menandai transaksi sebagai aman.

AI generated
```

### 16.3 Tone Visual

Gunakan tone yang konsisten dengan desain internal, tidak terlalu mencolok.

| Tone | Kondisi | Visual |
| --- | --- | --- |
| `info` | normal / netral | border emerald lembut, background emerald `bg-emerald-50/60` agar tidak bias dengan putih |
| `attention` | perlu diperhatikan | border amber lembut, background amber sangat ringan |
| `warning` | risiko tinggi | border rose/red lembut, background red sangat ringan |
| `success` | aman / tidak ada catatan | border emerald lembut, background emerald `bg-emerald-50/60` |

Keputusan warna:

```text
Informasi dari Zakky  -> bg-emerald-50/60, border-emerald-100
Perhatian dari Zakky  -> bg-amber-50/70, border-amber-200
Peringatan dari Zakky -> bg-rose-50/70, border-rose-200
```

Hijau hanya dipakai untuk state normal/success. State perhatian dan peringatan tetap memakai amber/rose agar makna visual tidak hilang.

Hindari:

- avatar AI besar
- bubble chat
- gradient mencolok
- animasi berlebihan
- card besar yang mengambil perhatian dari workflow utama
- tombol generate atau refresh

### 16.4 Copywriting UI

Pola kalimat:

```text
[kondisi utama]. [hal yang perlu diperhatikan]. [saran verifikasi singkat].
```

Contoh baik:

```text
Ada 4 transaksi belum ditinjau. Prioritaskan transaksi yang berubah setelah kwitansi dicetak karena dapat memengaruhi validitas bukti transaksi.
```

Contoh yang harus dihindari:

```text
Transaksi ini terbukti bermasalah dan harus diperbaiki.
```

Alasannya: Zakky hanya memberi informasi dan saran, bukan keputusan final.

## 17. MVP Scope

Fase pertama:

- Rename konsep `Review Anomali` menjadi `Deteksi Anomali`.
- Hapus AI Audit sebagai menu.
- Tambahkan panel `Informasi dari Zakky` di Audit Log.
- Tambahkan panel `Perhatian/Peringatan dari Zakky` di Deteksi Anomali.
- Tambahkan panel `Catatan dari Zakky` di detail anomali.
- Semua pesan dibuat rule-based.
- Tidak ada tabel baru.
- Tidak ada endpoint baru.
- Tidak ada panggilan AI otomatis.

Fase kedua:

- Tambahkan optional AI wording dengan cache.
- Gunakan background job jika perlu.
- Simpan context snapshot jika dibutuhkan untuk laporan akademik.

Fase ketiga:

- Tambahkan konfigurasi sensitivitas jika rule perlu disesuaikan admin.
- Tambahkan histori insight jika benar-benar dibutuhkan.

## 18. Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| AI dianggap mengambil keputusan | Gunakan label saran/informasi, bukan keputusan |
| Biaya API membengkak | Jangan panggil AI setiap page load |
| Informasi terlalu ramai | Batasi 1 paragraf dan maksimal 3 item |
| Data sensitif terkirim ke AI | Filter context sebelum dikirim |
| Sistem lambat | Rule-based sebagai default, AI cached/background |
| Admin terlalu bergantung pada AI | Tetap wajib review manual |

## 19. Narasi Akademik

Fitur ini dapat dijelaskan sebagai:

> Zakky berperan sebagai asisten informasi pada area admin. Sistem terlebih dahulu melakukan analisis berbasis aturan terhadap audit log dan hasil deteksi anomali. Berdasarkan hasil tersebut, Zakky menampilkan informasi, peringatan, dan saran pemeriksaan secara otomatis untuk membantu admin memahami aktivitas penting. Keputusan akhir tetap dilakukan oleh admin.

Nilai akademik:

- audit log sebagai sumber data aktivitas
- deteksi anomali berbasis aturan
- informasi otomatis berbasis konteks
- pembatasan peran AI agar tidak mengambil keputusan
- peningkatan auditabilitas dan kontrol internal

## 20. Rekomendasi Final

Implementasi paling aman:

```text
Audit Log
└── Informasi dari Zakky

Deteksi Anomali
└── Perhatian/Peringatan dari Zakky

Detail Anomali
└── Catatan dari Zakky
```

AI Admin tidak perlu menjadi menu. Zakky cukup hadir sebagai informasi otomatis yang membantu admin membaca kondisi sistem, memahami anomali, dan menentukan tindak lanjut secara manual.
