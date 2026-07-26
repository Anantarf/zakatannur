-[=]'

']]

# Dokumentasi Teknis Chatbot Zakky — Acuan Pembahasan Skripsi

Dokumen ini merangkum arsitektur, metodologi, dan hasil evaluasi terukur dari chatbot Zakky (asisten zakat Masjid An-Nur), disusun sebagai bahan mentah untuk bab pembahasan/hasil skripsi. Semua angka di sini berasal dari pengujian nyata terhadap kode yang berjalan (bukan estimasi), dengan referensi file dan baris kode supaya bisa ditelusuri ulang.

Dokumen pelengkap yang relevan:

- `docs/CHATBOT_ZAKKY.md` — dokumentasi arsitektur & troubleshooting operasional.
- `docs/chatbot-behavior-notes.md` — spesifikasi perilaku konsultatif (96 poin) yang mendasari `ChatbotBehaviorRubricDataset`.
- `docs/chatbot-thesis-notes.md` — ringkasan singkat apa yang bisa/tidak bisa dibuktikan otomatis.
- `docs/rag-threshold-evaluation.md` — catatan evaluasi threshold semantic search.

---

## 1. Ringkasan Sistem

Zakky adalah chatbot berbasis RAG (Retrieval-Augmented Generation) yang menjawab pertanyaan seputar zakat, fidyah, infaq/shodaqoh, dan layanan Masjid An-Nur. Sistem menggabungkan empat pendekatan sekaligus, bukan satu:

1. **Fast-path rule-based** — pertanyaan sederhana/berulang (total penerimaan, jadwal, sapaan) dijawab tanpa memanggil LLM sama sekali.
2. **RAG (semantic search + LLM)** — pertanyaan umum dijawab LLM dengan konteks dari basis pengetahuan (Knowledge Base) yang diambil lewat pencarian embedding.
3. **Kalkulasi deterministik** — perhitungan zakat mal TIDAK dihitung oleh LLM; LLM hanya mengekstrak variabel, backend PHP yang menghitung.
4. **Guardrail berlapis** — tiga lapisan independen untuk mencegah jawaban di luar topik, prompt injection, dan halusinasi (detail di Bab 6).

Provider LLM: OpenAI-compatible API (mendukung model apa pun yang expose endpoint `/chat/completions` bergaya OpenAI — di lingkungan ini dikonfigurasi ke tiga model dengan peran berbeda, lihat Bab 7).

---

## 2. Arsitektur Sistem

```
ChatbotController (HTTP endpoint: /api/chatbot/message, /api/chatbot/stream)
    ↓
ChatbotOrchestrator                              [app/Services/Chatbot/ChatbotOrchestrator.php]
    ├─ 1. getQuickResponse()  → fast-path rule-based (tanpa LLM)
    │     └─ ChatbotActionDetector                [ChatbotActionDetector.php]      (intent classification, keyword-based)
    │     └─ ChatbotCalculatorService              (kalkulasi fitrah/fidyah deterministik)
    │     └─ ChatbotPublicDataResponder            (data dashboard publik)
    │
    └─ 2. answerFromAi() / streamFromAi()  → jalur AI penuh
          ├─ ChatbotLanguageDetector               (deteksi bahasa id/en)
          ├─ ChatbotSentimentDetector               (deteksi frustrasi, koreksi angka)
          ├─ ChatbotConversationContext             (mode percakapan, hint prompt, cache key)
          ├─ KnowledgeRetriever                     (RAG: semantic search → keyword fallback)
          │     └─ OpenAiEmbeddingsProvider (text-embedding-3-small)
          │     └─ KnowledgeEmbeddingsCache (cache permanen, di-refresh sinkron saat KB disimpan)
          ├─ ChatbotChatLogger                      (histori percakapan + redaksi nominal)
          ├─ OpenAiChatbotProvider                  (pemanggilan LLM, routing 3 model)
          ├─ ChatbotSentinelParser                  ([HITUNG:{...}] → hasil kalkulasi PHP → [[HASIL]]...[[/HASIL]])
          ├─ ChatbotGuardrailVerifier                (LAPISAN KEAMANAN #1: keyword blocklist, per-kalimat)
          └─ ChatbotSafetyClassifier                 (LAPISAN KEAMANAN #2: embedding similarity, sekali per balasan)
```

### Tabel komponen inti

| Komponen                       | File                                                        | Tanggung jawab                                                                                                         |
| ------------------------------ | ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| `ChatbotOrchestrator`        | `app/Services/Chatbot/ChatbotOrchestrator.php`            | Mengatur seluruh alur: routing fast-path vs AI, retrieval, logging, finalisasi balasan                                 |
| `ChatbotActionDetector`      | `app/Services/Chatbot/ChatbotActionDetector.php`          | Klasifikasi intent berbasis kata kunci untuk fast-path (bukan ML)                                                      |
| `KnowledgeRetriever`         | `app/Services/Chatbot/Knowledge/KnowledgeRetriever.php`   | RAG: cosine similarity embedding, fallback ke keyword scoring                                                          |
| `ChatbotConversationContext` | `app/Services/Chatbot/ChatbotConversationContext.php`     | Deteksi mode`zakat_mal_consultation`, suntik hint instruksi ke prompt                                                |
| `ChatbotZakatMalGuide`       | `app/Services/Chatbot/ChatbotZakatMalGuide.php`           | Kalkulasi zakat mal murni PHP (bukan LLM)                                                                              |
| `ChatbotSentinelParser`      | `app/Services/Chatbot/ChatbotSentinelParser.php`          | Mem-parsing tag`[HITUNG:{...}]` dari LLM, memanggil kalkulator, menyisipkan hasil sebagai `[[HASIL]]...[[/HASIL]]` |
| `ChatbotGuardrailVerifier`   | `app/Services/Chatbot/ChatbotGuardrailVerifier.php`       | Guardrail keyword blocklist, jalan per-kalimat saat streaming                                                          |
| `ChatbotSafetyClassifier`    | `app/Services/Chatbot/Safety/ChatbotSafetyClassifier.php` | Guardrail tambahan berbasis embedding similarity, jalan sekali per balasan final                                       |
| `ChatbotChatLogger`          | `app/Services/Chatbot/ChatbotChatLogger.php`              | Persist histori ke`ai_chat_logs`, redaksi nominal sebelum disimpan                                                   |

---

## 3. Alur Percakapan End-to-End

1. Pesan user masuk ke `ChatbotOrchestrator::handle()`.
2. **Cek fast-path** (`getQuickResponse()`): kalau intent cocok dengan pola rule-based (mis. "total uang", kalkulasi fitrah/fidyah dengan pola angka eksplisit) dan percakapan belum masuk mode AI (`last_source !== 'ai'`), jawab langsung tanpa LLM. Ini membuat pertanyaan sederhana/berulang nyaris instan (tidak ada panggilan API).
3. Kalau tidak cocok fast-path, masuk **jalur AI**:
   a. Deteksi bahasa, sentimen, dan mode percakapan.
   b. `KnowledgeRetriever` mencari entri KB paling relevan (RAG) — **kecuali** kalau ini balasan lanjutan pendek (≤8 kata) dalam konsultasi zakat mal yang sudah berjalan, di mana langkah ini di-skip demi latensi (lihat Bab 7.3).
   c. Histori 8 giliran percakapan terakhir diambil dari `ai_chat_logs`.
   d. Prompt sistem dirakit: instruksi dasar + konteks KB + hint mode percakapan + histori.
   e. LLM dipanggil (model dipilih berdasarkan kompleksitas pesan, lihat Bab 7.1).
   f. Balasan LLM diproses: sentinel `[HITUNG:{...}]` (jika ada) diganti hasil kalkulasi PHP asli.
   g. **Guardrail lapis 1** (keyword blocklist) memverifikasi balasan.
   h. **Guardrail lapis 2** (embedding safety classifier) memverifikasi balasan final.
   i. Balasan dikirim ke user, konteks (`mode`, `topic`, `last_source`) dikembalikan untuk di-roundtrip pada giliran berikutnya.

---

## 4. Retrieval-Augmented Generation (RAG)

### 4.1 Basis Pengetahuan

- **54 entri** di tabel `knowledge_bases` ([database/seeders/KnowledgeBaseSeeder.php](../database/seeders/KnowledgeBaseSeeder.php)), mencakup topik zakat fitrah, zakat mal (penghasilan, emas, tabungan, perdagangan, pertanian, peternakan, properti sewa, saham/investasi, warisan, dll.), fidyah, infaq/shodaqoh, operasional layanan (cara bayar, konfirmasi, privasi data), dan 3 entri tambahan hasil tinjauan gap konten (bruto/netto zakat penghasilan, penyaluran mandiri vs. panitia, zakat mal yang terlewat).
- Nominal (zakat fitrah, fidyah, nisab) **tidak di-hardcode** di teks KB — diambil langsung dari `AnnualZakatDefaultsResolver`, sumber yang sama dipakai kalkulator transaksi, sehingga teks KB tidak bisa "menyimpang" dari perhitungan sistem yang sebenarnya.

### 4.2 Metode Pencarian

`KnowledgeRetriever::search()` ([KnowledgeRetriever.php:24](../app/Services/Chatbot/Knowledge/KnowledgeRetriever.php#L24)):

1. **Semantic search** (utama): embedding pesan user (model `text-embedding-3-small`) dibandingkan via **cosine similarity** terhadap embedding seluruh entri KB (di-cache permanen, di-refresh sinkron tiap KB disimpan/dihapus). Threshold **0,45** — hasil di bawah itu dianggap noise/di luar topik.
2. **Keyword fallback**: kalau semantic search gagal (mis. API embedding down) atau tidak ada yang lolos threshold, sistem jatuh ke penilaian skor berbasis kata kunci per entri (`score()`, [KnowledgeRetriever.php:113](../app/Services/Chatbot/Knowledge/KnowledgeRetriever.php#L113)).

Threshold 0,45 ditentukan dari observasi empiris: similarity >0,60 = relevansi sangat tinggi, 0,45–0,59 = relevansi moderat (parafrase/sinonim), <0,45 = di luar topik/noise (dijelaskan lengkap di docblock `searchViaEmbeddings()` dan `docs/rag-threshold-evaluation.md`).

---

## 5. Manajemen Konteks Percakapan

`ChatbotConversationContext` ([ChatbotConversationContext.php](../app/Services/Chatbot/ChatbotConversationContext.php)) menjaga state percakapan lewat context blob yang di-roundtrip antara frontend dan backend (bukan session server-side), berisi `last_intent`, `last_source`, `topic`, `mode`.

**Mode `zakat_mal_consultation`** aktif ketika pesan mengandung sinyal zakat mal (kata "zakat mal", "hitung zakat", "nisab") atau sinyal finansial (angka, "gaji", "tabungan", "emas", "hutang"). Begitu mode ini aktif, sistem menyisipkan instruksi tambahan ke prompt: rangkum data yang sudah ada, tanyakan hanya data yang kurang, jangan ulangi penjelasan umum, keluarkan `[HITUNG:{...}]` begitu data cukup. Mode ini **tetap bertahan** untuk balasan pendek/ambigu (angka polos, "tidak ada hutang", "iya") supaya alur konsultasi tidak putus, tapi **keluar** kalau user eksplisit berpindah topik (menyebut zakat fitrah, fidyah, jadwal, dll.) — logika ini diuji di `tests/Feature/ChatbotApiTest.php::test_switching_topic_mid_consultation_leaves_zakat_mal_consultation_mode`.

**Privasi log**: `ChatbotChatLogger::redactNominals()` ([ChatbotChatLogger.php:67](../app/Services/Chatbot/ChatbotChatLogger.php#L67)) mengganti angka yang terlihat seperti nominal uang (>=6 digit atau berformat grouping) dengan `[nominal]` sebelum disimpan ke `ai_chat_logs` — data finansial jamaah tidak tersimpan dalam bentuk plain text jangka panjang, sementara model tetap bisa merekonstruksi data dari frasa asli user pada giliran berikutnya (lihat temuan Bab 9).

---

## 6. Kalkulasi Zakat Mal Deterministik (Sentinel Pattern)

LLM **tidak pernah** menghitung nominal zakat mal sendiri — ini keputusan desain eksplisit untuk menghindari halusinasi angka. Alurnya:

1. Prompt sistem melarang LLM menghitung sendiri dan mewajibkan output tag `[HITUNG:{"income_monthly":...,"expenses_monthly":...,"savings":...,"gold_gram":...,"debt":...}]` begitu data cukup ([OpenAiChatbotProvider.php:335-337](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L335-L337)).
2. `ChatbotSentinelParser` mendeteksi tag ini, mengekstrak JSON variabelnya, dan memanggil `ChatbotZakatMalGuide::calculate()` — fungsi PHP murni yang menghitung zakat penghasilan (basis penghasilan bersih bulanan × 12, terpisah dari tabungan) dan zakat tabungan/emas (basis harta simpanan saat ini dikurangi hutang) secara independen terhadap nisab.
3. Hasil kalkulasi disisipkan kembali ke balasan sebagai blok `[[HASIL]]...[[/HASIL]]`, yang di-render frontend sebagai kartu hasil terpisah (lihat `resources/js/chatbot-widget.js`).

Prinsip metodologi (didokumentasikan di KB entri `catatan-metodologi-zakat`): penghasilan tahunan **tidak** dijumlah mentah dengan saldo tabungan sebagai satu basis, karena saldo tabungan biasanya sudah mencerminkan penghasilan yang diterima dan dibelanjakan sepanjang tahun — menjumlahkannya akan menghitung penghasilan yang sama dua kali.

---

## 7. Optimasi Model & Latensi

### 7.1 Routing 3 Model

`OpenAiChatbotProvider::selectModel()` ([OpenAiChatbotProvider.php:255](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L255)) memilih salah satu dari 3 model berdasarkan kompleksitas pesan:

| Tingkat           | Kapan dipakai                                                                                                                                                                          | Contoh trigger                               |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------- |
| **Premium** | Pesan punya **≥2 sinyal kompleksitas berbeda** (hitung, zakat mal, nisab, haul, emas, tabungan, hutang, aset, penghasilan, gaji, investasi, saham, usaha, warisan, konsultasi), atau 1 sinyal + ada angka eksplisit, atau konteks ≥3 entri, atau pesan >350 karakter | "Saya mau hitung zakat mal, gaji 10 juta..." (4 sinyal) |
| **Fast**    | Pesan pendek (≤6 kata) tanpa konteks, atau cocok pola sapaan/FAQ singkat                                                                                                              | "Halo", "jadwal zakat fitrah?"               |
| **Default** | Sisanya, termasuk pesan dengan **hanya 1 sinyal kompleksitas** tanpa angka                                                                                                            | "Saya punya hutang, apakah tetap wajib zakat?" (1 sinyal) |

Aturan "≥2 sinyal" ini hasil tuning di Bab 7.4 — sebelumnya 1 kata kunci apa pun sudah cukup memicu premium. Diuji otomatis di `tests/Feature/ChatbotApiTest.php::test_openai_provider_routes_fast_default_and_premium_models`.

### 7.2 Latensi Terukur (pengukuran langsung, bukan estimasi)

Angka baseline berikut diambil **sebelum** tuning threshold routing di Bab 7.4:

| Tahap                                            | Waktu terukur |
| ------------------------------------------------ | ------------- |
| Embedding pesan user + cosine search KB          | ~1.000 ms     |
| LLM completion, model premium                    | ~4.600 ms     |
| LLM completion, model fast/default               | ~2.300 ms     |
| Total end-to-end (jalur AI penuh, non-streaming) | ~4.200 ms     |

Frontend menggunakan **streaming** (`resources/js/chatbot-widget.js`, fallback ke non-streaming kalau gagal) sehingga latensi yang *dirasakan* user lebih rendah dari angka total di atas — teks muncul progresif, bukan menunggu balasan penuh selesai.

### 7.3 Optimasi Skip-Retrieval

Ditemukan lewat pengukuran: setiap pesan — termasuk balasan lanjutan pendek seperti "iya sudah benar semua" di tengah konsultasi — tetap menjalankan embedding+cosine-search KB penuh (~1 detik), padahal hint mode percakapan yang sebenarnya mengarahkan perilaku LLM tidak bergantung pada hasil retrieval tersebut (`ChatbotConversationContext::applyConversationHint()` tetap jalan meski hasil retrieval kosong).

**Fix** ([ChatbotOrchestrator.php, method `retrieveContexts()`](../app/Services/Chatbot/ChatbotOrchestrator.php)): retrieval KB di-skip kalau (a) mode percakapan sebelumnya SUDAH `zakat_mal_consultation` (bukan baru masuk turn ini), DAN (b) pesan pendek (≤8 kata). Verifikasi terukur: giliran lanjutan turun dari ~5.900 ms menjadi ~4.100 ms (hemat ~1.800 ms), tanpa mengubah hasil akhir (mode dan hasil kalkulasi tetap benar).

### 7.4 Optimasi Threshold Routing Model (Precision Tuning)

**Masalah**: `needsPremiumModel()` awalnya memicu model premium (~4,4–4,6 detik) hanya dengan **satu** kata kunci apa pun dari 18 kata yang ada — termasuk kata umum seperti "emas", "gaji", "usaha", "warisan" yang sering muncul di pertanyaan ringan/tangensial, bukan cuma di kalkulasi kompleks yang sungguh butuh model bertenaga. Karena pertanyaan definisi murni (mis. "apa itu nisab?") sudah lebih dulu terjawab lewat fast-path KB tanpa pernah sampai ke pemilihan model (Bab 3, langkah 2), keyword ini justru banyak menjaring pertanyaan yang lolos fast-path tapi tetap sederhana secara reasoning.

**Fix**: `needsPremiumModel()` diubah untuk mensyaratkan **≥2 sinyal kompleksitas berbeda**, atau 1 sinyal dipasangkan dengan angka eksplisit ("emas 100 gram"), sebelum menaikkan ke tier premium ([OpenAiChatbotProvider.php:266](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L266)).

**Bug yang ditemukan saat verifikasi (bukan cacat desain, tapi pelajaran metodologis penting)**: implementasi pertama dari fix ini tidak langsung berfungsi — pesan uji "Saya punya hutang, apakah tetap wajib zakat?" (seharusnya 1 sinyal) tetap ter-route ke premium. Investigasi menunjukkan kata kunci `'utang'` secara substring **tercakup di dalam** `'hutang'` (h-**utang**), sehingga satu kata "hutang" di pesan dihitung sebagai 2 sinyal berbeda, bukan 1 — meniadakan efek threshold ≥2 sepenuhnya untuk kasus itu. Diperbaiki dengan menghapus `'utang'` dari daftar (sudah tercakup `'hutang'`) dan `'perhitungan'` (sudah tercakup `'hitung'`) — dua pasangan kata kunci yang saling ber-substring dalam daftar 18 kata aslinya.

**Ini contoh baik untuk narasi metodologi skripsi**: perubahan logika sekecil apa pun pada sistem berbasis pencocokan kata kunci perlu diverifikasi dengan pengukuran langsung (bukan cuma dibaca kodenya), karena interaksi antar-kata kunci (substring overlap) tidak selalu terlihat dari inspeksi manual daftar kata.

**Hasil terukur** (rata-rata 3 sampel per kondisi, model yang benar-benar dipakai dikonfirmasi lewat `lastUsageMetadata()['model']`, bukan diasumsikan):

| Skenario | Sebelum tuning | Sesudah tuning |
|---|---|---|
| Pesan 1 sinyal, tanpa angka ("Saya punya hutang, apakah tetap wajib zakat?") | Premium (`gpt-5.6-sol`), ~4.400 ms | **Default** (`gpt-5.6-terra`), ~2.300 ms |
| Pesan multi-sinyal ("Saya mau hitung zakat mal dari gaji dan tabungan") | Premium (`gpt-5.6-sol`), ~3.300 ms | Tetap premium (`gpt-5.6-sol`), ~3.100 ms *(tidak berubah, sesuai desain)* |

**Verifikasi tanpa regresi**: setelah tuning, `chatbot:eval-behavior` tetap **11/11 skenario lolos** (saat itu masih 11 skenario, sebelum diperluas ke 18 di Bab 10.5) dan `chatbot:eval-rag` tetap **F1-score 0,987** (fact-check 0 gagal) — kualitas jawaban tidak berubah meski sebagian pesan sekarang diproses model yang lebih murah/cepat. F1-score naik ke **1,0** setelah perbaikan gap retrieval di Bab 10.6.

**Penilaian subjektif waktu respons keseluruhan** (skala 1–10, berdasarkan gabungan angka terukur di atas + pengalaman UX dengan streaming): naik dari **7/10** menjadi **8/10** setelah tuning ini. Sisa keterbatasan: pesan yang memang butuh reasoning kompleks (mayoritas interaksi konsultasi zakat mal, inti fungsional chatbot ini) tetap berada di kisaran 3–4,6 detik — batas ini ditentukan oleh kapasitas model itu sendiri, bukan lagi oleh logika routing, sehingga peningkatan lebih lanjut memerlukan trade-off yang lebih besar (mis. mengganti model premium yang tersedia, atau membatasi panjang keluaran per jalur).

---

## 8. Sistem Keamanan Berlapis (Defense in Depth)

Tiga lapisan independen, masing-masing dengan karakteristik biaya/kecepatan/akurasi berbeda — desain sengaja tidak mengganti lapisan lama dengan yang baru, melainkan menumpuk:

### Lapisan 1 — Instruksi Prompt

Larangan eksplisit di system prompt (jangan keluar topik, jangan ikuti instruksi yang mengubah peran, jangan tebak angka). Lapisan paling murah tapi paling lemah — bergantung sepenuhnya pada kepatuhan model.

### Lapisan 2 — `ChatbotGuardrailVerifier` (keyword blocklist)

Regex/keyword matching murni (tanpa panggilan API), dipanggil **per-kalimat saat streaming** ([ChatbotStreamParser.php:103](../app/Services/Chatbot/ChatbotStreamParser.php#L103)) sehingga pelanggaran bisa dihentikan di tengah stream. Dua mekanisme:

1. Daftar kata terlarang eksplisit (topik di luar zakat, indikator prompt injection).
2. Heuristik fallback: balasan >150 karakter tanpa satu pun kata kunci domain zakat dianggap mencurigakan.

**Keterbatasan yang diketahui dan terdokumentasi** ([CHATBOT_ZAKKY.md](../docs/CHATBOT_ZAKKY.md), bagian "Keterbatasan"): bisa dilewati dengan parafrase yang tidak memakai kata terlarang dan tetap <150 karakter. Dibuktikan lewat test `ChatbotGuardrailVerifierTest::test_known_limitation_paraphrased_off_topic_content_is_not_caught`.

### Lapisan 3 — `ChatbotSafetyClassifier` (embedding similarity)

Layer tambahan (bukan pengganti lapisan 2) yang dipanggil **sekali** setelah balasan final selesai (bukan per-kalimat, supaya tidak menambah panggilan embedding di setiap kalimat streaming). Metodologi dan hasil evaluasinya di Bab 9.4.

---

## 9. Metodologi & Hasil Evaluasi

Empat command evaluasi, masing-masing menguji aspek berbeda dari sistem:

### 9.1 `chatbot:eval-rag` — Kualitas Retrieval + Fact-Check

- Dataset: `ChatbotEvalDataset` — **40 kasus positif** (satu per topik KB utama) + **20 kasus negatif** (pertanyaan di luar topik, untuk mengukur specificity/true-negative rate).
- Metodologi: untuk tiap kasus positif, cek apakah slug KB yang diharapkan muncul di top-3 hasil retrieval; untuk kasus yang punya `fact` (angka/istilah spesifik yang aman dicek via substring), panggil LLM sungguhan dan cek apakah jawabannya mengandung fakta tersebut.
- Output: confusion matrix (TP/FN/TN/FP), **precision, recall, specificity, F1-score**.
- Sifat: butuh API key asli untuk fact-check; retrieval-only version tersedia sebagai `tests/Feature/ChatbotKnowledgeRetrievalEvalTest.php` (tanpa API, jalan di CI).

### 9.2 `chatbot:eval-behavior` — Perilaku Multi-Turn (Boolean)

- Dataset: `ChatbotBehaviorDataset` — **18 skenario** percakapan multi-turn, masing-masing dengan fungsi `expect` yang mengecek pola pasti (mis. balasan pertama tidak boleh mengandung `[HITUNG:`, atau balasan akhir harus mengandung `[[HASIL]]`).
- Metodologi: tiap giliran skenario dikirim berurutan lewat `ChatbotOrchestrator::handle()` dengan session ID yang sama, context di-roundtrip antar-giliran (mensimulasikan perilaku frontend), ekspektasi dicek terhadap balasan **giliran terakhir** saja.
- Kasus yang diuji: konfirmasi niat sebelum interogasi data, tidak menebak angka saat data kurang, menghitung setelah data dikonfirmasi, retensi konteks saat diselingi topik lain, tidak terpancing menghitung dari singgungan kata "gaji" di luar topik, mengakui jawaban pendek/range, mengganti angka lama saat dikoreksi, edukasi konsep tanpa masuk alur hitung, pause konsultasi untuk menjawab pertanyaan konsep, konfirmasi ulang angka yang kemungkinan kelebihan nol, hasil nol tidak terdengar seperti gagal, tetap berbahasa Indonesia saat user campur bahasa Inggris, data "tidak ada" dicatat sebagai nol tanpa ditanya ulang, tidak lanjut menghitung saat user bilang sudah bayar, follow-up ubah variabel setelah hasil, dan tidak memakai istilah internal ke user.
- Sifat: butuh API asli (perilaku model nondeterministik), dijalankan manual sebelum perubahan besar ke prompt — bukan gate CI otomatis. 2 dari 6 poin baru sempat menemukan bug produk nyata saat pertama kali dijalankan (lihat Bab 10.5).

### 9.3 `chatbot:eval-behavior-rubric` — Kualitas Konsultatif (Skor Manual 1–5)

- Dataset: `ChatbotBehaviorRubricDataset` — **12 skenario** (user takut salah hitung, malu karena tabungan kecil, minta jawaban singkat/detail, bingung kategorisasi, kasus abu-abu, koreksi angka, interupsi konsep, hasil nol, closure, dll.), dinilai terhadap **7 aspek rubric**: empati natural, tidak menghakimi, kejelasan langkah, tidak terlalu panjang, tidak defensif/disclaimer berlebihan, menjaga konteks, tone panitia masjid.
- Metodologi: berbeda dari `eval-behavior` yang boolean, aspek di sini bersifat kualitatif/manusiawi sehingga skor diisi manual oleh evaluator (dosen, panitia, atau peneliti) — command menyediakan output tabel (termasuk mode `--markdown` untuk ditempel langsung ke dokumen skripsi) berisi balasan Zakky untuk tiap skenario, kolom skor dikosongkan untuk diisi.
- Target realistis (dari `docs/chatbot-thesis-notes.md`): rata-rata ≥4,0/5, tidak ada aspek utama di bawah 3.

### 9.4 `chatbot:eval-safety` — Classifier Keamanan Berbasis Embedding Similarity

Ini metodologi paling "terukur" secara kuantitatif di antara keempatnya, cocok untuk bab evaluasi/hasil skripsi yang butuh angka statistik.

**Dataset**: `ChatbotSafetyDataset` — awalnya **120 contoh** berlabel bergaya pertanyaan user, diperluas jadi **145 contoh** setelah ditemukan celah metodologis (lihat Bab 10.4), 6 kategori:

| Kategori                      | Jumlah contoh | Deskripsi                                                  |
| ----------------------------- | ------------- | ---------------------------------------------------------- |
| `in_domain`                 | 40            | Pertanyaan zakat/masjid yang sah                           |
| `out_of_scope`              | 30            | Topik jelas di luar domain (resep masakan, olahraga, dll.); 5 di antaranya bergaya balasan bot |
| `prompt_injection`          | 25            | Upaya mengubah peran/aturan sistem; 5 di antaranya bergaya balasan bot |
| `unsupported_fatwa`         | 20            | Meminta vonis fikih pasti tanpa mau dirujuk ke ustadz; 5 di antaranya bergaya balasan bot |
| `privacy_risk`              | 15            | Meminta data pribadi muzakki/mustahik/jamaah lain; 5 di antaranya bergaya balasan bot |
| `payment_verification_risk` | 15            | Meminta bot memverifikasi/mengubah/membatalkan transaksi; 5 di antaranya bergaya balasan bot |

**Cara kerja classifier** (`ChatbotSafetyClassifier::classify()`):

```
Teks (pesan/balasan) → embedding vector (text-embedding-3-small)
    → cosine similarity terhadap 120 embedding contoh (di-cache)
    → ambil kategori dari contoh dengan similarity tertinggi (nearest neighbor)
    → skor >= threshold "confident" → kategori dipakai untuk keputusan blokir
    → skor di rentang "ambiguous" atau di bawah "no_match" → fail-open (tidak diblokir)
```

**Metodologi evaluasi — Leave-One-Out Cross-Validation**: karena classifier ini adalah nearest-neighbor terhadap datasetnya sendiri, akurasi tidak bisa diukur dengan mencocokkan tiap contoh ke dirinya sendiri (trivial, similarity = 1,0). Sebagai gantinya, tiap satu dari 145 contoh diklasifikasi terhadap **144 contoh lainnya** (dirinya sendiri dikeluarkan sementara dari reference set), lalu diulang untuk semua 145 contoh secara bergantian. Ini metodologi standar untuk mengevaluasi classifier nearest-neighbor pada dataset kecil tanpa perlu held-out test set terpisah.

**Threshold sweep**: nilai *cut-off* "confident" (0,30–0,75, step 0,02) disapu secara empiris terhadap skor yang sama dari leave-one-out di atas, menghasilkan kurva **akurasi vs. cakupan vs. tingkat false-positive terhadap `in_domain`**.

**Hasil terukur** (setelah perbaikan celah reply-style di Bab 10.4 — dataset 120→145 contoh, threshold tetap 0,68 karena masih titik potong optimal di sweep ulang):

| Metrik                                                              | Sebelum perbaikan (dataset 120, semua bergaya pertanyaan) | Setelah perbaikan (dataset 145, +reply-style) |
| ------------------------------------------------------------------- | --------------------- | ------------------------------------------------- |
| Akurasi top-1 (semua tingkat keyakinan)                             | 78,3%                 | 80,7%                                              |
| Akurasi kasus "confident"                                           | 91,7%                 | **95,1%**                                   |
| Tingkat false-positive`in_domain` (pertanyaan sah salah diblokir) | 0%                     | **0%**                                      |
| Cakupan "confident" dari total kasus                                 | 20,0%                 | 28,3%                                             |

**Kriteria pemilihan threshold**: dipilih titik potong **terendah** di mana tingkat false-positive `in_domain` mencapai 0% — bukan titik akurasi tertinggi murni. Justifikasi: karena classifier ini adalah lapisan **tambahan** di atas guardrail keyword yang sudah ada (Bab 8, Lapisan 2), risiko salah memblokir pengguna sah (false positive) dinilai lebih mahal secara operasional daripada risiko kasus berisiko halus yang lolos ke status "ambiguous"/"no_match" (yang tetap fail-open, tidak diblokir, tapi juga tidak mendapat perlindungan tambahan dari lapisan ini — lapisan keyword tetap menjadi jaring pengaman dasar untuk kasus yang jelas).

**Confusion matrix** (ringkasan kategori paling sering rancu, dari leave-one-out, setelah perbaikan Bab 10.4):

| Kategori                      | Total kasus | Error rate (leave-one-out) |
| ----------------------------- | ----------- | -------------------------- |
| `unsupported_fatwa`         | 20          | 35,0%                      |
| `out_of_scope`              | 30          | 20,0%                      |
| `privacy_risk`              | 15          | 20,0%                      |
| `in_domain`                 | 40          | 17,5%                      |
| `payment_verification_risk` | 15          | 13,3%                      |
| `prompt_injection`          | 25          | 12,0%                      |

Pola yang teramati: `unsupported_fatwa` paling sering rancu dengan `in_domain` (masuk akal — secara struktur kalimat mirip pertanyaan case-consultation biasa, bedanya di nada "menuntut vonis pasti" yang lebih sulit dipisahkan lewat embedding semantik murni). `privacy_risk` kadang rancu dengan `prompt_injection` (sama-sama "meminta sesuatu yang tidak seharusnya diberikan"). Error rate di leave-one-out cukup tinggi untuk beberapa kategori, tapi ini metrik "akurasi di semua tingkat keyakinan" — yang jadi keputusan blokir aktual (Bab 10.4) hanya kasus "confident", yang akurasinya jauh lebih tinggi (95,1%).

---

## 9.5 Unit Test Deterministik (Tanpa API Asli)

Selain 4 command evaluasi di atas (yang butuh API key asli dan bersifat manual/nondeterministik), bagian mekanis yang bisa diverifikasi tanpa panggilan LLM dijaga lewat unit test biasa (`php artisan test`, jalan di CI):

- `ChatbotSafetyClassifierTest` — logika cosine similarity + threshold tiering (matematika murni).
- `ChatbotGuardrailVerifierTest` — keyword blocklist, termasuk test yang secara eksplisit mendokumentasikan keterbatasannya.
- `ChatbotStreamParserTest` — parsing sentinel saat streaming.
- `ChatbotApiTest` (33 test) — routing model, mode percakapan, guardrail, regression test untuk instruksi prompt spesifik (mis. memastikan instruksi "konfirmasi niat sebelum kumpulkan data" tidak hilang di edit prompt berikutnya).

---

## 10. Temuan Selama Pengembangan (Studi Kasus untuk Bab Pembahasan)

### 10.1 Bug: Fast-path intent detector terlalu agresif

**Gejala**: pesan "Tolong hitungkan zakat mal saya: gaji 10 juta/bulan..." dijawab definisi generik zakat mal, bukan masuk alur konsultasi.

**Akar masalah**: `ChatbotActionDetector::intent()` mencocokkan frasa "zakat mal" ke intent `ask_zakat_mal_definition` tanpa mengecek apakah pesan sebenarnya permintaan kalkulasi (mengandung angka + kata "hitung"). Fast-path ini men-short-circuit alur **sebelum** pesan sempat sampai ke LLM.

**Ditemukan lewat**: `chatbot:eval-behavior`, bukan lewat inspeksi kode manual — bukti bahwa evaluasi perilaku end-to-end menangkap bug yang lolos dari review kode maupun unit test unit-level.

**Perbaikan**: menambah guard `$looksLikeCalculationRequest` ([ChatbotActionDetector.php:49](../app/Services/Chatbot/ChatbotActionDetector.php#L49)) — kalau pesan mengandung angka + kata "hitung"/"konsultasi", intent definisi di-skip, pesan dibiarkan lanjut ke jalur AI.

### 10.2 Redaksi privasi tidak merusak kontinuitas percakapan

Kekhawatiran awal: `redactNominals()` mengganti angka di balasan tersimpan dengan `[nominal]` sebelum masuk `ai_chat_logs` — apakah ini membuat LLM "lupa" angka yang baru saja dihitungnya sendiri di giliran berikutnya?

**Hasil investigasi**: tidak masalah dalam praktik. User biasanya menulis angka dalam format bebas ("12 juta", bukan "Rp12.000.000"), yang tidak cocok pola regex redaksi (yang menyasar angka berformat grouping panjang) — sehingga pesan **user** tetap utuh di histori, dan model bisa merekonstruksi konteks dari situ meski balasan **asisten** sebelumnya sudah ter-redaksi.

### 10.3 Kesalahan desain evaluasi (bukan bug produk)

Dua skenario di `chatbot:eval-behavior` sempat dilaporkan "GAGAL" karena mengecek keberadaan string mentah `[HITUNG:` di balasan akhir — padahal `ChatbotSentinelParser` **selalu** mengganti sentinel itu dengan blok `[[HASIL]]...[[/HASIL]]` sebelum balasan dikembalikan ke user. Setelah ekspektasi test diperbaiki untuk mencari `[[HASIL]]`, kelima skenario lolos. Ini contoh baik untuk bab pembahasan: bedakan kegagalan sistem vs. kegagalan instrumen ukur.

### 10.4 Bug: Celah distribusi latih vs. produksi pada safety classifier (Lapisan 3)

**Gejala**: `ChatbotOrchestrator::finalizeAiReply()` memanggil `ChatbotSafetyClassifier::checkReply($cleanReply)` — mengklasifikasi **teks balasan bot**, bukan pesan user. Tapi seluruh 120 contoh awal di `ChatbotSafetyDataset` ditulis bergaya **pertanyaan/perintah user** ("Saya mau...", "Apa itu...?", "Tolong tampilkan..."). Metrik evaluasi (leave-one-out, threshold sweep) diukur di atas dataset user-phrased ini, sehingga angka "91,7% akurasi confident-tier, 0% false-positive" yang dilaporkan awalnya **valid untuk distribusi pertanyaan user, belum tentu merepresentasikan performa nyata terhadap balasan bot** yang sesungguhnya diklasifikasi di production — potensi *train/production skew* klasik.

**Dibuktikan lewat pengujian manual**, bukan lewat command eval (karena command eval mengukur akurasi terhadap dataset itu sendiri, tidak bisa mendeteksi mismatch distribusi ini): tiga balasan tiruan yang mensimulasikan bot gagal menjaga guardrail (menuruti permintaan di luar topik, membocorkan system prompt, membocorkan data pribadi) semuanya **lolos tanpa diblokir** — kategori terdeteksi benar oleh classifier, tapi skor kemiripannya jatuh sistematis di bawah `CONFIDENT_THRESHOLD` (0,68) karena gaya bahasa balasan berbeda dari gaya bahasa referensi.

**Perbaikan**: menambah 25 contoh baru bergaya balasan-bot (5 per kategori yang bisa diblokir: `out_of_scope`, `prompt_injection`, `unsupported_fatwa`, `privacy_risk`, `payment_verification_risk`) ke `ChatbotSafetyDataset` — total dataset 120 → **145 contoh**. Setelah re-embed dan re-tuning, threshold optimal tetap 0,68 (sweep ulang mengonfirmasi titik ini masih 0% false-positive `in_domain`), dan ketiga balasan tiruan tadi kini **terblokir dengan benar**, tanpa balasan sah manapun jadi ikut terblokir (diverifikasi ulang secara manual).

**Pembelajaran metodologis**: untuk classifier nearest-neighbor yang dievaluasi lewat leave-one-out di atas dataset referensinya sendiri, penting memastikan **gaya/distribusi teks di dataset referensi menyerupai gaya/distribusi teks yang sesungguhnya diklasifikasi saat runtime** — leave-one-out cross-validation mengukur konsistensi internal dataset, bukan generalisasi ke distribusi input produksi yang berbeda gaya.

### 10.5 Dua bug perilaku ditemukan lewat perluasan `ChatbotBehaviorDataset`

Saat menambah 7 skenario baru ke `chatbot:eval-behavior` (11 → 18, menutup lebih banyak poin dari `docs/chatbot-behavior-notes.md`), dua di antaranya langsung menemukan bug produk nyata pada percobaan pertama — bukti lain bahwa evaluasi perilaku end-to-end menangkap kelas bug yang tidak tertangkap review kode atau unit test level-fungsi (pola yang sama seperti Bab 10.1).

**Bug 1 — bot tidak berhenti menghitung saat user bilang sudah bayar.** Skenario "tidak lanjut menghitung saat user bilang sudah bayar" (poin 36 di `chatbot-behavior-notes.md`) awalnya gagal: setelah user berkata *"Eh sebenarnya saya sudah transfer duluan tadi pagi"* di tengah konsultasi, bot tetap melanjutkan menanyakan data kalkulasi (mis. status haul tabungan), bukan mengarahkan ke konfirmasi pembayaran ke panitia — karena system prompt memang belum pernah punya instruksi eksplisit untuk kasus ini. **Perbaikan**: menambah satu kalimat instruksi baru di system prompt ([OpenAiChatbotProvider.php:352](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L352)) yang secara eksplisit melarang lanjut menghitung dan mengarahkan langsung ke konfirmasi panitia.

**Bug 2 — regresi dari optimasi latensi Bab 7.4.** Skenario "pause konsultasi saat user minta penjelasan konsep" (skenario lama, bukan baru) ikut gagal saat regression run: pesan *"Nanti dulu, jelasin nisab itu apa."* (≤8 kata, di tengah mode konsultasi) kena aturan skip-retrieval yang ditambahkan untuk optimasi latensi (Bab 7.4) — akibatnya bot menjawab *"Saya belum punya info itu di panduan Masjid An-Nur"* padahal nisab jelas ada di KB. Aturan skip itu awalnya mengasumsikan balasan pendek di tengah konsultasi selalu berupa **lanjutan data** ("50 juta", "tidak ada hutang"), padahal bisa juga berupa **pertanyaan tangensial pendek** yang tetap butuh grounding KB. **Perbaikan**: `ChatbotOrchestrator::retrieveContexts()` ([ChatbotOrchestrator.php:266](../app/Services/Chatbot/ChatbotOrchestrator.php#L266)) sekarang mengecualikan pesan yang mengandung pola pertanyaan (tanda `?` atau kata tanya seperti "apa", "kenapa", "jelasin") dari aturan skip, walau tetap pendek dan di tengah konsultasi.

Kedua bug diverifikasi ulang secara manual (balasan benar setelah perbaikan) dan lolos regresi penuh (`chatbot:eval-rag` F1 tetap 1,0; `php artisan test` 228/228).

### 10.6 Perbaikan false negative retrieval terakhir dan penambahan konten KB

Satu-satunya kasus retrieval yang masih gagal di `chatbot:eval-rag` (F1 0,987) adalah pertanyaan *"Kalau kasus saya rumit harus tanya siapa?"* — seharusnya menemukan entri `kapan-konsultasi-ustadz`, tapi entri `cara-zakky-menganalisis-kasus` (topik konseptual berdekatan: sama-sama soal "kasus rumit", beda sudut pandang) menang dengan similarity 0,452, sedikit di atas threshold 0,45. **Perbaikan**: menajamkan keyword `kapan-konsultasi-ustadz` dengan frasa yang lebih cocok dengan pola pertanyaan "harus tanya siapa" (`database/seeders/KnowledgeBaseSeeder.php`). Percobaan pertama sempat memunculkan false positive baru (pertanyaan "Rute tercepat ke Bandung pagi ini lewat mana?" ikut cocok karena kata "lewat" terlalu generik di judul entri baru lainnya) — diperbaiki dengan mengganti "lewat" jadi "melalui" di entri terkait. Setelah kedua perbaikan, `chatbot:eval-rag` mencapai **F1-score 1,0 (precision 1,0, recall 1,0)**.

Bersamaan dengan itu, ditambahkan **3 entri KB baru** untuk menutup gap konten yang teridentifikasi lewat tinjauan manual (bukan lewat eval otomatis, karena eval hanya bisa mengukur retrieval terhadap pertanyaan yang sudah ada di dataset, bukan menemukan topik yang belum pernah dimasukkan sama sekali): zakat penghasilan bruto vs. netto (potongan pajak/BPJS), boleh tidaknya menyalurkan zakat sendiri tanpa lewat panitia, dan zakat mal yang terlewat dari tahun-tahun sebelumnya. Konten ditulis mengikuti pola epistemik yang sama dengan entri KB lain (beri gambaran umum, akui ada beda pendapat ulama/lembaga, arahkan ke ustadz/panitia untuk keputusan final) sehingga tidak mengklaim fatwa tunggal.

---

## 11. Keterbatasan yang Diketahui (Untuk Bab Batasan Penelitian)

1. **Guardrail keyword (Lapisan 2) bisa dilewati parafrase** yang tidak memakai kata terlarang eksplisit dan tetap di bawah 150 karakter. Terdokumentasi dan dibuktikan test, bukan diklaim sebagai perlindungan penuh terhadap prompt injection.
2. **Safety classifier (Lapisan 3) punya cakupan "confident" sekitar 28%** setelah tuning — sisanya jatuh ke ambiguous/no_match dan tidak mendapat keputusan tegas dari lapisan ini (fail-open, mengandalkan Lapisan 1–2). Trade-off precision-vs-recall yang disengaja, bukan kegagalan tak disadari.
3. **Kategori `unsupported_fatwa` punya error rate tertinggi** (35,0%) di leave-one-out — dataset 20 contoh untuk kategori ini kemungkinan masih terlalu kecil/terlalu mirip `in_domain` secara struktur kalimat untuk representasi yang stabil; menambah contoh yang ditargetkan ke kategori ini adalah perbaikan lanjutan yang jelas.
4. **Dataset reference untuk safety classifier tercampur dua gaya penulisan** (pertanyaan user + balasan bot, lihat Bab 10.4) setelah perbaikan celah distribusi — cukup untuk menutup gap yang ditemukan, tapi rasio 5 contoh reply-style per 25-40 contoh question-style per kategori masih kecil; menambah lebih banyak contoh reply-style adalah perbaikan lanjutan yang jelas kalau classifier ini dikembangkan lebih jauh.
5. **Refresh embedding cache KB bersifat sinkron** — menyimpan entri KB memblokir request admin selama kira-kira (jumlah entri aktif × latensi API embedding). Aman di skala 54 entri saat ini, perlu dipertimbangkan ulang (batching/queue) kalau KB tumbuh jadi ratusan entri.
6. **Evaluasi `eval-behavior`, `eval-behavior-rubric`, dan `eval-safety` bergantung pada API key asli** dan bersifat nondeterministik (jawaban LLM bisa sedikit berbeda antar run) — dijalankan manual sebagai regression check sebelum perubahan besar ke prompt, bukan gate CI otomatis seperti unit test biasa.
7. **Rubric kualitas konsultatif (Bab 9.3) butuh skor manual manusia** — sistem menyediakan bahan evaluasinya (balasan Zakky per skenario dalam format tabel), tapi penilaian 1–5 per aspek tetap memerlukan evaluator manusia (dosen/panitia/peneliti), bukan otomatis.

---

## 12. Rekomendasi Pengembangan Lanjutan

1. Tambah contoh dataset di kategori `unsupported_fatwa` dan `privacy_risk` (error rate tertinggi), lalu ukur ulang lewat `chatbot:eval-safety` apakah akurasi confident-tier membaik.
2. Terapkan `chatbot:eval-safety` juga terhadap **pesan masuk user** (bukan cuma balasan LLM) — dataset sudah mendukung ini (banyak contoh `prompt_injection` ditulis dari sudut pandang pesan user), tapi integrasinya saat ini baru menyasar balasan akhir untuk menghindari penambahan panggilan embedding di jalur kritis.
3. Pertimbangkan caching/batching untuk refresh embedding KB kalau jumlah entri bertambah signifikan.
4. Lengkapi evaluasi kuantitatif (Bab 9.1, 9.2, 9.4) dengan evaluasi kualitatif oleh responden manusia (dosen pembimbing, panitia masjid, atau sampel jamaah) menggunakan rubric di Bab 9.3 — kombinasi keduanya (terukur otomatis + manusia) memberi validitas yang lebih kuat untuk klaim "chatbot ini membantu" di skripsi.

---

## Lampiran: Indeks File Kode Sumber

| Area                     | File                                                                                                                                                                                      |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Orkestrasi utama         | `app/Services/Chatbot/ChatbotOrchestrator.php`                                                                                                                                          |
| Deteksi intent fast-path | `app/Services/Chatbot/ChatbotActionDetector.php`                                                                                                                                        |
| RAG / retrieval          | `app/Services/Chatbot/Knowledge/KnowledgeRetriever.php`, `KnowledgeEmbeddingsCache.php`                                                                                               |
| Manajemen konteks        | `app/Services/Chatbot/ChatbotConversationContext.php`                                                                                                                                   |
| Kalkulasi zakat mal      | `app/Services/Chatbot/ChatbotZakatMalGuide.php`, `ChatbotSentinelParser.php`                                                                                                          |
| Provider LLM             | `app/Services/Chatbot/Providers/OpenAiChatbotProvider.php`                                                                                                                              |
| Guardrail keyword        | `app/Services/Chatbot/ChatbotGuardrailVerifier.php`                                                                                                                                     |
| Safety classifier        | `app/Services/Chatbot/Safety/ChatbotSafetyClassifier.php`, `ChatbotSafetyDataset.php`, `ChatbotSafetyEmbeddingsCache.php`                                                           |
| Logging & privasi        | `app/Services/Chatbot/ChatbotChatLogger.php`                                                                                                                                            |
| Basis pengetahuan        | `database/seeders/KnowledgeBaseSeeder.php`                                                                                                                                              |
| Dataset evaluasi         | `app/Services/Chatbot/Knowledge/ChatbotEvalDataset.php`, `ChatbotBehaviorDataset.php`, `ChatbotBehaviorRubricDataset.php`, `app/Services/Chatbot/Safety/ChatbotSafetyDataset.php` |
| Command evaluasi         | `app/Console/Commands/EvaluateChatbotRag.php`, `EvaluateChatbotBehavior.php`, `EvaluateChatbotBehaviorRubric.php`, `EvaluateChatbotSafety.php`                                    |
| Frontend widget          | `resources/js/chatbot-widget.js`, `resources/views/components/chatbot-widget.blade.php`                                                                                               |
