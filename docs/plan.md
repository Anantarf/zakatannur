# Implementation Plan

## Project

Implementasi Deteksi Duplikasi dan Anomali Transaksi pada Web Zakat An-Nur dengan Dukungan Transaction Review Assistant

## Ringkasan

Dokumen ini menjadi rencana kerja untuk menambahkan dua kemampuan baru pada Web Zakat An-Nur:

1. Deteksi duplikasi transaksi
2. Deteksi anomali transaksi
3. Transaction Review Assistant sebagai fitur pendamping pemeriksaan transaksi

Fitur inti tetap berada pada deteksi duplikasi dan anomali. Transaction Review Assistant berperan sebagai lapisan pendamping yang membantu operator meninjau transaksi yang perlu dicek ulang tanpa mengganggu validasi input yang sudah kuat.

## Latar Belakang Masalah

Sistem transaksi zakat rawan terhadap:

- input ganda dalam konteks transaksi yang sama atau sangat mirip pada tahun zakat yang sama
- nilai transaksi yang menyimpang dari standar tahunan lembaga atau perlu verifikasi manual
- kombinasi field yang tidak konsisten
- pengisian form yang benar secara teknis tetapi janggal secara operasional

Masalah ini penting karena data transaksi dipakai untuk:

- rekap internal
- laporan publik
- bukti pembayaran
- audit operasional

## Tujuan

### Tujuan utama

Membangun mekanisme otomatis untuk mendeteksi transaksi duplikat dan anomali pada Web Zakat An-Nur.

### Tujuan pendukung

- membantu petugas memeriksa transaksi yang perlu ditinjau ulang
- menurunkan risiko human error yang lolos dari validasi dasar
- menyediakan penanda risiko yang mudah dipahami operator
- menyiapkan landasan penelitian skripsi yang terukur

## Scope

### In scope

- analisis transaksi saat create dan update
- pemeriksaan duplikasi berbasis rule
- pemeriksaan anomali berbasis rule dan scoring
- Transaction Review Assistant di halaman riwayat dan detail transaksi internal
- penyimpanan hasil analisis per transaksi atau per grup transaksi
- tampilan badge, warning, dan informasi kandidat duplikasi untuk operator
- pengujian backend dan feature test untuk skenario utama

### Out of scope fase awal

- machine learning penuh
- anomaly detection berbasis model statistik kompleks
- auto-delete transaksi
- auto-void transaksi
- keputusan final sepenuhnya diambil AI tanpa campur tangan operator

## Definisi Fitur

### 1. Duplicate Detection

Fitur untuk menandai transaksi yang sangat mirip dengan transaksi lain dalam rentang waktu tertentu.

Contoh indikasi:

- nama pembayar sama, nominal sama, kategori sama, tahun zakat sama, dan waktu berdekatan
- muzakki sama, metode sama, total sama, tahun zakat sama, dan shift/waktu berdekatan
- transaksi transfer dengan ciri identik tersimpan lebih dari sekali

Catatan penting:

- nama pembayar yang sama tidak otomatis berarti duplikat
- transaksi pada tahun zakat yang berbeda tidak dianggap kandidat duplikasi default
- satu pembayar dapat secara sah membayarkan zakat untuk orang yang berbeda

### 2. Anomaly Detection

Fitur untuk menandai transaksi yang tidak lazim menurut aturan bisnis atau pola operasional.

Contoh indikasi:

- nilai transaksi menyimpang dari konfigurasi tahunan lembaga atau pola operasional yang perlu diverifikasi
- fitrah tunai tidak sesuai kelipatan default per jiwa
- fidyah beras tidak sesuai kelipatan per hari
- jiwa atau hari terisi tetapi kategori/metodenya tidak cocok
- pembayar baru memasukkan kombinasi field yang jarang terjadi

### 3. Transaction Review Assistant

Fitur bantuan saat operator meninjau transaksi di area internal, terutama pada halaman riwayat dan detail transaksi.

Contoh perilaku:

- memberi tanda bahwa transaksi perlu dicek ulang
- menampilkan kandidat transaksi yang mirip
- memberi alasan mengapa transaksi dianggap berisiko
- membantu operator memahami kenapa transaksi ditandai berisiko

## Existing Intelligence vs Proposed Intelligence

### Intelligence yang sudah ada di sistem

Web Zakat An-Nur saat ini sudah memiliki rule-based validation yang kuat, antara lain:

- validasi kategori dan metode transaksi
- perhitungan otomatis nilai default zakat fitrah dan fidyah
- pembatasan input agar tidak berada di bawah takaran yang ditetapkan
- validasi kombinasi field sesuai aturan bisnis

Lapisan ini tetap dipertahankan sebagai validasi utama saat input.

### Intelligence yang akan ditambahkan

Pengembangan baru difokuskan pada rule-based risk intelligence, yaitu:

- mendeteksi potensi duplikasi transaksi
- menandai transaksi yang perlu verifikasi manual
- membantu operator saat meninjau transaksi yang sudah tersimpan atau akan disimpan

Dengan demikian, pengembangan ini bukan mengganti validasi yang sudah solid, tetapi memperluas kecerdasan sistem dari validasi aturan menjadi deteksi risiko transaksi.

## Prinsip Implementasi

- fase pertama rule-based terlebih dahulu
- hasil analisis risiko bersifat informatif dan non-blocking kecuali pelanggaran validasi inti
- keputusan simpan tetap berada di tangan operator
- scoring dan alasan deteksi harus dapat dijelaskan
- arsitektur mengikuti service/domain yang sudah ada di project

## Arsitektur yang Diusulkan

### Backend

Folder target:

- `app/Services/Transactions/`
- `app/Data/` atau `app/Support/`
- `app/Http/Controllers/Internal/`
- `app/Models/`

Komponen yang disarankan:

1. `TransactionRiskAnalyzer`
   - orkestrator analisis risiko
   - memanggil duplicate detector dan anomaly detector
   - menghasilkan payload hasil analisis

2. `DuplicateTransactionDetector`
   - berisi rule deteksi transaksi mirip
   - fokus pada perbandingan dengan transaksi existing

3. `TransactionAnomalyDetector`
   - berisi rule anomali bisnis
   - fokus pada konsistensi nilai dan kombinasi field

4. `TransactionReviewAssistantService`
   - menyusun informasi review untuk history dan detail transaksi
   - menghasilkan alasan risiko, kandidat duplikasi, dan ringkasan pemeriksaan

5. `TransactionRiskResult`
   - DTO atau value object untuk hasil analisis
   - memuat status, score, flags, dan reasons

### Frontend

Komponen target:

- halaman riwayat transaksi internal
- halaman detail transaksi
- partial Blade untuk badge dan panel review risiko

Perilaku UI:

- menampilkan badge risiko pada daftar transaksi
- menampilkan panel review pada detail transaksi
- menampilkan kandidat duplikasi yang relevan
- menampilkan label risiko ringan, sedang, atau tinggi
- menampilkan alasan deteksi dengan bahasa operasional
- tidak mengganggu alur kerja operator secara berlebihan

## Audit UX dan Penempatan Fitur

### Temuan umum

Secara fungsi, sistem transaksi saat ini sudah kaya dan aturan bisnisnya kuat. Namun dari sisi layout dan penempatan fitur, masih ada kesan bahwa beberapa halaman tumbuh bertahap sehingga hirarki visual dan pembagian tugas antarhalaman belum sepenuhnya tegas.

Temuan utamanya:

- halaman input transaksi memikul terlalu banyak perhatian sekaligus
- halaman riwayat transaksi belum menjadi pusat pemindaian risiko
- halaman detail transaksi belum dimanfaatkan sebagai ruang verifikasi mendalam
- beberapa fitur penting sudah ada, tetapi rumah interaksinya belum paling cocok

### Prinsip penataan ulang

Supaya sistem terasa lebih rapi dan lebih tenang dipakai, penempatan fitur perlu mengikuti mode kerja operator:

1. halaman input untuk membuat dan memperbaiki transaksi
2. halaman riwayat untuk memindai dan menemukan transaksi yang perlu perhatian
3. halaman detail untuk memverifikasi, memahami konteks, dan mengambil keputusan

Dengan prinsip ini, fitur review risiko tidak perlu membanjiri form input, tetapi hadir kuat di area pemeriksaan transaksi.

## Rekomendasi Layout per Halaman

### 1. Halaman input transaksi

Peran utama:

- memasukkan data transaksi
- menghitung nilai sesuai aturan yang sudah ada
- memastikan validasi dasar berjalan lancar

Masalah yang terasa sekarang:

- area input utama, informasi pembayar, dan informasi bantuan sama-sama meminta perhatian
- halaman terasa padat karena banyak state hidup dalam satu layar
- jika fitur risiko diletakkan terlalu aktif di sini, form bisa kehilangan fokus

Rekomendasi layout:

- pertahankan fokus utama pada input pembayar dan rincian anggota
- tempatkan ringkasan transaksi dan aksi simpan di area yang tetap mudah dipindai
- jika tetap ada informasi risiko di halaman ini, bentuknya cukup sebagai indikator ringan setelah submit atau saat edit, bukan panel besar yang mengganggu
- jangan jadikan form sebagai pusat review duplikasi

Peran fitur baru di halaman ini:

- minimal
- hanya menampilkan status risiko ringkas jika transaksi sedang diedit atau baru disimpan

### 2. Halaman riwayat transaksi

Peran utama:

- memindai transaksi
- menemukan transaksi yang perlu ditinjau
- menyaring data berdasarkan risiko, kategori, tahun, dan kata kunci

Masalah yang terasa sekarang:

- halaman sudah baik sebagai daftar transaksi, tetapi belum menonjolkan prioritas visual untuk review
- semua baris terasa setara, padahal nanti akan ada transaksi yang perlu cepat dikenali

Rekomendasi layout:

- tambahkan kolom atau badge risiko yang langsung terlihat di setiap baris
- tambahkan filter `risk_level` agar operator bisa fokus pada transaksi `warning` atau `suspicious`
- jika memungkinkan, tambahkan ringkasan kecil di atas tabel seperti:
  - jumlah transaksi perlu cek
  - jumlah kandidat duplikasi
  - jumlah transaksi normal
- pertahankan aksi utama tetap ringkas: lihat, edit, cetak, hapus

Peran fitur baru di halaman ini:

- utama
- halaman ini menjadi tempat pertama operator sadar bahwa ada transaksi yang perlu diperiksa

### 3. Halaman detail transaksi

Peran utama:

- memahami isi transaksi secara lengkap
- memverifikasi apakah transaksi aman atau perlu tindak lanjut

Masalah yang terasa sekarang:

- halaman ini sudah cukup rapi untuk ringkasan transaksi
- tetapi belum memiliki ruang khusus untuk menjelaskan kenapa sebuah transaksi dianggap berisiko

Rekomendasi layout:

- tambahkan panel `Review Risiko` di bagian atas ringkasan atau tepat setelah metadata utama
- panel ini memuat:
  - level risiko
  - alasan deteksi
  - kandidat transaksi mirip
  - penjelasan singkat apakah ini sekadar warning atau kandidat duplikasi kuat
- hindari mencampur panel risiko dengan tabel rincian pembayaran agar fokus baca tetap jelas

Peran fitur baru di halaman ini:

- paling penting untuk verifikasi
- menjadi rumah utama bagi Transaction Review Assistant

## Strategi Penempatan Fitur Baru

### Duplicate detection

Tempat terbaik:

- badge di riwayat transaksi
- panel kandidat duplikasi di detail transaksi

### Anomaly detection

Tempat terbaik:

- badge risiko di riwayat
- alasan deteksi di detail

### Transaction Review Assistant

Tempat terbaik:

- ringkasan visual di riwayat
- penjelasan dan pembanding di detail transaksi

### Form input

Tempat terbaik hanya untuk:

- validasi aturan bisnis yang memang sudah ada
- umpan balik dasar jika payload tidak valid

## Prioritas UX untuk Implementasi

Urutan implementasi UX yang disarankan:

1. tambahkan `risk_level` badge di halaman riwayat
2. tambahkan filter transaksi berdasarkan level risiko
3. tambahkan panel `Review Risiko` di halaman detail transaksi
4. tambahkan daftar kandidat transaksi mirip pada detail
5. jika masih diperlukan, tambahkan indikator risiko ringan saat edit transaksi

## Acceptance Criteria UX

Fitur review dianggap tertata dengan baik jika:

- operator dapat melihat transaksi berisiko langsung dari halaman riwayat
- operator tidak perlu kembali ke form input hanya untuk memahami warning risiko
- halaman detail menjadi tempat yang jelas untuk verifikasi manual
- informasi risiko mudah dipindai dan tidak menenggelamkan aksi utama transaksi

## Integrasi dengan Struktur Project Saat Ini

Project sudah memiliki domain transaksi yang mulai rapi di:

- `TransactionHistoryService`
- `TransactionGroupLifecycleService`
- `GroupedTransactionQueryService`
- `TransactionRowPersister`
- `TransactionNominalValidator`

Rencana integrasi:

1. `ZakatService` tetap menjadi orchestrator create/update transaksi
2. setelah payload transaksi dibangun, sistem memanggil `TransactionRiskAnalyzer`
3. hasil analisis:
   - disimpan sebagai metadata risiko transaksi
   - ditampilkan di area review internal
   - disimpan ke database untuk audit ringan
   - bisa dipakai di history, detail, dan filter transaksi

## Data yang Dibutuhkan

Field yang sudah tersedia dan bisa dipakai:

- `no_transaksi`
- `muzakki_id`
- `pembayar_nama`
- `pembayar_phone`
- `category`
- `metode`
- `nominal_uang`
- `jumlah_beras_kg`
- `jiwa`
- `hari`
- `tahun_zakat`
- `shift`
- `waktu_terima`
- `is_transfer`
- `petugas_id`
- `created_at`

## Usulan Perubahan Database

Ada dua opsi implementasi.

### Opsi A - ringan dan cepat

Tambah field langsung ke `zakat_transactions`:

- `risk_level` nullable string
- `risk_score` nullable unsigned integer
- `risk_flags` nullable json
- `risk_checked_at` nullable timestamp

Kelebihan:

- cepat diimplementasikan
- query history lebih mudah
- cocok untuk fase awal skripsi

Kekurangan:

- hasil analisis hanya menyimpan status terakhir

### Opsi B - lebih rapi

Buat tabel baru misalnya `transaction_risk_reviews`:

- `id`
- `zakat_transaction_id`
- `group_no_transaksi`
- `risk_level`
- `risk_score`
- `risk_flags` json
- `detector_version`
- `checked_at`
- `created_at`
- `updated_at`

Kelebihan:

- histori analisis lebih jelas
- lebih fleksibel jika rules berubah
- lebih cocok jika ingin audit lebih formal

Kekurangan:

- implementasi sedikit lebih panjang

### Rekomendasi

Untuk fase awal skripsi, gunakan Opsi A lebih dulu. Jika kebutuhan audit berkembang, hasilnya bisa dipindahkan ke tabel terpisah pada fase dua.

## Rule Kandidat

### Rule duplikasi

1. Exact duplicate by signature
   - `tahun_zakat` sama
   - muzakki sama atau grup orang yang dizakatkan sama
   - pembayar sama
   - kategori sama
   - metode sama
   - nominal/beras sama
   - selisih waktu sangat dekat

2. Near duplicate by payment identity
   - `tahun_zakat` sama
   - pembayar_nama sama
   - phone sama atau kosong semua
   - total sama
   - kategori dan metode sama
   - shift sama atau hari yang sama
   - hanya menjadi warning, bukan duplikat pasti, jika target muzakki berbeda

3. Same payer, different beneficiary
   - pembayar_nama sama
   - target muzakki atau orang yang dizakatkan berbeda
   - selama kategori, konteks, atau isi tanggungan berbeda secara masuk akal, kondisi ini dianggap normal
   - dapat menjadi warning ringan hanya jika pola transaksi sangat identik dan terjadi berulang dalam waktu sangat singkat

4. Transfer duplicate candidate
   - `tahun_zakat` sama
   - `is_transfer = true`
   - nominal sama
   - pembayar sama
   - waktu terima berdekatan

### Rule anomali

1. Fitrah cash mismatch
   - `nominal_uang` tidak sesuai `jiwa x default_fitrah_cash_per_jiwa_used`

2. Fitrah beras mismatch
   - `jumlah_beras_kg` tidak sesuai `jiwa x beras_per_jiwa`

3. Fidyah mismatch
   - nominal atau beras tidak sesuai `hari x default`

4. Invalid field combination
   - `jiwa` terisi untuk kategori yang tidak memerlukannya
   - `hari` terisi untuk kategori yang tidak memerlukannya
   - `nominal_uang` dan `jumlah_beras_kg` bertentangan dengan metode

5. Outlier nominal
   - nominal jauh di atas atau di bawah rentang normal kategori tertentu

6. Suspicious repetition
   - transaksi dengan pola sama muncul berulang dalam rentang waktu sangat singkat

### Batas interpretasi

- beda `tahun_zakat` bukan anomali
- nama pembayar yang sama bukan anomali dengan sendirinya
- fokus deteksi adalah kemiripan transaksi dalam konteks operasional yang sama, bukan sekadar kemiripan nama

## Skema Risk Level

Contoh level:

- `normal`
- `warning`
- `suspicious`

Contoh scoring:

- 0-19: normal
- 20-49: warning
- 50+: suspicious

Catatan:

- score hanya alat bantu internal
- user melihat label dan alasan, bukan angka mentah saja

## Transaction Review Assistant Flow

### Saat transaksi dibuat atau diperbarui

1. sistem memvalidasi aturan bisnis yang sudah ada
2. sistem membangun payload final transaksi
3. sistem menjalankan analisis duplikasi dan anomali
4. sistem menyimpan hasil pemeriksaan risiko

### Saat operator memeriksa transaksi

1. operator membuka riwayat atau detail transaksi
2. sistem menampilkan badge level risiko
3. sistem menampilkan alasan deteksi
4. sistem menampilkan kandidat transaksi mirip jika ada
5. operator memutuskan apakah transaksi aman, perlu dicek ulang, atau perlu tindak lanjut

## Hard Validation vs Soft Warning

### Hard validation

Tetap ditolak:

- field wajib kosong atau salah format
- kategori/metode tidak valid
- relasi data wajib tidak ada

### Soft warning

Tetap boleh simpan:

- nilai transaksi perlu verifikasi manual
- pola mirip transaksi sebelumnya
- nilai tidak mengikuti default tetapi masih masuk akal

## Endpoint / Flow Teknis

### Opsi minimum

Analisis hanya dijalankan saat submit transaksi di backend.

Kelebihan:

- implementasi lebih cepat
- tidak perlu endpoint baru di awal

### Opsi lebih nyaman

Tambah endpoint review:

- `GET /internal/transactions/{transaction}/risk-review`

Response:

- `risk_level`
- `risk_score`
- `reasons`
- `duplicate_candidates`
- `review_summary`

Rekomendasi implementasi:

- fase 1 simpan hasil analisis saat submit
- fase 2 tambahkan endpoint review terstruktur jika dibutuhkan UI yang lebih kaya

## Rencana Implementasi Bertahap

### Fase 1 - fondasi backend

Target:

- tentukan rule awal
- buat DTO hasil analisis
- buat service analyzer
- tambahkan penyimpanan hasil ringkas

Task:

1. tentukan field risk yang akan disimpan
2. buat migration
3. buat `TransactionRiskAnalyzer`
4. buat `DuplicateTransactionDetector`
5. buat `TransactionAnomalyDetector`
6. buat unit test untuk rules utama

### Fase 2 - integrasi create/update

Target:

- analisis berjalan saat transaksi disimpan atau diperbarui

Task:

1. integrasikan analyzer ke `ZakatService`
2. simpan `risk_level`, `risk_score`, dan `risk_flags`
3. pastikan update tidak false positive terhadap transaksi grup yang sama
4. tambah feature test create dan update

### Fase 3 - Transaction Review Assistant

Target:

- operator mendapat informasi pemeriksaan yang mudah dipahami

Task:

1. tambah badge risiko di history transaksi
2. tambah panel review di detail transaksi
3. tampilkan duplicate candidate yang relevan
4. jaga agar UI tetap informatif dan tidak berisik

### Fase 4 - history dan monitoring

Target:

- risiko bisa terlihat di halaman internal

Task:

1. tampilkan badge risk di history transaksi
2. tambah filter `risk_level`
3. tambah highlight untuk transaksi mencurigakan

### Fase 5 - evaluasi untuk skripsi

Target:

- hasil bisa dijelaskan dan diukur

Task:

1. siapkan skenario data normal dan data janggal
2. ukur berapa transaksi berhasil ditandai
3. pisahkan true positive, false positive, false negative
4. susun hasil evaluasi untuk bab implementasi dan pengujian

## Kriteria Keberhasilan

Fitur dianggap berhasil jika:

- sistem mampu menandai kandidat duplikasi yang relevan
- sistem mampu menandai anomali operasional utama
- operator tetap bisa menyimpan transaksi valid tanpa terganggu oleh deteksi risiko
- hasil analisis tampil konsisten di backend
- pengujian otomatis tetap hijau

## Kebutuhan Testing

### Backend tests

- duplicate detection for same payer and same amount
- duplicate detection ignores current edited group
- anomaly detection for fitrah mismatch
- anomaly detection for fidyah mismatch
- normal transaction is not marked suspicious

### Feature tests

- create transaction stores risk result
- update transaction recomputes risk result
- history page shows risk badge
- transaction detail shows review information
- risk filter works as expected

### Manual QA

- operator memahami alasan review
- badge dan panel risiko mudah dipindai
- detail transaksi tetap rapi di desktop dan mobile

## Risiko Implementasi

1. False positive terlalu banyak
   - mitigasi: mulai dari rule sederhana dan ketat

2. Operator merasa terganggu
   - mitigasi: fokuskan informasi di area review, bukan membanjiri form input

3. Query duplicate terlalu berat
   - mitigasi: batasi window waktu dan index field relevan

4. Rule terlalu banyak di service utama
   - mitigasi: pisahkan detector per tanggung jawab

## Rekomendasi Teknis

### Rekomendasi fase awal

- pakai rule-based detection
- simpan hasil ringkas di `zakat_transactions`
- integrasikan ke `ZakatService`
- tampilkan hasil analisis di history dan detail transaksi

### Rekomendasi fase lanjutan

- tambahkan endpoint review terstruktur
- tambah tabel audit analisis jika perlu histori
- evaluasi kemungkinan hybrid scoring yang lebih cerdas

## Deliverables

1. migration field risk
2. service analyzer dan detector
3. integrasi create/update
4. panel Transaction Review Assistant
5. risk badge di history
6. automated tests
7. dokumentasi hasil implementasi

## Urutan Kerja yang Disarankan

1. finalisasi scope dan rule awal
2. pilih desain database Opsi A atau Opsi B
3. bangun backend analyzer
4. integrasikan ke `ZakatService`
5. tampilkan hasil di UI
6. tambah testing
7. siapkan narasi evaluasi skripsi

## Keputusan Awal yang Direkomendasikan

Supaya cepat bergerak dan tetap kuat untuk skripsi:

- gunakan pendekatan rule-based
- gunakan Opsi A untuk penyimpanan
- mulai dengan 3-5 rule duplikasi
- mulai dengan 4-6 rule anomali
- fokuskan assistant pada review transaksi, bukan pada warning form input

## Status

Sebagian besar Paket 1 sudah diimplementasikan:

- analisis risiko saat create dan update
- penyimpanan hasil review risiko
- badge dan filter risiko di history
- panel review risiko di detail transaksi
- command backfill untuk transaksi lama

Dokumen ini tetap menjadi baseline untuk sisa pengembangan dan evaluasi fase berikutnya.
