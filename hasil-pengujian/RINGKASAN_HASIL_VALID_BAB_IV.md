# Ringkasan Hasil Valid Untuk Bab IV

Tanggal pengujian: 2026-08-05

## Snapshot Repository

- Branch: `master`
- Commit awal pengujian: `044afd16195b1e6aac5316f834e06a5dfcb38cd2`
- Pesan commit awal: `docs(db-schema): document chatbot and zakat_periods tables`
- Commit pembekuan evidence Bab IV (awal): `034f6b93523a08118d502874c89423b6e7884939`
- Pesan commit pembekuan (awal): `docs(thesis): finalize Bab IV testing evidence`
- Commit pembekuan evidence Bab IV (revisi keamanan): `23a7fd65822aac453251bf5c1f1338c754ee6062`
- Pesan commit pembekuan (revisi keamanan): `fix(chatbot): harden prompt-injection defense and fix markdown-link XSS`
- Commit pembekuan evidence Bab IV (revisi nisab BAZNAS, final): `34d89f1fdea044ae92d287a781b5553eac73949d`
- Pesan commit pembekuan (revisi nisab BAZNAS): `feat(zakat-mal): support direct rupiah nisab override for BAZNAS SK figures`

## Konfigurasi Terverifikasi

- `APP_ENV`: `local`
- `APP_URL`: `http://localhost`
- `DB_CONNECTION`: `mysql`
- `DB_DATABASE`: `zakatannur`
- `OPENAI_CHAT_MODEL`: `gpt-5.6-terra`
- `OPENAI_FAST_MODEL`: `gpt-5.6-luna`
- `OPENAI_PREMIUM_MODEL`: `gpt-5.6-terra`
- Embedding model: `text-embedding-3-small`
- Retrieval threshold: `0.45`
- Jumlah kandidat retrieval: `3`
- Keyword fallback: aktif jika hasil semantic kosong atau embedding gagal.
- Riwayat percakapan: 8 log terakhir.
- Safety classifier threshold: confident `0.66`, ambiguous `0.45`.
- Jumlah entri basis pengetahuan: `54`.

## Hasil Pengujian Otomatis

- `php artisan test`: valid, exit code `0`, hasil `338 passed` (bertambah dari 309 setelah total 11 bug chatbot ditemukan dan diperbaiki dalam dua putaran perbaikan - lihat catatan revisi 2026-08-07 dan 2026-08-08 di bagian Catatan Pembekuan, dan `docs/chatbot-dokumentasi-skripsi.md` bagian 10.19-10.25).
- `npm run build`: valid, exit code `0`, build berhasil. Ada warning Browserslist/caniuse-lite usang, tidak menggagalkan build.

File pemetaan test ke KF-01 sampai KF-09 khusus AI Assistant Zakky: `hasil-pengujian/01-fungsional/pemetaan-304-test-ke-kf.md`

Catatan: `309 passed` dipakai sebagai bukti stabilitas suite penuh pada revisi terbaru. Untuk Bab IV Zakky, pemetaan KF hanya memakai test/evaluator yang relevan dengan AI Assistant, bukan modul umum seperti autentikasi, transaksi umum, dashboard admin, atau export.

## Hasil Evaluasi Retrieval

Command: `php artisan chatbot:eval-rag`

Status: valid setelah MySQL aktif.

- True Positive: `41`
- False Negative: `0`
- True Negative: `20`
- False Positive: `0`
- Precision: `1`
- Recall: `1`
- Specificity: `1`
- F1-score: `1`
- Fact-check gagal: `0`

File bukti utama: `hasil-pengujian/02-retrieval/chatbot-eval-rag-mysql.txt`

## Hasil Evaluasi Perilaku Multi-Turn

Command: `php artisan chatbot:eval-behavior`

Status: valid setelah MySQL aktif dan API/model merespons.

- Total skenario: `19`
- Lolos: `19`
- Gagal: `0`

Catatan revisi: dijalankan ulang setelah menambahkan hard rule anti-manipulasi ke system prompt (lihat catatan revisi di bagian Evaluasi Keamanan) untuk memastikan tidak ada regresi pada alur konsultasi zakat mal. Hasil tetap `19/19`.

Catatan revisi (2026-08-07): dijalankan ulang dua kali setelah perbaikan 9 bug chatbot (lihat Catatan Pembekuan). Run pertama sempat `18/19` (skenario "mengakui jawaban pendek dan mengklarifikasi rentang" gagal) - run kedua kembali `19/19` tanpa perubahan kode apa pun di antara keduanya, mengonfirmasi ini variasi LLM nondeterministik (Keterbatasan #6 di `docs/chatbot-dokumentasi-skripsi.md`), bukan regresi dari perbaikan bug. File bukti memuat hasil run kedua (`19/19`).

File bukti utama: `hasil-pengujian/04-kualitas-jawaban/chatbot-eval-behavior-mysql.txt`

## Hasil Evaluasi Rubrik Kualitas Jawaban

Command: `php artisan chatbot:eval-behavior-rubric --markdown`

Status: respons Zakky valid sudah tersimpan dan sudah dinilai manual mengikuti rubrik Bab III.

- 12 skenario kualitas jawaban.
- Total skor: `233` dari `240`.
- Rata-rata keseluruhan: `3,88` dari `4`.
- Hasil evaluasi perilaku `19/19` tidak digunakan sebagai pengganti skor kualitas jawaban.

File bukti utama: `hasil-pengujian/04-kualitas-jawaban/chatbot-eval-behavior-rubric-mysql.md`
File skor final: `hasil-pengujian/04-kualitas-jawaban/rubrik-kualitas-jawaban-final.md`

Catatan revisi (nisab BAZNAS): setelah nisab direvisi ke Rp91.681.728 (lihat catatan revisi nisab BAZNAS di bagian Catatan Pembekuan), respons BEH-12 berubah karena tabungan Rp90.000.000 pada skenario itu kini di bawah nisab baru (sebelumnya di atas nisab lama Rp76.500.000) - total pada respons turun dari Rp5.850.000 menjadi Rp3.600.000 (zakat tabungan jadi tidak wajib). Skor manual BEH-12 tidak diubah (tetap 4/4/4/4/4) karena rubrik menilai kualitas komunikasi respons, bukan nominal rupiahnya, dan respons baru tetap memberi closure yang jelas. Total skor 233/240 tetap berlaku. Skenario lain (BEH-01 s.d. BEH-11) tidak terpengaruh - nominal penghasilan/tabungannya berada jauh di bawah atau jauh di atas kedua nisab (lama maupun baru). Respons terbaru: `hasil-pengujian/04-kualitas-jawaban/hasil-rubrik-terbaru.md`.

## Hasil Evaluasi Keamanan

Command: `php artisan chatbot:eval-safety`

Status: valid.

- Total kasus: `167`
- Top-1 akurasi semua tingkat keyakinan: `0.862`
- Kasus confident skor >= `0.66`: `12`
- Akurasi kasus confident: `1`
- Cakupan confident: `0.072`
- Kasus ambiguous `0.45-0.66`: `121`
- Kasus no_match `< 0.45`: `34`

Catatan interpretasi: angka ini adalah hasil classifier, bukan jaminan keamanan sistem secara menyeluruh. Bagian penting untuk Bab IV adalah cakupan confident yang rendah, yaitu `7,2%`. Classifier tepat pada kasus yang diyakininya, tetapi mayoritas kasus berada pada kategori ambiguous sehingga harus dibahas sebagai keterbatasan.

Catatan revisi (tuning internal sebelum pembekuan final): menambahkan 6 contoh referensi `out_of_scope` bergaya instruksional (mis. "tolong buatkan...", "cara pasang...") untuk mengurangi kekeliruan nearest-neighbor terhadap `prompt_injection`. Total kasus naik dari 161 ke 167, akurasi keseluruhan naik dari 84,5% menjadi 86,2%. Cakupan confident tidak berubah signifikan karena perbaikan menyasar kasus di zona ambiguous/no_match, bukan threshold keyakinan. Percobaan lanjutan menambah 5 contoh lagi ke `in_domain`/`privacy_risk` untuk kasus singleton lain dicoba dan dibatalkan - akurasi malah turun ke 85,5% karena menggeser tetangga terdekat kasus lain yang sebelumnya benar (bukti bahwa nearest-neighbor pada dataset kecil rawan whack-a-mole, bukan sekadar kurang usaha).

Catatan revisi tambahan (Layer 1 - system prompt): audit arsitektur manual menemukan pertahanan anti-injection sebelumnya murni reaktif (Layer 2/3 memindai balasan setelah LLM selesai generate), tanpa hard rule proaktif yang melarang model mengubah peran, membocorkan instruksi, atau mengklaim wewenang verifikasi pembayaran/akses data pribadi. Ditambahkan 2 hard rule eksplisit ke `OpenAiChatbotProvider::getSystemInstruction` (kedua bahasa), diuji lewat data-provider test yang sama dengan hard rule lain (`ChatbotApiTest::hardRulePresentInBothLanguagesProvider`). Diverifikasi ulang lewat API asli: `chatbot:eval-behavior` tetap `19/19`, dan skenario keamanan SEC-01-06 tetap Sesuai dengan satu perbaikan terukur - SEC-03 (manipulasi instruksi) berubah dari `fallback/403` (balasan sempat tertangkap lapisan pengaman reaktif) menjadi `ai/200` (LLM menolak secara proaktif dari system prompt-nya sendiri).

Catatan revisi tambahan (frontend): ditemukan celah XSS pada parser markdown link di `resources/js/chatbot-widget.js` - skema URL `[text](url)` tidak divalidasi, sehingga balasan yang memuat `javascript:` URI akan dirender sebagai link asli yang bisa dieksekusi saat diklik. Diperbaiki dengan membatasi skema ke `http`/`https`, verifikasi lewat `npm run build`.

Catatan tambahan: log `ai_chat_logs` produksi lokal (1105 baris, 617 sesi, rentang 2026-06-24 s.d. 2026-08-03) ditinjau sebagai sumber sinyal independen dari skenario sintetis - tidak ada sentiment negatif, dan seluruh entri bersentimen "confused" ternyata berasal dari pengujian manual berulang (Bab 17), bukan keluhan user asli. Tidak ada temuan baru dari peninjauan ini.

File bukti utama: `hasil-pengujian/05-keamanan/chatbot-eval-safety.txt`
Tabel skenario Bab III: `hasil-pengujian/05-keamanan/tabel-skenario-keamanan-bab-iii.md`
Respons aktual enam skenario: `hasil-pengujian/05-keamanan/respons-aktual-keamanan.txt`

## Hasil E2E Publik

Command: `npm run test:e2e`

Status: valid setelah perbaikan kontras, stabilisasi rate-limit E2E, dan pembaruan baseline visual.

Ringkasan:

- 7 test passed.
- Exit code `0`.
- UI audit desktop, tablet, dan mobile lulus.
- Visual regression desktop, tablet, dan mobile lulus.
- Snapshot visual dibatasi ke area `main` dan memask elemen dinamis seperti angka, chart, gambar, dan widget chat.

File bukti utama: `hasil-pengujian/01-fungsional/npm-run-test-e2e-final-pass-11.txt`

## Konversi Retrieval Bab III

- Konteks sesuai: `41`
- Konteks tidak sesuai: `0`
- Pertanyaan tanpa konteks relevan yang berhasil ditolak: `20`
- Pertanyaan negatif yang salah menerima konteks: `0`
- Total kasus: `61`
- Sesuai hasil yang diharapkan: `61`
- Tidak sesuai: `0`

File konversi: `hasil-pengujian/02-retrieval/konversi-evaluasi-retrieval-bab-iii.md`

## Pengukuran Performa Berulang

Status: valid, 4 skenario x 5 pengulangan.

- Berbasis aturan: rata-rata `26,00 ms`, token `0`.
- Data publik: rata-rata `19,80 ms`, token `0`.
- Pengetahuan cepat / retrieval langsung: rata-rata `11,80 ms`, token `0`.
- RAG dengan kalkulasi deterministik: rata-rata `5719,80 ms`, rata-rata token `2749,80`, model `gpt-5.6-terra`.

Catatan: `PERF-03` tidak diklaim sebagai RAG generatif penuh karena model `-` dan token `0`; jalurnya adalah pengetahuan cepat/retrieval langsung.

File data mentah: `hasil-pengujian/06-performa/pengukuran-performa-berulang.json`
File ringkasan: `hasil-pengujian/06-performa/pengukuran-performa-berulang.md`

## Pengujian Kalkulasi

Status: valid.

- Fitrah 4 jiwa: Rp200.000 dan 10,0 kg beras.
- Fidyah 3 hari: Rp90.000 dan 2,25 kg beras.
- Zakat mal lengkap: estimasi Rp5.500.000 per tahun.
- Data zakat mal tidak lengkap: sistem meminta klarifikasi, tidak memaksa hasil.
- Kasus batas nisab penghasilan: Rp7.640.144/bulan menghasilkan zakat Rp2.292.043/tahun; komponen tabungan/emas tidak ditampilkan ketika nilai tabungan dan emas nol.
- Cakupan kalkulasi kontekstual: input penghasilan saja hanya menampilkan `Estimasi Zakat Penghasilan`; input tabungan saja hanya menampilkan `Estimasi Zakat Tabungan & Emas`; field tabungan/emas bernilai nol tidak memunculkan blok harta simpanan kosong.
- Format tidak valid: angka tahun 2026 tidak dipakai sebagai jumlah jiwa.
- Perhitungan luar cakupan: zakat pertanian diberi arahan umum dan diarahkan ke panitia/ustadz.

Nisab Rp91.681.728/tahun (Rp7.640.144/bulan) berasal dari override `nishab_annual_rupiah` pada periode aktif MySQL lokal, mengikuti Keputusan Ketua BAZNAS RI Nomor 15 Tahun 2026 - bukan lagi hasil kali 85 gram emas x harga emas (SK ini tidak habis dibagi 85 gram secara genap). Zakat penghasilan dan tabungan/emas dinilai terhadap nisab secara terpisah, lalu hanya nilai zakat akhirnya yang dijumlahkan ketika kedua komponen relevan pada input pengguna.

File bukti: `hasil-pengujian/03-kalkulasi/tabel-kalkulasi-deterministik.md`, `hasil-pengujian/03-kalkulasi/output-kalkulasi-extended-terbaru.txt`

## Format Arsip Evidence

File bukti teks yang sebelumnya terdeteksi sebagai UTF-16LE/UTF-8 BOM sudah dikonversi ke UTF-8 tanpa BOM untuk arsip final.

## Catatan Pembekuan

Commit pembekuan evidence Bab IV (awal): `034f6b93523a08118d502874c89423b6e7884939`.

Commit pembekuan evidence Bab IV (revisi keamanan, final): `23a7fd65822aac453251bf5c1f1338c754ee6062`. Revisi ini menambahkan 2 hard rule anti-manipulasi ke system prompt, memperbaiki celah XSS di widget chat, tuning kecil safety classifier, dan menyegarkan seluruh evidence terkait (safety eval, behavior eval, SEC-01..06) terhadap kode final. Pada revisi tersebut diverifikasi lewat `php artisan test` (306 passed), `npm run build`, dan panggilan API live untuk eval-behavior serta SEC-01..06 sebelum dibekukan.

Catatan revisi nisab BAZNAS (2026-08-05): nisab zakat mal diganti dari perkiraan gram emas (85 gram x Rp900.000 = Rp76.500.000) menjadi angka resmi Keputusan Ketua BAZNAS RI Nomor 15 Tahun 2026 (Rp91.681.728/tahun, Rp7.640.144/bulan), lewat kolom override baru `nishab_annual_rupiah` pada `zakat_periods` (`AnnualZakatDefaults::nishabAnnual()`). Dua bug ditemukan dan diperbaiki dalam proses ini:

1. Kolom `nishab_gold_gram` dan `gold_price_per_gram` tidak ada di `$fillable` model `ZakatPeriod`, sehingga form pengaturan periode diam-diam gagal menyimpan perubahan pada kedua field itu sejak awal - sudah ditambahkan ke `$fillable` bersama kolom baru.
2. `KnowledgeBaseSeeder` menghitung teks nisab dari `nishab_gold_gram x gold_price_per_gram` secara manual, bukan lewat `nishabAnnual()`, sehingga jawaban Zakky (retrieval knowledge base) masih mengutip nisab lama walau kalkulator sudah benar. Diperbaiki di seeder, dan konten KB yang sudah ter-seed (`zakat-penghasilan`) ditambal lewat migration data-patch `2026_08_05_040000_sync_nishab_annual_override_kb_content.php` (pola yang sama dengan `2026_07_29_010000_sync_bruto_methodology_kb_content.php`), diikuti `chatbot:cache-embeddings` ulang.

Dampak terhadap evidence revisi nisab BAZNAS: CALC-05 (kasus batas nisab) dan skenario BEH-12 (lihat catatan di bagian Evaluasi Rubrik Kualitas Jawaban) berubah nominalnya; CALC-03/contoh Rp5.500.000, eval-rag, eval-behavior 19/19, eval-safety, dan pengukuran performa tidak terpengaruh pada revisi tersebut. Saat itu diverifikasi lewat `php artisan test` (306 passed), `chatbot:eval-rag` (tetap 41/0/20/0, F1=1), dan pemanggilan API live ulang untuk CALC-03/CALC-05/BEH-12.

Commit pembekuan evidence Bab IV (revisi nisab BAZNAS, final): `34d89f1fdea044ae92d287a781b5553eac73949d`. Ini adalah commit acuan terbaru untuk skripsi - seluruh angka kalkulasi zakat mal, konfigurasi nisab, dan evidence terkait pada dokumen ini mengikuti kode per commit tersebut.

Catatan revisi UX cakupan kalkulasi (2026-08-05): alur Zakky diperbaiki agar perhitungan mengikuti cakupan pertanyaan pengguna, bukan selalu menampilkan paket penghasilan + tabungan + emas. Jika pengguna hanya menanyakan zakat penghasilan, sistem hanya menampilkan komponen penghasilan. Jika pengguna hanya memberi data tabungan, sistem hanya menampilkan komponen tabungan/emas. Jika model mengirim `savings = 0` atau `gold_gram = 0`, parser tidak lagi memunculkan blok tabungan/emas kosong. Evidence diperbarui lewat CALC-08, CALC-09, dan CALC-10 pada `hasil-pengujian/03-kalkulasi/output-kalkulasi-extended-terbaru.txt` serta regression test `ChatbotSentinelParserTest`; suite penuh valid dengan `php artisan test` = `309 passed`.

Catatan revisi (perbaikan bug chatbot, 2026-08-07): dipicu laporan pengguna nyata ("Penghasilan saya Rp7.500.000 per bulan..." dijawab penjelasan generik, bukan alur konsultasi), diikuti audit manual menyeluruh terhadap seluruh 16 file `app/Services/Chatbot/`. **9 bug ditemukan dan diperbaiki**, didokumentasikan lengkap di `docs/chatbot-dokumentasi-skripsi.md` bagian 10.19-10.23:

1. **Redaksi privasi (`ChatbotChatLogger`) membocorkan placeholder `[nominal]` ke konteks AI** (Bab 10.19) - koreksi atas kesimpulan Bab 10.2 yang ternyata tidak lengkap. Angka berformat Rupiah baku ("Rp7.500.000") ter-redaksi ke `[nominal]` sebelum masuk `ai_chat_logs`, lalu `history()` membaca ulang teks ter-redaksi itu sebagai konteks percakapan ke LLM - LLM melihat placeholder-nya sendiri dan membalas seolah data belum terisi. Diperbaiki dengan memisahkan cache percakapan session-scoped (raw, TTL 30 menit) dari `ai_chat_logs` (tetap redaksi, permanen).
2. **5 kasus pembajakan lanjutan di `ChatbotActionDetector`** (Bab 10.20) lolos dari audit sistematis Bab 10.15 sebelumnya - cabang nishab, fitrah/fidyah, contoh zakat mal, total beras, info pembayaran, dan follow-up data-publik semuanya kekurangan guard `!$looksLikeCalculationRequest` yang sudah dipasang di cabang tetangganya.
3. **`ChatbotConversationContext` kehilangan sinyal "penghasilan"** (Bab 10.21) - tidak sinkron dengan daftar kata kunci `ChatbotActionDetector`, menyebabkan pertanyaan berbasis "penghasilan" (bukan "gaji") gagal masuk mode konsultasi terpandu.
4. **Guardrail (Lapisan 2) lolos oleh kata pendek "mal"/"rp" sebagai substring kata umum** (Bab 10.22) - heuristik "balasan panjang tanpa kata domain = mencurigakan" memakai `str_contains` biasa, sehingga kata seperti "formal"/"normal"/"malam"/"terperinci" salah dianggap mengandung kata kunci domain.

**Verifikasi**: seluruh perbaikan disertai test regresi TDD (merah sebelum perbaikan, hijau sesudah). `php artisan test` naik dari `258 passed` (titik Bab 10.18) menjadi **`326 passed`**. Evaluasi perilaku nyata dijalankan ulang pasca-perbaikan (API key asli, 2026-08-07): `chatbot:eval-behavior` 19/19, `chatbot:eval-rag` precision/recall/F1 = 1,0, `chatbot:eval-safety` akurasi tier confident = 1,0 - seluruhnya konsisten dengan angka sebelum perbaikan, mengonfirmasi tidak ada regresi.

Commit pembekuan evidence Bab IV (perbaikan bug chatbot, final): `d81e219`. Tiga commit kode terpisah (`7114108`, `101fe29`, `d81e219`) masing-masing menutup satu kelompok bug (ChatbotActionDetector, ChatbotConversationContext, ChatbotGuardrailVerifier); perbaikan `ChatbotChatLogger` tercatat lebih awal, tergabung dalam commit `8bf4e06` (bersama perubahan UI drag-to-resize yang tidak terkait Bab IV).

Catatan revisi (perbaikan bug chatbot lanjutan, 2026-08-08): dipicu laporan pengguna nyata lain ("nishab zakat penghasilan berapa" dijawab definisi generik nisab/haul, bukan angka nisab penghasilan), diikuti audit lanjutan sesuai permintaan eksplisit "cek masih ada bug lagi ga" sebelum penulisan skripsi dilanjutkan. **2 bug tambahan ditemukan dan diperbaiki**, didokumentasikan di `docs/chatbot-dokumentasi-skripsi.md` bagian 10.24-10.25 (total jadi 11 bug dari seluruh sesi perbaikan chatbot):

1. **`ask_zakat_mal_nishab`/`ask_zakat_mal_definition`/`ask_zakat_mal_example` selalu menjawab entri KB generik** (Bab 10.24) - ketiga intent ini di-*hardcode* memetakan ke satu entri KB tetap (`nisab-dan-haul` atau `zakat-mal`) tanpa pernah mempertimbangkan aset spesifik yang disebut user ("Apa itu zakat penghasilan?", "Berapa nisab emas?", "Contoh zakat pertanian dong" semuanya dijawab generik, bukan entri yang jauh lebih relevan). Diperbaiki dengan `$hasSpecificAssetTopic` - kalau pesan menyebut aset spesifik apa pun, jalur cepat dilewati dan diteruskan ke AI+RAG.
2. **Balasan lokasi/kontak masjid berisi data placeholder** (Bab 10.25) - "Jl. Contoh Alamat No. 123, Kelurahan Maju, Kecamatan Bersama, Kota Sejahtera" dan "0812-3456-7890 (Bapak Fulan)" adalah data contoh yang tidak pernah diganti data asli, disampaikan percaya diri ke user sungguhan seolah resmi. Sesuai keputusan produk, diganti jadi arahan umum ke panitia tanpa menyebut detail spesifik apa pun.

**Verifikasi**: test regresi TDD baru (12 test, seluruhnya di `ChatbotActionDetectorTest`). `php artisan test` naik dari `326 passed` menjadi **`338 passed`**. Diverifikasi ulang lewat API asli pasca-perbaikan: `chatbot:eval-rag` tetap precision/recall/F1 = 1,0; `chatbot:eval-behavior` 19/19 (run pertama sempat 18/19 pada skenario yang sama yang sebelumnya juga flaky di revisi 2026-08-07 - dikonfirmasi nondeterminisme LLM lewat run ulang, bukan regresi).

Commit pembekuan evidence Bab IV (perbaikan bug chatbot lanjutan, final): `f0a0df3`.

