# AUDIT REPOSITORY UNTUK PERSIAPAN BAB IV

**Repository:** `Anantarf/zakatannur`  
**Branch yang terlihat pada GitHub:** `master`  
**Tujuan dokumen:** memetakan isi repository ke kebutuhan Bab IV, membedakan data yang sudah tersedia, data yang perlu dijalankan ulang, dan data yang masih perlu dilengkapi.

---

# 1. Kesimpulan Audit Awal

Repository sudah menyediakan sebagian besar bahan teknis untuk Bab IV. Bahan tersebut mencakup:

- arsitektur AI Assistant Zakky;
- alur pemrosesan percakapan;
- jalur berbasis aturan;
- jalur data publik;
- jalur RAG;
- basis pengetahuan;
- model embedding;
- kemiripan kosinus;
- nilai ambang retrieval;
- *keyword fallback*;
- konteks percakapan;
- kalkulasi deterministik;
- guardrail berlapis;
- routing model;
- data diagnostik;
- command evaluasi;
- dataset evaluasi;
- unit test, feature test, dan end-to-end test;
- catatan hasil pengukuran latensi;
- dokumentasi temuan selama pengembangan.

Repository tidak perlu disusun ulang untuk kebutuhan skripsi. Pekerjaan utama adalah:

1. membekukan satu commit final;
2. memverifikasi isi dokumentasi terhadap kode aktual;
3. menjalankan ulang pengujian pada commit tersebut;
4. mengonversi hasil teknis ke format Bab IV;
5. menulis hasil dan pembahasan berdasarkan data aktual.

---

# 2. Sumber Utama di Repository

## 2.1 Dokumentasi utama

File utama:

```text
docs/chatbot-dokumentasi-skripsi.md
```

Dokumen ini memuat arsitektur, metodologi, hasil evaluasi, temuan pengembangan, dan referensi file kode.

Dokumen pelengkap:

```text
docs/CHATBOT_ZAKKY.md
docs/chatbot-behavior-notes.md
docs/chatbot-thesis-notes.md
docs/rag-threshold-evaluation.md
```

## 2.2 Kode utama yang perlu diverifikasi

```text
app/Services/Chatbot/ChatbotOrchestrator.php
app/Services/Chatbot/ChatbotActionDetector.php
app/Services/Chatbot/ChatbotCalculatorService.php
app/Services/Chatbot/ChatbotPublicDataResponder.php
app/Services/Chatbot/ChatbotLanguageDetector.php
app/Services/Chatbot/ChatbotSentimentDetector.php
app/Services/Chatbot/ChatbotConversationContext.php
app/Services/Chatbot/ChatbotChatLogger.php
app/Services/Chatbot/ChatbotSentinelParser.php
app/Services/Chatbot/ChatbotZakatMalGuide.php
app/Services/Chatbot/ChatbotGuardrailVerifier.php
app/Services/Chatbot/Safety/ChatbotSafetyClassifier.php
app/Services/Chatbot/Knowledge/KnowledgeRetriever.php
app/Services/Chatbot/Providers/OpenAiEmbeddingsProvider.php
app/Services/Chatbot/Knowledge/KnowledgeEmbeddingsCache.php
app/Services/Chatbot/Providers/OpenAiChatbotProvider.php
```

## 2.3 Basis pengetahuan

```text
database/seeders/KnowledgeBaseSeeder.php
```

Dokumentasi repository menyebut 54 entri basis pengetahuan. Jumlah ini harus dihitung ulang pada commit final sebelum dicantumkan di Bab IV.

## 2.4 Pengujian

Folder yang tersedia:

```text
tests/Feature
tests/Unit
tests/e2e
```

Dokumentasi juga menyebut beberapa pengujian utama:

```text
ChatbotApiTest
ChatbotKnowledgeRetrievalEvalTest
ChatbotSafetyClassifierTest
ChatbotGuardrailVerifierTest
ChatbotStreamParserTest
```

---

# 3. Identitas Versi Pengujian

Sebelum pengujian final, jalankan:

```bash
git status
git branch --show-current
git rev-parse HEAD
git log -1 --format="%H%n%ad%n%s" --date=iso
```

Catat hasilnya:

```text
Branch:
Commit hash:
Tanggal commit:
Pesan commit:
Tanggal pengujian:
Status working tree:
```

Aturan:

- pengujian final dilakukan pada satu commit;
- working tree harus bersih;
- perubahan setelah pengujian harus dibuat dalam commit baru;
- pengujian yang terdampak harus dijalankan ulang;
- commit hash harus dicantumkan pada dokumentasi hasil.

---

# 4. Konfigurasi yang Harus Dikunci

Catat konfigurasi berikut dari `.env` atau file konfigurasi terkait tanpa menyalin API key:

```text
APP_ENV:
APP_URL:
DB_CONNECTION:
DB_HOST:
DB_PORT:
DB_DATABASE:

OPENAI_CHAT_MODEL:
OPENAI_FAST_MODEL:
OPENAI_PREMIUM_MODEL:

Embedding model:
Retrieval threshold:
Jumlah kandidat:
Keyword fallback:
Jumlah riwayat percakapan:
Safety classifier threshold:
```

Jangan mencantumkan:

- API key;
- password basis data;
- token rahasia;
- kredensial hosting.

---

# 5. Temuan Implementasi yang Sudah Terdokumentasi

## 5.1 Arsitektur

Dokumentasi menunjukkan alur:

```text
ChatbotController
-> ChatbotOrchestrator
-> fast-path atau jalur AI
```

Fast-path mencakup:

- `ChatbotActionDetector`;
- `ChatbotCalculatorService`;
- `ChatbotPublicDataResponder`.

Jalur AI mencakup:

- deteksi bahasa;
- deteksi sentimen;
- konteks percakapan;
- retrieval;
- histori percakapan;
- LLM;
- sentinel kalkulasi;
- guardrail;
- safety classifier;
- pencatatan log.

## 5.2 Retrieval

Dokumentasi menyebut:

```text
Model embedding: text-embedding-3-small
Metode: cosine similarity
Threshold: 0,45
Fallback: keyword scoring
Penyimpanan embedding: cache permanen
```

Semua nilai tersebut harus diverifikasi terhadap kode dan konfigurasi final.

## 5.3 Konteks percakapan

Dokumentasi menyebut:

- context blob dikirim bolak-balik antara frontend dan backend;
- bukan session server-side;
- histori delapan giliran terakhir diambil dari `ai_chat_logs`;
- mode `zakat_mal_consultation`;
- retrieval dapat dilewati untuk balasan pendek tertentu;
- nominal tertentu direduksi sebelum disimpan ke log.

Bagian ini perlu diselaraskan dengan istilah "konteks percakapan terbatas" pada Bab III.

## 5.4 Kalkulasi deterministik

Dokumentasi menunjukkan pola:

```text
LLM mengekstraksi variabel
-> menghasilkan tag [HITUNG:{...}]
-> ChatbotSentinelParser membaca tag
-> ChatbotZakatMalGuide menghitung dengan PHP
-> hasil disisipkan melalui blok [[HASIL]]...[[/HASIL]]
```

Bab IV harus membedakan:

- ekstraksi nilai oleh model;
- perhitungan oleh fungsi aplikasi;
- penyusunan respons akhir.

## 5.5 Guardrail

Dokumentasi menunjukkan tiga lapisan:

1. instruksi pada system prompt;
2. `ChatbotGuardrailVerifier` berbasis keyword/regex;
3. `ChatbotSafetyClassifier` berbasis embedding similarity.

Jangan menyatakan guardrail menjamin keamanan penuh. Jelaskan hasil hanya pada skenario yang diuji.

## 5.6 Routing model

Dokumentasi menyebut tiga peran model:

- premium;
- fast;
- default.

Pemilihan model dipengaruhi oleh:

- sinyal kompleksitas;
- keberadaan angka;
- jumlah konteks;
- panjang pesan.

Nama model pada dokumentasi lama perlu dibandingkan dengan konfigurasi terbaru sebelum dicantumkan.

---

# 6. Command Evaluasi yang Sudah Tersedia

Dokumentasi repository menyebut empat command:

```bash
php artisan chatbot:eval-rag
php artisan chatbot:eval-behavior
php artisan chatbot:eval-behavior-rubric
php artisan chatbot:eval-safety
```

Sebelum menjalankan, pastikan command masih tersedia:

```bash
php artisan list | findstr chatbot
```

atau pada terminal non-Windows:

```bash
php artisan list | grep chatbot
```

Simpan daftar command:

```bash
php artisan list > hasil-pengujian/00-konfigurasi/daftar-command.txt
```

---

# 7. Pengujian Dasar Repository

Jalankan seluruh pengujian otomatis:

```bash
php artisan test
```

Simpan output:

```bash
php artisan test > hasil-pengujian/01-fungsional/php-artisan-test.txt
```

Jika diperlukan, jalankan kelompok tertentu:

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

Untuk E2E, baca konfigurasi aktual pada:

```text
playwright.config.js
tests/e2e
package.json
```

Jangan mengasumsikan command E2E sebelum memeriksa `package.json`.

---

# 8. Pemetaan Repository ke Bab IV

| Subbab Bab IV | Sumber Repository | Status |
|---|---|---|
| 4.1 Hasil Pengembangan Prototipe | riwayat commit, dokumentasi temuan, catatan tuning | tersedia, perlu dipilih |
| 4.2 Hasil Implementasi AI Assistant Zakky | orchestrator, controller, action detector, public responder | tersedia |
| 4.3 Hasil Implementasi Mekanisme Retrieval | `KnowledgeRetriever`, embedding provider, cache, seeder, threshold doc | tersedia, perlu verifikasi |
| 4.4 Hasil Pengujian Fungsional | `tests/Unit`, `tests/Feature`, `tests/e2e` | tersedia, perlu dijalankan ulang |
| 4.5 Hasil Evaluasi Retrieval | `chatbot:eval-rag`, retrieval eval test, threshold doc | tersedia, perlu disesuaikan dengan Bab III |
| 4.6 Hasil Pengujian Kalkulasi Deterministik | sentinel parser, calculator service, zakat guide, tests | tersedia, perlu hasil acuan manual |
| 4.7 Hasil Evaluasi Kualitas Jawaban | behavior rubric dataset dan command | tersedia sebagian, skor manual belum final |
| 4.8 Hasil Pengujian Keamanan Respons | guardrail tests, safety classifier, `chatbot:eval-safety` | tersedia, perlu dijalankan ulang |
| 4.9 Hasil Evaluasi Performa | metadata penggunaan, data latensi, catatan optimasi | tersedia sebagian, perlu pengukuran final |
| 4.10 Pembahasan | seluruh hasil, penelitian terdahulu, temuan bug dan perbaikan | belum ditulis |

---

# 9. Penyesuaian dengan Metodologi Bab III

Dokumentasi teknis repository menggunakan beberapa metrik yang tidak seluruhnya dipakai pada Bab III.

## 9.1 Retrieval

Command repository dapat menghasilkan:

- TP;
- FN;
- TN;
- FP;
- precision;
- recall;
- specificity;
- F1-score;
- top-3 retrieval.

Bab III terbaru menggunakan kategori:

- konteks sesuai;
- konteks tidak sesuai;
- pertanyaan tanpa konteks relevan berhasil ditolak.

Keputusan:

- output command tetap disimpan sebagai bukti teknis;
- tabel utama Bab IV mengikuti kategori Bab III;
- metrik lain dapat disebut sebagai data tambahan hanya jika konsisten dan benar-benar diperlukan;
- jangan mengubah Bab III setelah melihat hasil hanya untuk memasukkan seluruh metrik repository.

## 9.2 Kualitas jawaban

Command `chatbot:eval-behavior-rubric` menggunakan:

- 12 skenario;
- 7 aspek;
- skala 1-5.

Bab III terbaru menggunakan:

- Ketepatan;
- Relevansi;
- Kelengkapan;
- Kejelasan;
- Konsistensi terhadap Sumber;
- skala 1-4.

Keputusan:

- jangan langsung menyalin rubrik lama;
- command dapat digunakan untuk menghasilkan respons;
- penilaian final mengikuti rubrik Bab III;
- skor dilakukan oleh peneliti;
- alasan skor dicatat;
- penilaian satu peneliti dinyatakan sebagai keterbatasan.

## 9.3 Keamanan

Repository mengevaluasi classifier berbasis kategori dan threshold. Bab III mengevaluasi perilaku respons pada skenario:

- di luar topik;
- manipulasi instruksi;
- informasi sensitif;
- informasi tidak tersedia;
- kewenangan ahli;
- masukan normal.

Keputusan:

- hasil classifier dapat digunakan sebagai bukti tambahan;
- tabel utama Bab IV mengikuti skenario Bab III;
- jangan menyamakan akurasi classifier dengan keamanan sistem secara keseluruhan.

## 9.4 Performa

Dokumentasi lama mencatat beberapa angka latensi dan hasil tuning.

Keputusan:

- angka lama bukan angka final;
- pengukuran harus diulang pada commit, model, basis pengetahuan, dan jaringan yang digunakan saat penelitian;
- laporkan lingkungan dan jumlah pengulangan;
- jangan menyimpulkan kemampuan beban pengguna.

---

# 10. Data yang Sudah Siap Dipakai

Data berikut sudah dapat digunakan sebagai dasar penjelasan implementasi setelah diverifikasi:

- nama komponen;
- alur controller dan orchestrator;
- pembagian fast-path dan jalur AI;
- model embedding;
- cosine similarity;
- keyword fallback;
- konteks percakapan;
- sentinel kalkulasi;
- tiga lapisan guardrail;
- folder dan jenis pengujian;
- command evaluasi;
- daftar temuan bug dan perbaikan.

Data tersebut belum otomatis menjadi "hasil final". Bab IV harus mencantumkan versi kode dan bukti pengujian.

---

# 11. Data yang Harus Dijalankan Ulang

## Wajib

1. seluruh test Laravel;
2. evaluasi retrieval;
3. evaluasi perilaku multi-turn;
4. keluaran untuk rubrik kualitas jawaban;
5. evaluasi safety;
6. skenario kalkulasi deterministik;
7. pengukuran performa;
8. pengujian E2E jika digunakan sebagai bukti.

## Alasan

- model dapat berubah;
- konfigurasi dapat berubah;
- isi basis pengetahuan dapat berubah;
- hasil LLM bersifat nondeterministik;
- latensi bergantung pada lingkungan;
- dokumentasi lama dapat berasal dari commit berbeda.

---

# 12. Data yang Masih Harus Dilengkapi

1. branch dan commit hash final;
2. tanggal pengujian;
3. jumlah entri basis pengetahuan final;
4. nilai threshold aktual;
5. jumlah kandidat aktual;
6. konfigurasi model final;
7. dataset final sesuai Bab III;
8. hasil acuan kalkulasi yang dihitung independen;
9. skor kualitas jawaban skala 1-4;
10. bukti tangkapan layar;
11. output terminal;
12. tabel hasil Bab IV;
13. keterbatasan penelitian;
14. alasan pemilihan contoh hasil yang ditampilkan.

---

# 13. Struktur Folder Bukti

Buat folder:

```text
hasil-pengujian/
|-- 00-konfigurasi/
|   |-- commit.txt
|   |-- konfigurasi-tanpa-rahasia.txt
|   `-- daftar-command.txt
|-- 01-fungsional/
|-- 02-retrieval/
|-- 03-kalkulasi/
|-- 04-kualitas-jawaban/
|-- 05-keamanan/
|-- 06-performa/
`-- 07-screenshot/
```

Gunakan nama file:

```text
PF-01.txt
PF-01.png
ER-01.json
KD-01.txt
KJ-01.md
KR-01.txt
EP-01.json
```

---

# 14. Urutan Eksekusi

## Tahap 1 - Persiapan

```bash
git status
git branch --show-current
git rev-parse HEAD
php -v
php artisan --version
node -v
npm -v
```

## Tahap 2 - Konfigurasi

- cek `.env` tanpa menyalin rahasia;
- catat model;
- catat threshold;
- catat basis pengetahuan;
- catat jumlah kandidat;
- catat mode cache.

## Tahap 3 - Pengujian otomatis

```bash
php artisan test
php artisan list
```

## Tahap 4 - Evaluasi chatbot

```bash
php artisan chatbot:eval-rag
php artisan chatbot:eval-behavior
php artisan chatbot:eval-behavior-rubric --markdown
php artisan chatbot:eval-safety
```

Periksa opsi command melalui:

```bash
php artisan help chatbot:eval-rag
php artisan help chatbot:eval-behavior
php artisan help chatbot:eval-behavior-rubric
php artisan help chatbot:eval-safety
```

## Tahap 5 - Pengujian manual

- fungsional melalui antarmuka;
- kalkulasi normal;
- data kurang;
- koreksi nilai;
- di luar ruang lingkup;
- prompt injection;
- data publik;
- percakapan lanjutan;
- kegagalan API jika dapat disimulasikan dengan aman.

## Tahap 6 - Konversi hasil

- kelompokkan sesuai enam pengujian Bab III;
- susun tabel;
- hitung frekuensi;
- isi rubrik;
- pilih bukti;
- catat keterbatasan.

## Tahap 7 - Penulisan

```text
4.1-4.3 implementasi
-> 4.4-4.9 hasil pengujian
-> 4.10 pembahasan
```

---

# 15. Checklist Sebelum Menulis Bab IV

- [ ] Bab I-III sudah dikunci.
- [ ] Branch final sudah ditentukan.
- [ ] Commit hash sudah dicatat.
- [ ] Working tree bersih.
- [ ] Konfigurasi tanpa rahasia sudah dicatat.
- [ ] Basis pengetahuan final sudah dihitung.
- [ ] Threshold dan jumlah kandidat sudah diverifikasi.
- [ ] Semua test otomatis sudah dijalankan.
- [ ] Semua command evaluasi sudah dijalankan.
- [ ] Dataset final sesuai Bab III.
- [ ] Hasil kalkulasi acuan tersedia.
- [ ] Skor kualitas jawaban sudah diisi.
- [ ] Skenario keamanan sudah diuji.
- [ ] Data performa final tersedia.
- [ ] Bukti pengujian tersimpan.
- [ ] Tabel hasil sudah disusun.
- [ ] Tidak ada hasil lama yang dipakai tanpa label.
- [ ] Tidak ada klaim di luar data pengujian.

---

# 16. Keputusan Akhir

Repository sudah cukup untuk menjadi dasar Bab IV. Pekerjaan berikutnya bukan membuat arsitektur atau instrumen dari nol, tetapi:

```text
bekukan commit
-> verifikasi implementasi
-> jalankan ulang pengujian
-> sesuaikan hasil dengan Bab III
-> susun tabel dan bukti
-> tulis Bab IV
```

Dokumentasi repository digunakan sebagai peta, sedangkan output pengujian pada commit final digunakan sebagai bukti hasil penelitian.

