# Konsep Pengembangan AI Assistant dan AI Audit pada Sistem Informasi Pengelolaan Zakat Masjid An-Nur

## 1. Gambaran Umum Sistem

Sistem Informasi Pengelolaan Zakat Masjid An-Nur merupakan aplikasi berbasis web yang digunakan untuk membantu proses pencatatan, pengelolaan, rekapitulasi, audit, dan publikasi penerimaan zakat secara transparan.

Sistem ini memiliki dua sisi utama, yaitu:

1. **Sisi Internal Panitia**

   * Digunakan oleh panitia/admin untuk mencatat transaksi zakat.
   * Mengelola data muzakki.
   * Melihat dashboard penerimaan.
   * Mencetak bukti transaksi.
   * Mengelola laporan.
   * Melihat audit aktivitas pengguna.

2. **Sisi Publik Jamaah**

   * Digunakan untuk menampilkan ringkasan penerimaan zakat.
   * Menampilkan total penerimaan uang, beras, dan jumlah jiwa.
   * Menampilkan grafik penerimaan.
   * Menampilkan informasi publik secara real-time sebagai bentuk transparansi.

Untuk meningkatkan nilai sistem, ditambahkan fitur **AI Assistant** dan **AI Audit Assistant**. Fitur ini tidak hanya berfungsi sebagai chatbot umum, tetapi sebagai asisten analitik yang membantu pengguna memahami data penerimaan zakat dan membantu panitia meninjau aktivitas audit secara lebih mudah.

---

# 2. Fokus Pengembangan AI

Pengembangan AI pada sistem ini difokuskan pada dua bagian:

1. **AI Assistant Chat**

   * Berfungsi sebagai asisten informasi dan analitik laporan zakat.
   * Membantu pengguna memahami data penerimaan zakat melalui bahasa natural.
   * Memberikan ringkasan laporan, penjelasan grafik, dan jawaban berbasis data sistem.

2. **AI Audit Assistant**

   * Berfungsi membantu panitia membaca dan memahami aktivitas audit.
   * Memberikan ringkasan aktivitas penting.
   * Menandai aktivitas yang perlu ditinjau berdasarkan data audit log.
   * Membantu panitia menemukan pola perubahan data yang berpotensi sensitif.

---

# 3. Tujuan AI Assistant

Tujuan utama AI Assistant adalah mengubah data zakat yang berbentuk angka, tabel, dan grafik menjadi informasi yang lebih mudah dipahami oleh panitia maupun jamaah.

AI Assistant tidak dirancang sebagai chatbot bebas, tetapi sebagai **Natural Language Analytics Assistant**, yaitu asisten berbasis bahasa natural yang mampu menjawab pertanyaan berdasarkan data sistem.

Contoh pertanyaan yang dapat dijawab:

* Berapa total penerimaan zakat hari ini?
* Berapa total zakat fitrah pada periode aktif?
* Berapa total beras yang sudah diterima?
* Jenis zakat apa yang paling banyak diterima?
* Bagaimana ringkasan penerimaan zakat minggu ini?
* Kapan penerimaan zakat paling tinggi?
* Buatkan narasi laporan singkat untuk pengumuman jamaah.

Dengan demikian, AI Assistant tidak hanya menjawab pertanyaan umum, tetapi membantu proses interpretasi data zakat.

---

# 4. Tujuan AI Audit Assistant

AI Audit Assistant bertujuan membantu panitia memahami aktivitas sistem yang terekam pada audit log.

Audit log berisi catatan aktivitas pengguna, seperti:

* Membuat transaksi.
* Mengubah transaksi.
* Menghapus transaksi.
* Me-restore transaksi.
* Mencetak bukti transaksi.
* Mengubah data muzakki.
* Mengubah periode aktif.
* Mengubah pengaturan laporan.

AI Audit Assistant digunakan untuk:

* Membuat ringkasan aktivitas harian.
* Menampilkan aktivitas penting yang perlu ditinjau.
* Memberikan penjelasan perubahan transaksi.
* Membantu panitia memahami siapa melakukan apa, kapan, dan terhadap data apa.
* Menandai aktivitas sensitif, misalnya transaksi diedit setelah kwitansi dicetak.

---

# 5. Arsitektur Sistem

## 5.1 Arsitektur Umum

```text
+----------------------+
|      Jamaah          |
|  Halaman Publik      |
+----------+-----------+
           |
           v
+----------------------+
|    Laravel Web App   |
|  Public Dashboard    |
|  Internal Dashboard  |
+----------+-----------+
           |
           v
+----------------------+
|      Database        |
|  Transaksi Zakat     |
|  Muzakki             |
|  Audit Log           |
|  Rekap Penerimaan    |
+----------+-----------+
           |
           v
+----------------------+
| AI Context Builder   |
| Data Aggregation     |
| Prompt Builder       |
+----------+-----------+
           |
           v
+----------------------+
|   AI API Provider    |
| Generative AI Model  |
+----------+-----------+
           |
           v
+----------------------+
| AI Response Handler  |
| Validasi Output      |
| Format Jawaban       |
+----------+-----------+
           |
           v
+----------------------+
|  Jawaban ke User     |
|  Chat / Ringkasan    |
+----------------------+
```

---

## 5.2 Komponen Utama

### 1. Laravel Web Application

Laravel digunakan sebagai backend utama sistem. Komponen ini menangani:

* Autentikasi pengguna.
* Role-based access control.
* Manajemen transaksi zakat.
* Manajemen data muzakki.
* Dashboard internal.
* Dashboard publik.
* Audit log.
* Export laporan.
* Integrasi AI API.

---

### 2. Database

Database menyimpan data utama sistem, seperti:

* Data pengguna.
* Data muzakki.
* Data transaksi.
* Data kategori zakat.
* Data periode aktif.
* Data audit log.
* Data ringkasan publik.
* Data riwayat percakapan AI jika diperlukan.

---

### 3. AI Context Builder

AI Context Builder adalah komponen yang menyiapkan data sebelum dikirim ke AI.

AI tidak diberikan akses langsung ke seluruh database. Sistem hanya mengambil data yang relevan sesuai pertanyaan pengguna.

Contoh:

Jika pengguna bertanya:

> Berapa total zakat fitrah hari ini?

Maka sistem hanya mengambil:

* Periode aktif.
* Total zakat fitrah hari ini.
* Total uang.
* Total beras.
* Jumlah jiwa.

Data tersebut kemudian disusun menjadi context yang aman untuk dikirim ke AI.

---

### 4. Prompt Builder

Prompt Builder bertugas menyusun instruksi untuk AI agar jawaban tetap sesuai konteks sistem.

Contoh struktur prompt:

```text
Anda adalah AI Assistant pada Sistem Informasi Zakat Masjid An-Nur.
Jawab hanya berdasarkan data yang diberikan.
Jangan membuat angka sendiri.
Jika data tidak tersedia, katakan bahwa data tidak tersedia.
Gunakan bahasa Indonesia yang jelas dan mudah dipahami.
Jangan memberikan fatwa agama di luar data dan panduan yang tersedia.

Data sistem:
{context_data}

Pertanyaan pengguna:
{user_question}
```

---

### 5. AI API Provider

AI API Provider adalah layanan AI eksternal yang digunakan untuk menghasilkan jawaban berbasis Generative AI.

Contoh provider yang dapat digunakan:

* OpenAI API.
* Gemini API.
* Groq API.
* OpenRouter API.
* Provider LLM lain yang mendukung chat completion.

AI hanya berperan sebagai pengolah bahasa dan peringkas informasi, bukan sebagai sumber data utama.

---

### 6. AI Response Handler

AI Response Handler bertugas menerima jawaban dari AI, kemudian melakukan pengecekan sebelum jawaban ditampilkan.

Fungsi Response Handler:

* Memastikan jawaban tidak kosong.
* Memastikan jawaban tidak keluar dari konteks.
* Memastikan AI tidak membuat angka yang tidak ada di data.
* Menampilkan fallback response jika AI gagal.
* Menyimpan riwayat percakapan jika dibutuhkan.

---

# 6. Logic AI Assistant Chat

## 6.1 Alur Kerja AI Assistant

```text
User mengirim pertanyaan
        |
        v
Sistem menerima input
        |
        v
Sistem mengklasifikasi jenis pertanyaan
        |
        v
Sistem mengambil data terkait dari database
        |
        v
Context Builder menyusun data
        |
        v
Prompt Builder membuat instruksi AI
        |
        v
Request dikirim ke AI API
        |
        v
AI menghasilkan jawaban
        |
        v
Response Handler memvalidasi jawaban
        |
        v
Jawaban ditampilkan ke user
```

---

## 6.2 Klasifikasi Pertanyaan

Pertanyaan user dapat diklasifikasikan menjadi beberapa kategori:

### 1. Pertanyaan Data Penerimaan

Contoh:

* Berapa total zakat hari ini?
* Berapa total zakat fitrah?
* Berapa total infaq?
* Berapa total beras yang diterima?

Data yang diambil:

* Total penerimaan per kategori.
* Total uang.
* Total beras.
* Total jiwa.
* Periode aktif.

---

### 2. Pertanyaan Ringkasan Laporan

Contoh:

* Ringkas penerimaan zakat hari ini.
* Buatkan laporan singkat minggu ini.
* Buatkan narasi untuk pengumuman jamaah.

Data yang diambil:

* Rekap harian.
* Rekap mingguan.
* Rekap kategori.
* Total keseluruhan.
* Jumlah transaksi.

---

### 3. Pertanyaan Grafik dan Tren

Contoh:

* Hari apa penerimaan paling tinggi?
* Bagaimana tren penerimaan minggu ini?
* Kenapa grafik hari ini meningkat?

Data yang diambil:

* Data grafik harian.
* Total penerimaan per tanggal.
* Perbandingan antar hari.
* Kategori dengan kenaikan tertinggi.

---

### 4. Pertanyaan Panduan Zakat

Contoh:

* Apa itu zakat fitrah?
* Apa perbedaan zakat mal dan infaq?
* Bagaimana ketentuan fidyah?

Data yang diambil:

* FAQ zakat yang sudah disiapkan.
* Panduan zakat internal masjid.
* Ketentuan yang berlaku di periode aktif.

Catatan: AI tidak boleh memberikan fatwa bebas. Jawaban harus berdasarkan panduan yang dimasukkan ke sistem.

---

## 6.3 Contoh Logic Query

### Pertanyaan:

```text
Berapa total zakat fitrah hari ini?
```

### Proses:

```text
1. Sistem mendeteksi intent: data_penerimaan.
2. Sistem mendeteksi kategori: zakat_fitrah.
3. Sistem mendeteksi rentang waktu: hari_ini.
4. Sistem mengambil data dari tabel transaksi.
5. Sistem menghitung total uang, beras, dan jiwa.
6. Sistem mengirim context ke AI.
7. AI membuat jawaban natural.
```

### Context yang dikirim:

```json
{
  "periode": "Ramadhan 1448 H",
  "tanggal": "2026-03-15",
  "kategori": "Zakat Fitrah",
  "total_uang": 12500000,
  "total_beras": 180,
  "total_jiwa": 325,
  "jumlah_transaksi": 87
}
```

### Jawaban AI:

```text
Total zakat fitrah hari ini tercatat sebesar Rp12.500.000, dengan penerimaan beras sebanyak 180 kg dan total 325 jiwa dari 87 transaksi.
```

---

# 7. Logic AI Audit Assistant

## 7.1 Alur Kerja AI Audit Assistant

```text
Admin membuka halaman AI Audit
        |
        v
Admin memilih rentang waktu audit
        |
        v
Sistem mengambil data audit log
        |
        v
Sistem mengelompokkan aktivitas
        |
        v
Sistem menandai aktivitas sensitif
        |
        v
Context audit dikirim ke AI
        |
        v
AI membuat ringkasan audit
        |
        v
Sistem menampilkan hasil analisis audit
```

---

## 7.2 Data Audit yang Digunakan

Data audit log minimal berisi:

```text
- ID audit
- User ID
- Nama user
- Role user
- Action
- Entity type
- Entity ID
- Data sebelum perubahan
- Data setelah perubahan
- IP address
- User agent
- Waktu aktivitas
```

Contoh action:

```text
CREATE_TRANSACTION
UPDATE_TRANSACTION
DELETE_TRANSACTION
RESTORE_TRANSACTION
PRINT_RECEIPT
UPDATE_MUZAKKI
CHANGE_ACTIVE_PERIOD
EXPORT_REPORT
LOGIN
LOGOUT
```

---

## 7.3 Aktivitas yang Perlu Ditinjau

Sistem dapat menandai aktivitas sebagai perlu ditinjau jika memenuhi kondisi berikut:

### 1. Transaksi Diedit Setelah Kwitansi Dicetak

```text
Jika transaksi sudah memiliki status receipt_printed = true,
lalu terjadi UPDATE_TRANSACTION,
maka aktivitas diberi label: perlu_ditinjau.
```

Alasan:

Perubahan setelah bukti transaksi dicetak dapat memengaruhi validitas laporan.

---

### 2. Transaksi Dihapus Setelah Masuk Rekap

```text
Jika transaksi sudah masuk laporan publik,
lalu terjadi DELETE_TRANSACTION,
maka aktivitas diberi label: sensitif.
```

Alasan:

Penghapusan transaksi dapat mengubah total penerimaan yang telah ditampilkan ke publik.

---

### 3. Perubahan Nominal Signifikan

```text
Jika nilai nominal sebelum dan sesudah berubah lebih dari batas tertentu,
misalnya lebih dari 50%,
maka aktivitas diberi label: perubahan_signifikan.
```

Alasan:

Perubahan nominal besar perlu dikonfirmasi oleh admin.

---

### 4. Aktivitas Berulang pada Transaksi yang Sama

```text
Jika satu transaksi diubah lebih dari 2 kali dalam 1 hari,
maka aktivitas diberi label: aktivitas_berulang.
```

Alasan:

Perubahan berulang dapat menunjukkan kesalahan input atau kebutuhan verifikasi.

---

### 5. Perubahan Periode Aktif

```text
Jika user mengubah periode aktif,
maka aktivitas diberi label: konfigurasi_penting.
```

Alasan:

Periode aktif memengaruhi laporan, dashboard, dan ringkasan publik.

---

## 7.4 Output AI Audit Assistant

AI Audit Assistant dapat menghasilkan:

### 1. Ringkasan Audit Harian

Contoh:

```text
Pada tanggal 15 Maret 2026 terdapat 124 aktivitas sistem. Aktivitas terbanyak adalah pembuatan transaksi sebanyak 98 kali. Terdapat 4 aktivitas yang perlu ditinjau, yaitu 2 perubahan transaksi setelah kwitansi dicetak dan 2 penghapusan transaksi yang sudah masuk rekap publik.
```

---

### 2. Daftar Aktivitas Sensitif

Contoh:

```text
Aktivitas yang perlu ditinjau:
1. Transaksi TRX-1024 diedit oleh Admin A setelah kwitansi dicetak.
2. Transaksi TRX-1041 dihapus oleh Staff B setelah masuk rekap publik.
3. Periode aktif diubah oleh Super Admin pada pukul 20.13.
```

---

### 3. Rekomendasi Tindak Lanjut

Contoh:

```text
Disarankan admin melakukan verifikasi terhadap transaksi TRX-1024 dan TRX-1041 dengan mencocokkan kembali bukti pembayaran atau catatan manual panitia.
```

---

# 8. Perbedaan AI Assistant dan AI Audit Assistant

| Aspek    | AI Assistant Chat                  | AI Audit Assistant                  |
| -------- | ---------------------------------- | ----------------------------------- |
| Pengguna | Jamaah dan panitia                 | Admin dan super admin               |
| Fokus    | Informasi dan laporan zakat        | Aktivitas sistem dan audit          |
| Data     | Rekap publik, grafik, FAQ          | Audit log dan transaksi             |
| Output   | Jawaban natural, ringkasan laporan | Ringkasan audit, aktivitas sensitif |
| Tujuan   | Memudahkan pemahaman data          | Membantu kontrol internal           |
| Risiko   | Jawaban keluar konteks             | Salah interpretasi audit            |
| Mitigasi | Context dibatasi                   | Rule flagging sebelum AI            |

---

# 9. Batasan AI

Agar sistem tetap aman dan dapat dipertanggungjawabkan, AI memiliki beberapa batasan:

1. AI tidak boleh mengakses seluruh database secara langsung.
2. AI hanya menerima data yang sudah disiapkan oleh sistem.
3. AI tidak boleh membuat angka sendiri.
4. AI tidak boleh memberi fatwa zakat di luar panduan yang tersedia.
5. AI tidak boleh mengubah, menghapus, atau membuat transaksi.
6. AI hanya memberikan ringkasan, penjelasan, dan rekomendasi.
7. Keputusan akhir tetap berada pada admin atau panitia.

---

# 10. Keamanan dan Privasi

Data yang dikirim ke AI harus dibatasi.

Data yang boleh dikirim:

* Total penerimaan.
* Rekap kategori.
* Grafik harian.
* Jumlah transaksi.
* Ringkasan audit.
* Nama role pengguna.
* ID transaksi yang disamarkan jika perlu.

Data yang sebaiknya tidak dikirim:

* Nomor telepon muzakki.
* Alamat lengkap muzakki.
* Data pribadi sensitif.
* Catatan internal yang tidak relevan.
* Password atau credential.
* Token API.

Contoh masking:

```text
TRX-1024 tetap ditampilkan.
Nama muzakki lengkap dapat disamarkan menjadi "Muzakki A".
Nomor telepon tidak dikirim ke AI.
```

---

# 11. Skema Database Tambahan

## 11.1 Tabel `ai_chat_logs`

Digunakan untuk menyimpan riwayat penggunaan AI Assistant.

```text
id
user_id
session_id
question
intent
context_summary
answer
source_type
created_at
updated_at
```

---

## 11.2 Tabel `ai_audit_summaries`

Digunakan untuk menyimpan hasil ringkasan AI Audit.

```text
id
generated_by
date_from
date_to
total_activities
sensitive_activities_count
summary
recommendation
created_at
updated_at
```

---

## 11.3 Tabel `audit_flags`

Digunakan untuk menyimpan hasil penandaan aktivitas sensitif sebelum dikirim ke AI.

```text
id
audit_log_id
flag_type
severity
reason
status
reviewed_by
reviewed_at
created_at
updated_at
```

Contoh `flag_type`:

```text
UPDATED_AFTER_RECEIPT_PRINTED
DELETED_AFTER_PUBLIC_RECAP
SIGNIFICANT_AMOUNT_CHANGE
REPEATED_UPDATE
ACTIVE_PERIOD_CHANGED
```

Contoh `severity`:

```text
LOW
MEDIUM
HIGH
```

Contoh `status`:

```text
PENDING
REVIEWED
IGNORED
RESOLVED
```

---

# 12. Service Layer yang Dibutuhkan

## 12.1 `AiAssistantService`

Bertugas menangani chat berbasis data zakat.

Fungsi utama:

```text
detectIntent()
buildContext()
buildPrompt()
sendToAiProvider()
handleResponse()
saveChatLog()
```

---

## 12.2 `AiAuditService`

Bertugas menangani ringkasan audit berbasis AI.

Fungsi utama:

```text
getAuditLogs()
generateAuditFlags()
buildAuditContext()
buildAuditPrompt()
sendToAiProvider()
saveAuditSummary()
```

---

## 12.3 `ZakatAnalyticsService`

Bertugas menyiapkan data statistik zakat.

Fungsi utama:

```text
getTotalByPeriod()
getTotalByCategory()
getDailyTrend()
getPeakDay()
getPublicSummary()
getCategoryDistribution()
```

---

## 12.4 `AuditFlagService`

Bertugas memberi label aktivitas audit yang perlu ditinjau.

Fungsi utama:

```text
flagUpdatedAfterReceiptPrinted()
flagDeletedAfterPublicRecap()
flagSignificantAmountChange()
flagRepeatedUpdate()
flagActivePeriodChanged()
```

---

# 13. Contoh Endpoint API

## 13.1 AI Assistant Chat

```text
POST /api/ai/chat
```

Request:

```json
{
  "message": "Berapa total zakat fitrah hari ini?"
}
```

Response:

```json
{
  "intent": "zakat_summary",
  "answer": "Total zakat fitrah hari ini tercatat sebesar Rp12.500.000 dengan total 325 jiwa.",
  "sources": ["public_summary", "transactions"],
  "created_at": "2026-03-15 20:10:00"
}
```

---

## 13.2 Generate Audit Summary

```text
POST /api/ai/audit-summary
```

Request:

```json
{
  "date_from": "2026-03-15",
  "date_to": "2026-03-15"
}
```

Response:

```json
{
  "summary": "Pada tanggal 15 Maret 2026 terdapat 124 aktivitas sistem...",
  "sensitive_activities_count": 4,
  "recommendation": "Admin disarankan meninjau transaksi TRX-1024 dan TRX-1041."
}
```

---

# 14. Pengujian Sistem

## 14.1 Pengujian AI Assistant

Pengujian dilakukan dengan membandingkan jawaban AI dengan data aktual di database.

Aspek yang diuji:

1. Kesesuaian angka.
2. Kesesuaian kategori zakat.
3. Kesesuaian periode.
4. Kejelasan jawaban.
5. Kemampuan membuat ringkasan.
6. Kemampuan menjawab jika data tidak tersedia.

Contoh skenario:

| No | Pertanyaan                   | Data Aktual  | Jawaban AI   | Status |
| -- | ---------------------------- | ------------ | ------------ | ------ |
| 1  | Total zakat fitrah hari ini? | Rp12.500.000 | Rp12.500.000 | Valid  |
| 2  | Total beras diterima?        | 180 kg       | 180 kg       | Valid  |
| 3  | Ringkas laporan minggu ini   | Sesuai rekap | Sesuai       | Valid  |

---

## 14.2 Pengujian AI Audit Assistant

Pengujian dilakukan dengan membandingkan hasil AI dengan audit log aktual.

Aspek yang diuji:

1. Jumlah aktivitas.
2. Jumlah aktivitas sensitif.
3. Jenis aktivitas sensitif.
4. Rekomendasi tindak lanjut.
5. Kesesuaian ringkasan dengan data audit.

Contoh skenario:

| No | Kondisi Audit                             | Ekspektasi          | Output AI           | Status |
| -- | ----------------------------------------- | ------------------- | ------------------- | ------ |
| 1  | Transaksi diedit setelah kwitansi dicetak | Perlu ditinjau      | Perlu ditinjau      | Valid  |
| 2  | Transaksi dihapus setelah masuk rekap     | Sensitif            | Sensitif            | Valid  |
| 3  | Periode aktif diubah                      | Konfigurasi penting | Konfigurasi penting | Valid  |

---

# 15. Kontribusi Penelitian

Kontribusi dari penelitian ini adalah:

1. Mengembangkan sistem informasi zakat berbasis web yang mendukung proses pencatatan dan transparansi laporan penerimaan zakat.
2. Mengintegrasikan Generative AI sebagai asisten analitik untuk membantu pengguna memahami data zakat melalui bahasa natural.
3. Mengembangkan AI Audit Assistant untuk membantu panitia membaca aktivitas audit dan meninjau aktivitas sensitif.
4. Menyediakan mekanisme penyajian laporan zakat yang lebih mudah dipahami oleh panitia dan jamaah.
5. Meningkatkan auditabilitas sistem melalui audit log, flag aktivitas, dan ringkasan berbasis AI.

---

# 16. Batasan Penelitian

Batasan penelitian ini adalah:

1. Sistem hanya digunakan untuk pengelolaan zakat di Masjid An-Nur.
2. AI Assistant hanya menjawab berdasarkan data yang tersedia pada sistem.
3. AI tidak digunakan untuk mengambil keputusan final.
4. AI tidak digunakan untuk melakukan transaksi.
5. AI tidak menggantikan peran panitia atau pengurus masjid.
6. AI tidak memberikan fatwa agama di luar panduan zakat yang disediakan.
7. Evaluasi AI dilakukan berdasarkan kesesuaian jawaban terhadap data sistem dan hasil pengujian pengguna.

---

# 17. Kesimpulan Arah Pengembangan

Pengembangan AI pada sistem ini tidak diposisikan sebagai chatbot umum, melainkan sebagai asisten analitik yang membantu mengubah data zakat menjadi informasi yang lebih mudah dipahami.

AI Assistant digunakan untuk menjawab pertanyaan, membuat ringkasan, dan menjelaskan laporan penerimaan zakat. Sementara itu, AI Audit Assistant digunakan untuk membantu panitia memahami aktivitas sistem, menandai aktivitas sensitif, dan memberikan rekomendasi peninjauan.

Dengan pendekatan ini, sistem tidak hanya menjadi aplikasi pencatatan zakat, tetapi berkembang menjadi sistem informasi zakat yang mendukung transparansi, auditabilitas, dan interpretasi data berbasis Generative AI.
