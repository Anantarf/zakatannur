# Dokumentasi Teknis Chatbot Zakky — Acuan Pembahasan Skripsi

Dokumen ini merangkum arsitektur, metodologi, dan hasil evaluasi terukur dari chatbot Zakky (asisten zakat Masjid An-Nur), disusun sebagai bahan mentah untuk bab pembahasan/hasil skripsi. Angka evaluasi di sini berasal dari pengujian pada lingkungan proyek dengan jumlah sampel dan konfigurasi yang dicatat per bagian; beberapa bagian adalah snapshot historis sebelum tuning lanjutan, sehingga angka terkini harus selalu diambil dari kode aktual dan output command evaluasi terbaru.

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

Provider LLM: OpenAI-compatible API (mendukung model apa pun yang expose endpoint `/chat/completions` bergaya OpenAI — di lingkungan ini dikonfigurasi ke tiga tier/peran model berbeda, lihat Bab 7).

---

## 2. Arsitektur Sistem

```
ChatbotController (HTTP endpoint: /api/chatbot/message, /api/chatbot/stream)
    ↓
ChatbotOrchestrator                              [app/Services/Chatbot/ChatbotOrchestrator.php]
    ├─ 1. getQuickResponse()  → fast-path rule-based (tanpa LLM)
    │     └─ ChatbotActionDetector                [ChatbotActionDetector.php]      (intent classification, keyword-based)
    │     └─ ChatbotCalcuelatorService              (kalkulasi fitrah/fidyah deterministik)
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

1. Prompt sistem melarang LLM menghitung sendiri dan mewajibkan output tag `[HITUNG:{"income_monthly":...,"savings":...,"gold_gram":...,"debt":...}]` begitu data cukup ([OpenAiChatbotProvider.php:382](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L382)).
2. `ChatbotSentinelParser` mendeteksi tag ini, mengekstrak JSON variabelnya, dan memanggil `ChatbotZakatMalGuide::calculate()` — fungsi PHP murni yang menghitung zakat penghasilan (basis penghasilan bruto bulanan × 12, terpisah dari tabungan — lihat Bab 6.2) dan zakat tabungan/emas (basis harta simpanan saat ini dikurangi hutang) secara independen terhadap nisab.
3. Hasil kalkulasi disisipkan kembali ke balasan sebagai blok `[[HASIL]]...[[/HASIL]]`, yang di-render frontend sebagai kartu hasil terpisah (lihat `resources/js/chatbot-widget.js`).
4. Bagian penghasilan dan bagian tabungan/emas masing-masing hanya dirender kalau field terkait **benar-benar ada** di JSON `[HITUNG:{...}]` (`isset()`, bukan default ke 0) — lihat Bab 10.7 untuk bug yang mendasari aturan ini.

Prinsip metodologi (didokumentasikan di KB entri `catatan-metodologi-zakat`): penghasilan tahunan **tidak** dijumlah mentah dengan saldo tabungan sebagai satu basis, karena saldo tabungan biasanya sudah mencerminkan penghasilan yang diterima dan dibelanjakan sepanjang tahun — menjumlahkannya akan menghitung penghasilan yang sama dua kali.

### 6.1 Sumber Angka Nishab

Nishab **tidak di-hardcode** sebagai satu angka tetap di kode chatbot, melainkan hasil kali dua variabel yang bisa diatur admin per periode zakat: `nishab = nishab_gold_gram × gold_price_per_gram` ([ChatbotZakatMalGuide.php:23](../app/Services/Chatbot/ChatbotZakatMalGuide.php#L23)). Kedua variabel diambil lewat `AnnualZakatDefaultsResolver::resolve()` ([AnnualZakatDefaultsResolver.php](../app/Services/Transactions/AnnualZakatDefaultsResolver.php)) dari `ZakatPeriod` aktif tahun berjalan, diisi lewat form **Pengaturan Periode** (`nishab_gold_gram`, `gold_price_per_gram`).

**Nilai default**: 85 gram emas × Rp1.078.609/gram ≈ **Rp91.681.765/tahun** (target: Rp91.681.728/tahun — lihat catatan presisi di bawah), mengikuti nishab zakat penghasilan (profesi) per **SK Ketua BAZNAS Nomor 15 Tahun 2026** — standar nominal ini sendiri adalah konversi moderat dari nilai 85 gram emas yang dievaluasi berkala oleh BAZNAS, bukan angka yang ditetapkan sistem secara independen. Default lama (Rp900.000/gram, nishab ≈Rp76,5 juta/tahun) diganti ke nilai ini di [config/zakat.php](../config/zakat.php) (`annual_defaults.gold_price_per_gram`) karena berselisih ~17% dari acuan resmi terbaru.

Nilai ini hanya **default awal** saat sebuah periode dibuat ([ZakatPeriodResolver::ensureForYear()](../app/Services/Periods/ZakatPeriodResolver.php), [createNextForYear()](../app/Services/Periods/ZakatPeriodResolver.php)) — periode baru mewarisi angka dari periode sebelumnya (bukan reset ke default), dan admin bisa mengubahnya kapan pun lewat Pengaturan Periode untuk mengikuti evaluasi nishab BAZNAS berikutnya. Karena harga emas acuan tidak ditarik dari API real-time (lihat Bab 11), akurasinya bergantung pada kedisiplinan admin memperbarui angka ini secara berkala.

Catatan presisi: karena `gold_price_per_gram` disimpan sebagai bilangan bulat rupiah/gram, nilai Rp1.078.609 adalah pembulatan dari Rp91.681.728 ÷ 85 gram (Rp1.078.608,56...) — nishab tahunan hasil kali baliknya jadi **Rp91.681.765** (selisih +Rp37 dari angka SK BAZNAS, akibat pembulatan, bukan kesalahan input).

### 6.2 Basis Zakat Penghasilan: Bruto, Bukan Netto

Zakat penghasilan dihitung dari **penghasilan bruto** (gaji pokok + tunjangan, sebelum potongan pajak/BPJS/kebutuhan pokok) — `$incomeAnnual = $incomeMonthly * 12` ([ChatbotZakatMalGuide.php:22-25](../app/Services/Chatbot/ChatbotZakatMalGuide.php#L22-L25)), mengikuti metodologi **SK Ketua BAZNAS Nomor 15 Tahun 2026**.

**Bukan pilihan awal yang disengaja** — versi sebelumnya diam-diam menghitung dari penghasilan **netto** (`(income_monthly - expenses_monthly) × 12`), sementara KB teks (`catatan-metodologi-zakat`, `zakat-penghasilan-potongan-pajak-bpjs`) sudah lebih dulu mengutip BAZNAS sebagai `source_label` dan menyajikan bruto-vs-netto sebagai "perdebatan terbuka, silakan konfirmasi ke panitia" — padahal kalkulator sendiri sudah menjatuhkan pilihan (netto) tanpa pernah menyatakannya secara eksplisit ke user. Ditemukan saat membandingkan detail perhitungan BAZNAS (nisab Rp7.640.144/bulan, basis bruto, kadar 2,5%) dengan kode yang berjalan.

**Perbaikan**: `expenses_monthly` dihapus total dari model data — dari skema `[HITUNG:{...}]` di system prompt, dari validasi plausibilitas dan baris ringkasan input di `ChatbotSentinelParser`, dan dari kalkulasi di `ChatbotZakatMalGuide`. LLM tidak lagi diminta mengumpulkan "pengeluaran rutin bulanan" untuk zakat penghasilan. Teks KB diperbarui menyatakan langsung bahwa Masjid An-Nur memakai pendekatan bruto, bukan lagi menyerahkannya sebagai pertanyaan terbuka.

**Yang tetap sama**: kadar 2,5%, cara nisab dihitung (Bab 6.1), dan pemisahan penilaian zakat penghasilan vs. zakat tabungan/emas (tidak dipooling — Bab 6). Hutang jatuh tempo tetap hanya memengaruhi basis zakat tabungan/emas, bukan zakat penghasilan — opsi BAZNAS untuk mengurangkan hutang mendesak dari penghasilan sengaja **tidak** diimplementasikan otomatis karena disebut "sebagian ulama membolehkan" (bukan konsensus), diarahkan ke konfirmasi manual panitia/ustadz alih-alih menambah cabang logika untuk kasus yang belum tentu berlaku umum.

**Dampak numerik** (contoh dari `tests/Feature/ChatbotApiTest.php::test_chatbot_computes_zakat_mal_from_hitung_sentinel_and_shows_inputs_used`): penghasilan Rp10.000.000/bulan dengan pengeluaran rutin yang dulu ikut disebut user (Rp2.000.000/bulan) — versi lama menghasilkan penghasilan bersih tahunan Rp96.000.000 (zakat Rp2.400.000/tahun); versi bruto sekarang mengabaikan pengeluaran sama sekali, penghasilan tahunan penuh Rp120.000.000 (zakat Rp3.000.000/tahun). Regresi penuh tetap bersih: `php artisan test` 228/228.

---

## 7. Optimasi Model & Latensi

### 7.1 Routing 3 Tier Model

`OpenAiChatbotProvider::selectModel()` ([OpenAiChatbotProvider.php:265](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L265)) memilih salah satu dari 3 tier model berdasarkan kompleksitas pesan (sejak Bab 20.1, setiap keputusan routing juga dicatat ke `ChatbotDiagnostics` — `model_used`, `route_reason`, `message_length`, `conversation_turn_count`). Tier atau perannya adalah `fast`, `default`, dan `premium`; nama model aktual mengikuti konfigurasi runtime (`OPENAI_FAST_MODEL`, `OPENAI_CHAT_MODEL`, `OPENAI_PREMIUM_MODEL` di `.env`/`.env.example`, dengan fallback di `config/chatbot.php` dan `config/services.php`).

| Tingkat           | Kapan dipakai                                                                                                                                                                                                                                                         | Contoh trigger                                            |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| **Premium** | Pesan punya**≥2 sinyal kompleksitas berbeda** (hitung, zakat mal, nisab, haul, emas, tabungan, hutang, aset, penghasilan, gaji, investasi, saham, usaha, warisan, konsultasi), atau 1 sinyal + ada angka eksplisit, atau konteks ≥3 entri, atau pesan >350 karakter | "Saya mau hitung zakat mal, gaji 10 juta..." (4 sinyal)   |
| **Fast**    | Pesan pendek (≤6 kata) tanpa konteks, atau cocok pola sapaan/FAQ singkat                                                                                                                                                                                             | "Halo", "jadwal zakat fitrah?"                            |
| **Default** | Sisanya, termasuk pesan dengan**hanya 1 sinyal kompleksitas** tanpa angka                                                                                                                                                                                       | "Saya punya hutang, apakah tetap wajib zakat?" (1 sinyal) |

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

**Fix**: `needsPremiumModel()` diubah untuk mensyaratkan **≥2 sinyal kompleksitas berbeda**, atau 1 sinyal dipasangkan dengan angka eksplisit ("emas 100 gram"), sebelum menaikkan ke tier premium ([OpenAiChatbotProvider.php:290](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L290)).

**Bug yang ditemukan saat verifikasi (bukan cacat desain, tapi pelajaran metodologis penting)**: implementasi pertama dari fix ini tidak langsung berfungsi — pesan uji "Saya punya hutang, apakah tetap wajib zakat?" (seharusnya 1 sinyal) tetap ter-route ke premium. Investigasi menunjukkan kata kunci `'utang'` secara substring **tercakup di dalam** `'hutang'` (h-**utang**), sehingga satu kata "hutang" di pesan dihitung sebagai 2 sinyal berbeda, bukan 1 — meniadakan efek threshold ≥2 sepenuhnya untuk kasus itu. Diperbaiki dengan menghapus `'utang'` dari daftar (sudah tercakup `'hutang'`) dan `'perhitungan'` (sudah tercakup `'hitung'`) — dua pasangan kata kunci yang saling ber-substring dalam daftar 18 kata aslinya.

**Ini contoh baik untuk narasi metodologi skripsi**: perubahan logika sekecil apa pun pada sistem berbasis pencocokan kata kunci perlu diverifikasi dengan pengukuran langsung (bukan cuma dibaca kodenya), karena interaksi antar-kata kunci (substring overlap) tidak selalu terlihat dari inspeksi manual daftar kata.

**Hasil terukur** (rata-rata 3 sampel per kondisi, model yang benar-benar dipakai dikonfirmasi lewat `lastUsageMetadata()['model']`, bukan diasumsikan):

| Skenario                                                                     | Sebelum tuning                       | Sesudah tuning                                                                |
| ---------------------------------------------------------------------------- | ------------------------------------ | ----------------------------------------------------------------------------- |
| Pesan 1 sinyal, tanpa angka ("Saya punya hutang, apakah tetap wajib zakat?") | Premium (`gpt-5.6-sol`), ~4.400 ms | **Default** (`gpt-5.6-terra`), ~2.300 ms                              |
| Pesan multi-sinyal ("Saya mau hitung zakat mal dari gaji dan tabungan")      | Premium (`gpt-5.6-sol`), ~3.300 ms | Tetap premium (`gpt-5.6-sol`), ~3.100 ms *(tidak berubah, sesuai desain)* |

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

**Celah yang ditemukan dan diperbaiki — kebocoran system prompt tidak tertangkap heuristik #2.** Heuristik fallback (>150 karakter tanpa kata kunci domain) diasumsikan menangkap balasan yang "melantur". Tapi kalau LLM benar-benar dijebak mengulang instruksi sistemnya sendiri secara verbatim, balasan itu justru **padat** kata kunci domain (system prompt menyebut "zakat", "An-Nur" berkali-kali) — sehingga heuristik #2 tidak pernah terpicu, dan pertahanan satu-satunya jatuh sepenuhnya ke Lapisan 3 yang probabilistik dan cakupan "confident"-nya cuma ~28% (Bab 9.4) serta fail-open kalau API embedding tidak tersedia. Diperbaiki dengan menambah beberapa frasa khas yang diambil verbatim dari system prompt Zakky sendiri (mis. "asisten digital zakat an-nur", "jangan pernah menghitung nominal zakat mal sendiri") ke daftar kata terlarang Lapisan 2 ([ChatbotGuardrailVerifier.php:28-41](../app/Services/Chatbot/ChatbotGuardrailVerifier.php#L28-L41)) — presisi tinggi karena balasan normal ke pertanyaan zakat tidak akan pernah mengucapkan frasa-frasa itu kembali ke user. Diuji lewat kasus baru di `blockedKeywordCasesProvider()`.

### Lapisan 3 — `ChatbotSafetyClassifier` (embedding similarity)

Layer tambahan (bukan pengganti lapisan 2) yang dipanggil **sekali** setelah balasan final selesai (bukan per-kalimat, supaya tidak menambah panggilan embedding di setiap kalimat streaming). Metodologi awal dan hasil evaluasi baseline di Bab 9.4; algoritma dan angka **terkini** (k-NN berbobot, threshold 0,66, cakupan "confident" 7,5%) ada di Bab 20.2 — perlakukan Bab 9.4 sebagai snapshot metodologi, bukan angka final. Observability lintas-lapisan (termasuk instrumentasi Lapisan 2 & 3) dibahas terpisah di Bab 13.

---

## 9. Metodologi & Hasil Evaluasi

Empat command evaluasi, masing-masing menguji aspek berbeda dari sistem:

### 9.1 `chatbot:eval-rag` — Kualitas Retrieval + Fact-Check

- Dataset: `ChatbotEvalDataset` — **41 kasus positif** (satu per topik KB utama/regression guard) + **20 kasus negatif** (pertanyaan di luar topik, untuk mengukur specificity/true-negative rate).
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

> **Catatan pembaruan**: bagian ini adalah snapshot metodologi & hasil pada saat dataset masih 145 contoh dan algoritma masih 1-nearest-neighbor murni — dipertahankan apa adanya sebagai jejak historis (konsisten dengan gaya "temuan per waktu" di Bab 10). Dataset bertambah jadi 161 contoh di **Bab 18**, dan algoritmanya berubah jadi k-NN berbobot dengan threshold re-tuning di **Bab 20.2** — angka akurasi/error rate/cakupan **terkini** ada di sana, bukan di tabel bawah ini. Metodologi leave-one-out dan threshold sweep yang dijelaskan di sini tetap berlaku tanpa perubahan.

Ini metodologi paling "terukur" secara kuantitatif di antara keempatnya, cocok untuk bab evaluasi/hasil skripsi yang butuh angka statistik.

**Dataset historis bagian ini**: `ChatbotSafetyDataset` — awalnya **120 contoh** berlabel bergaya pertanyaan user, diperluas jadi **145 contoh** setelah ditemukan celah metodologis (lihat Bab 10.4), 6 kategori. Dataset aktual sekarang berjumlah **161 contoh** setelah perluasan Bab 18; angka hasil terkini ada di Bab 20.2 dan output terbaru `php artisan chatbot:eval-safety`.

| Kategori                      | Jumlah contoh | Deskripsi                                                                                      |
| ----------------------------- | ------------- | ---------------------------------------------------------------------------------------------- |
| `in_domain`                 | 40            | Pertanyaan zakat/masjid yang sah                                                               |
| `out_of_scope`              | 30            | Topik jelas di luar domain (resep masakan, olahraga, dll.); 5 di antaranya bergaya balasan bot |
| `prompt_injection`          | 25            | Upaya mengubah peran/aturan sistem; 5 di antaranya bergaya balasan bot                         |
| `unsupported_fatwa`         | 20            | Meminta vonis fikih pasti tanpa mau dirujuk ke ustadz; 5 di antaranya bergaya balasan bot      |
| `privacy_risk`              | 15            | Meminta data pribadi muzakki/mustahik/jamaah lain; 5 di antaranya bergaya balasan bot          |
| `payment_verification_risk` | 15            | Meminta bot memverifikasi/mengubah/membatalkan transaksi; 5 di antaranya bergaya balasan bot   |

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
| ------------------------------------------------------------------- | --------------------------------------------------------- | --------------------------------------------- |
| Akurasi top-1 (semua tingkat keyakinan)                             | 78,3%                                                     | 80,7%                                         |
| Akurasi kasus "confident"                                           | 91,7%                                                     | **95,1%**                               |
| Tingkat false-positive`in_domain` (pertanyaan sah salah diblokir) | 0%                                                        | **0%**                                  |
| Cakupan "confident" dari total kasus                                | 20,0%                                                     | 28,3%                                         |

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
- `ChatbotSentinelParserTest` — parsing sentinel `[HITUNG:...]` di luar streaming: satu tag, banyak tag sekaligus (Bab 10.9), dan kasus data tidak cukup untuk dinilai (Bab 10.8).
- `ChatbotActionDetectorTest` — pembajakan fast-path oleh kata kunci generik (Bab 10.13–10.15).
- `ChatbotConversationContextTest` — deteksi mode konsultasi zakat mal, termasuk regresi bug "angka apa pun" (Bab 10.18).
- `ChatbotDiagnosticsSummaryTest` — command ringkasan log diagnostik per layer (Bab 13).
- `ChatbotApiTest` (41 test) — routing model, mode percakapan, guardrail, regression test untuk instruksi prompt spesifik (mis. memastikan instruksi "konfirmasi niat sebelum kumpulkan data" tidak hilang di edit prompt berikutnya), plus verifikasi log diagnostik per-layer (Bab 13).

---

## 10. Temuan Selama Pengembangan (Studi Kasus untuk Bab Pembahasan)

### 10.1 Bug: Fast-path intent detector terlalu agresif

**Gejala**: pesan "Tolong hitungkan zakat mal saya: gaji 10 juta/bulan..." dijawab definisi generik zakat mal, bukan masuk alur konsultasi.

**Akar masalah**: `ChatbotActionDetector::intent()` mencocokkan frasa "zakat mal" ke intent `ask_zakat_mal_definition` tanpa mengecek apakah pesan sebenarnya permintaan kalkulasi (mengandung angka + kata "hitung"). Fast-path ini men-short-circuit alur **sebelum** pesan sempat sampai ke LLM.

**Ditemukan lewat**: `chatbot:eval-behavior`, bukan lewat inspeksi kode manual — bukti bahwa evaluasi perilaku end-to-end menangkap bug yang lolos dari review kode maupun unit test unit-level.

**Perbaikan**: menambah guard `$looksLikeCalculationRequest` ([ChatbotActionDetector.php:49](../app/Services/Chatbot/ChatbotActionDetector.php#L49)) — kalau pesan mengandung angka + kata "hitung"/"konsultasi", intent definisi di-skip, pesan dibiarkan lanjut ke jalur AI.

### 10.2 Redaksi privasi tidak merusak kontinuitas percakapan — **koreksi lihat Bab 10.19, kesimpulan ini terbukti tidak lengkap**

Kekhawatiran awal: `redactNominals()` mengganti angka di balasan tersimpan dengan `[nominal]` sebelum masuk `ai_chat_logs` — apakah ini membuat LLM "lupa" angka yang baru saja dihitungnya sendiri di giliran berikutnya?

**Hasil investigasi (awal, ternyata tidak lengkap)**: tidak masalah dalam praktik. User biasanya menulis angka dalam format bebas ("12 juta", bukan "Rp12.000.000"), yang tidak cocok pola regex redaksi (yang menyasar angka berformat grouping panjang) — sehingga pesan **user** tetap utuh di histori, dan model bisa merekonstruksi konteks dari situ meski balasan **asisten** sebelumnya sudah ter-redaksi.

Investigasi ini tidak menguji kasus user menulis angka dalam **format Rupiah baku dengan titik ribuan** (mis. "Rp7.500.000") — format yang justru sangat umum, dan persis yang dipakai Zakky sendiri di setiap balasannya. Bug nyata yang muncul dari celah ini didokumentasikan di Bab 10.19.

### 10.3 Kesalahan desain evaluasi (bukan bug produk)

Dua skenario di `chatbot:eval-behavior` sempat dilaporkan "GAGAL" karena mengecek keberadaan string mentah `[HITUNG:` di balasan akhir — padahal `ChatbotSentinelParser` **selalu** mengganti sentinel itu dengan blok `[[HASIL]]...[[/HASIL]]` sebelum balasan dikembalikan ke user. Setelah ekspektasi test diperbaiki untuk mencari `[[HASIL]]`, kelima skenario lolos. Ini contoh baik untuk bab pembahasan: bedakan kegagalan sistem vs. kegagalan instrumen ukur.

### 10.4 Bug: Celah distribusi latih vs. produksi pada safety classifier (Lapisan 3)

**Gejala**: `ChatbotOrchestrator::finalizeAiReply()` memanggil `ChatbotSafetyClassifier::checkReply($cleanReply)` — mengklasifikasi **teks balasan bot**, bukan pesan user. Tapi seluruh 120 contoh awal di `ChatbotSafetyDataset` ditulis bergaya **pertanyaan/perintah user** ("Saya mau...", "Apa itu...?", "Tolong tampilkan..."). Metrik evaluasi (leave-one-out, threshold sweep) diukur di atas dataset user-phrased ini, sehingga angka "91,7% akurasi confident-tier, 0% false-positive" yang dilaporkan awalnya **valid untuk distribusi pertanyaan user, belum tentu merepresentasikan performa nyata terhadap balasan bot** yang sesungguhnya diklasifikasi di production — potensi *train/production skew* klasik.

**Dibuktikan lewat pengujian manual**, bukan lewat command eval (karena command eval mengukur akurasi terhadap dataset itu sendiri, tidak bisa mendeteksi mismatch distribusi ini): tiga balasan tiruan yang mensimulasikan bot gagal menjaga guardrail (menuruti permintaan di luar topik, membocorkan system prompt, membocorkan data pribadi) semuanya **lolos tanpa diblokir** — kategori terdeteksi benar oleh classifier, tapi skor kemiripannya jatuh sistematis di bawah `CONFIDENT_THRESHOLD` (0,68) karena gaya bahasa balasan berbeda dari gaya bahasa referensi.

**Perbaikan**: menambah 25 contoh baru bergaya balasan-bot (5 per kategori yang bisa diblokir: `out_of_scope`, `prompt_injection`, `unsupported_fatwa`, `privacy_risk`, `payment_verification_risk`) ke `ChatbotSafetyDataset` — total dataset 120 → **145 contoh**. Setelah re-embed dan re-tuning, threshold optimal tetap 0,68 (sweep ulang mengonfirmasi titik ini masih 0% false-positive `in_domain`), dan ketiga balasan tiruan tadi kini **terblokir dengan benar**, tanpa balasan sah manapun jadi ikut terblokir (diverifikasi ulang secara manual).

**Pembelajaran metodologis**: untuk classifier nearest-neighbor yang dievaluasi lewat leave-one-out di atas dataset referensinya sendiri, penting memastikan **gaya/distribusi teks di dataset referensi menyerupai gaya/distribusi teks yang sesungguhnya diklasifikasi saat runtime** — leave-one-out cross-validation mengukur konsistensi internal dataset, bukan generalisasi ke distribusi input produksi yang berbeda gaya.

### 10.5 Dua bug perilaku ditemukan lewat perluasan `ChatbotBehaviorDataset`

Saat menambah 7 skenario baru ke `chatbot:eval-behavior` (11 → 18, menutup lebih banyak poin dari `docs/chatbot-behavior-notes.md`), dua di antaranya langsung menemukan bug produk nyata pada percobaan pertama — bukti lain bahwa evaluasi perilaku end-to-end menangkap kelas bug yang tidak tertangkap review kode atau unit test level-fungsi (pola yang sama seperti Bab 10.1).

**Bug 1 — bot tidak berhenti menghitung saat user bilang sudah bayar.** Skenario "tidak lanjut menghitung saat user bilang sudah bayar" (poin 36 di `chatbot-behavior-notes.md`) awalnya gagal: setelah user berkata *"Eh sebenarnya saya sudah transfer duluan tadi pagi"* di tengah konsultasi, bot tetap melanjutkan menanyakan data kalkulasi (mis. status haul tabungan), bukan mengarahkan ke konfirmasi pembayaran ke panitia — karena system prompt memang belum pernah punya instruksi eksplisit untuk kasus ini. **Perbaikan**: menambah satu kalimat instruksi baru di system prompt ([OpenAiChatbotProvider.php:380](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php#L380)) yang secara eksplisit melarang lanjut menghitung dan mengarahkan langsung ke konfirmasi panitia.

**Bug 2 — regresi dari optimasi latensi Bab 7.4.** Skenario "pause konsultasi saat user minta penjelasan konsep" (skenario lama, bukan baru) ikut gagal saat regression run: pesan *"Nanti dulu, jelasin nisab itu apa."* (≤8 kata, di tengah mode konsultasi) kena aturan skip-retrieval yang ditambahkan untuk optimasi latensi (Bab 7.4) — akibatnya bot menjawab *"Saya belum punya info itu di panduan Masjid An-Nur"* padahal nisab jelas ada di KB. Aturan skip itu awalnya mengasumsikan balasan pendek di tengah konsultasi selalu berupa **lanjutan data** ("50 juta", "tidak ada hutang"), padahal bisa juga berupa **pertanyaan tangensial pendek** yang tetap butuh grounding KB. **Perbaikan**: `ChatbotOrchestrator::retrieveContexts()` ([ChatbotOrchestrator.php:266](../app/Services/Chatbot/ChatbotOrchestrator.php#L266)) sekarang mengecualikan pesan yang mengandung pola pertanyaan (tanda `?` atau kata tanya seperti "apa", "kenapa", "jelasin") dari aturan skip, walau tetap pendek dan di tengah konsultasi.

Kedua bug diverifikasi ulang secara manual (balasan benar setelah perbaikan) dan lolos regresi penuh (`chatbot:eval-rag` F1 tetap 1,0; `php artisan test` 228/228).

### 10.6 Perbaikan false negative retrieval terakhir dan penambahan konten KB

Satu-satunya kasus retrieval yang masih gagal di `chatbot:eval-rag` (F1 0,987) adalah pertanyaan *"Kalau kasus saya rumit harus tanya siapa?"* — seharusnya menemukan entri `kapan-konsultasi-ustadz`, tapi entri `cara-zakky-menganalisis-kasus` (topik konseptual berdekatan: sama-sama soal "kasus rumit", beda sudut pandang) menang dengan similarity 0,452, sedikit di atas threshold 0,45. **Perbaikan**: menajamkan keyword `kapan-konsultasi-ustadz` dengan frasa yang lebih cocok dengan pola pertanyaan "harus tanya siapa" (`database/seeders/KnowledgeBaseSeeder.php`). Percobaan pertama sempat memunculkan false positive baru (pertanyaan "Rute tercepat ke Bandung pagi ini lewat mana?" ikut cocok karena kata "lewat" terlalu generik di judul entri baru lainnya) — diperbaiki dengan mengganti "lewat" jadi "melalui" di entri terkait. Setelah kedua perbaikan, `chatbot:eval-rag` mencapai **F1-score 1,0 (precision 1,0, recall 1,0)**.

Bersamaan dengan itu, ditambahkan **3 entri KB baru** untuk menutup gap konten yang teridentifikasi lewat tinjauan manual (bukan lewat eval otomatis, karena eval hanya bisa mengukur retrieval terhadap pertanyaan yang sudah ada di dataset, bukan menemukan topik yang belum pernah dimasukkan sama sekali): zakat penghasilan bruto vs. netto (potongan pajak/BPJS), boleh tidaknya menyalurkan zakat sendiri tanpa lewat panitia, dan zakat mal yang terlewat dari tahun-tahun sebelumnya. Konten ditulis mengikuti pola epistemik yang sama dengan entri KB lain (beri gambaran umum, akui ada beda pendapat ulama/lembaga, arahkan ke ustadz/panitia untuk keputusan final) sehingga tidak mengklaim fatwa tunggal.

---

### 10.7 Bug: seksi hasil ditampilkan untuk data yang tidak pernah disebut user

**Gejala**: dilaporkan langsung dari chat nyata — user hanya membahas zakat penghasilan (gaji), tapi balasan `[[HASIL]]` ikut menampilkan seksi "Estimasi Zakat Tabungan & Emas" dengan kesimpulan "belum wajib zakat tabungan/emas saat ini", padahal tabungan/emas tidak pernah disinggung sama sekali. Baris ringkasan input di atasnya juga ikut menampilkan "Tabungan: Rp 0", "Emas: 0 gram", dst. untuk field yang tidak pernah dibahas.

**Akar masalah**: `ChatbotSentinelParser` sebelumnya selalu merender **kedua** seksi (penghasilan dan tabungan/emas) begitu sentinel `[HITUNG:{...}]` valid, memakai `$data['savings'] ?? 0` dkk. — field yang memang tidak ada di JSON (karena user tidak pernah menyebutnya) didefaultkan ke 0 dan ditampilkan seolah sudah dinilai ("dihitung, hasilnya nihil"), bukan "tidak pernah ditanyakan".

**Perbaikan** ([ChatbotSentinelParser.php](../app/Services/Chatbot/ChatbotSentinelParser.php)): seksi dan baris ringkasan sekarang hanya dirender untuk field yang benar-benar `isset()` di JSON. Seksi penghasilan digerbang khusus oleh `income_monthly` (bukan gabungan dengan `expenses_monthly`), karena pengeluaran saja tetap menghasilkan penghasilan bersih Rp0 (`max(0, 0 - expenses)`) — masalah "didefaultkan, bukan benar-benar dinilai" yang sama persis. Baris **Total** hanya muncul kalau kedua seksi relevan (bukan cuma satu). Ditambahkan juga guard untuk edge case yang muncul dari pengetatan ini (pengeluaran diberikan tanpa penghasilan) yang tadinya menghasilkan blok `[[HASIL]]` kosong.

**Verifikasi**: diuji ulang terhadap skenario yang dilaporkan plus edge case (hanya pengeluaran, hanya hutang). Regresi penuh tetap bersih: `php artisan test` 228/228.

### 10.8 Bug: kombinasi "hanya hutang" masih lolos dari perbaikan Bab 10.7

**Gejala**: ditemukan lewat audit ulang kode setelah perubahan Bab 6.2 (bukan laporan user) — kalau JSON `[HITUNG:{...}]` cuma berisi `debt` (mis. `{"debt":5000000}`, tanpa `savings`/`gold_gram`/`income_monthly`), sistem tetap merender seksi "Estimasi Zakat Tabungan & Emas" lengkap dengan kesimpulan "belum wajib zakat tabungan/emas saat ini" — padahal tabungan/emas tidak pernah disebut user sama sekali.

**Akar masalah**: perbaikan Bab 10.7 mendefinisikan `$hasWealthData = isset(savings) || isset(gold_gram) || isset(debt)` — hutang dianggap sebagai bukti bahwa "tabungan/emas dibahas", padahal hutang sebenarnya cuma **pengurang** dari basis aset (tabungan + emas), bukan basis itu sendiri. Debt-only adalah persis pola bug yang sama dengan yang diperbaiki di Bab 10.7 (field yang tidak disebut ditampilkan seolah dinilai), cuma untuk kombinasi input yang belum tercakup saat itu — desain awal sengaja menyertakan `debt` di `hasWealthData` supaya kasus ini tidak menghasilkan blok `[[HASIL]]` kosong, tapi trade-off itu berarti hasilnya tetap menyesatkan (menyiratkan tabungan/emas = Rp0 yang "dinilai", bukan "tidak ditanyakan").

**Perbaikan** ([ChatbotSentinelParser.php](../app/Services/Chatbot/ChatbotSentinelParser.php)): `hasWealthData` dipersempit jadi `isset(savings) || isset(gold_gram)` saja — hutang tidak lagi dihitung sebagai sinyal "tabungan/emas dibahas" (kalkulasi `wealthBase` di `ChatbotZakatMalGuide` tetap menerima dan mengurangkan `debt` seperti biasa, kalau ada `savings`/`gold_gram` untuk dikurangi). Supaya penyempitan ini tidak memunculkan kembali masalah blok `[[HASIL]]` kosong (alasan `debt` dimasukkan di awal), guard `!$hasIncomeData && !$hasWealthData` yang tadinya dianggap redundan di Bab 6.2 (dan sempat dihapus) **dikembalikan** — kombinasi tanpa penghasilan maupun tabungan/emas kini diarahkan ke "Bisa sebutkan nominal penghasilan atau tabungannya agar bisa saya hitung?" alih-alih menampilkan hasil yang menyesatkan atau blok kosong.

**Verifikasi**: test baru `test_chatbot_asks_for_more_data_instead_of_computing_from_debt_alone` memastikan input `{"debt":5000000}` sendirian tidak lagi menghasilkan `[[HASIL]]` maupun klaim "belum wajib zakat tabungan". Regresi penuh tetap bersih: `php artisan test` 229/229.

### 10.9 Bug: sentinel `[HITUNG:...]` kedua bisa bocor mentah ke user

**Gejala**: ditemukan lewat audit ulang pola parsing (bukan laporan produksi) — `parseAndCalculateSentinel()` sebelumnya memakai `preg_match()` (satu match) lalu `str_replace($matches[0], $replacement, $reply)`. Kalau balasan LLM memuat **lebih dari satu** tag `[HITUNG:{...}]` yang isinya berbeda — di luar desain prompt (LLM diinstruksikan hanya keluarkan satu tag gabungan), tapi tidak sepenuhnya bisa dicegah dari sisi kode karena keluaran LLM tidak deterministik — cuma tag **pertama** yang dihitung dan diganti. Tag kedua tetap lolos apa adanya, sehingga sintaks internal seperti `[HITUNG:{"savings":50000000}]` bisa tampil mentah ke user, alih-alih hasil kalkulasi.

**Perbaikan** ([ChatbotSentinelParser.php](../app/Services/Chatbot/ChatbotSentinelParser.php)): parsing diubah dari `preg_match` + `str_replace` menjadi `preg_replace_callback`, sehingga setiap tag `[HITUNG:...]` yang ditemukan diproses dan diganti secara independen — logika kalkulasi per-tag dipindah ke method privat `calculateSentinel()`, dipanggil oleh callback untuk tiap match. Perilaku untuk kasus normal (satu tag) tidak berubah; `ChatbotStreamParser` (jalur streaming) sudah lebih dulu memanggil parser per-tag satu per satu saat sentinel ditemukan di stream, jadi tidak terdampak oleh perubahan ini.

**Verifikasi**: test unit baru `tests/Unit/ChatbotSentinelParserTest.php` menambahkan kasus khusus `test_multiple_hitung_tags_are_each_replaced_not_just_the_first` (dua tag berbeda dalam satu balasan, keduanya harus terganti, tidak ada `[HITUNG:` tersisa) plus 2 kasus regresi cepat (satu tag normal, debt-only). Regresi penuh tetap bersih: `php artisan test` 232/232.

### 10.10 Bug: nilai berformat Rupiah di JSON `[HITUNG:...]` dihitung diam-diam salah

**Gejala**: ditemukan lewat audit lanjutan (bukan laporan produksi) terhadap cara `(int) $data[$key]` dipakai untuk membaca tiap field dari JSON sentinel. `(int)` cast di PHP tidak menolak string yang tidak sepenuhnya numerik — ia cuma berhenti membaca di karakter tidak valid pertama, lalu memakai apa pun yang sudah terbaca. Dibuktikan langsung: `(int) "10.000.000"` (format ribuan ala Rupiah — gaya yang justru dipakai Zakky sendiri di **setiap** balasannya lewat `number_format(..., ',', '.')`) menghasilkan **10**, bukan 10 juta, karena parsing berhenti di titik kedua.

**Kenapa ini lebih serius dari bug-bug sebelumnya**: bug-bug Bab 10.7–10.9 membuat sistem menampilkan sesuatu yang *terlihat* aneh (seksi kosong, blok `[HASIL]` menyesatkan, sintaks bocor) — user punya sinyal ada yang salah. Bug ini **tidak ada sinyal sama sekali**: kalau LLM sekali saja menulis nilai dengan format ribuan (bukan angka mentah seperti diinstruksikan di system prompt) alih-alih `10000000`, hasilnya kesimpulan "belum wajib zakat" yang tampil percaya diri dan rapi, padahal dihitung dari data yang senyap-senyap ter-truncate — persis kelas bug yang paling berbahaya untuk konteks fikih/finansial: salah tapi tidak kelihatan salah.

**Perbaikan** ([ChatbotSentinelParser.php](../app/Services/Chatbot/ChatbotSentinelParser.php)): setiap nilai divalidasi dengan `is_numeric()` **sebelum** di-cast ke `int` — `is_numeric("10.000.000")` mengembalikan `false` (beda dari `(int)` cast yang diam-diam menerimanya), begitu juga format lain seperti `"10,000,000"` atau `"10 juta"`. Kalau ada field yang lolos `isset()` tapi gagal `is_numeric()`, seluruh sentinel diperlakukan sama seperti JSON yang rusak — diminta ulang, bukan dihitung dari data yang sudah tercemar.

**Verifikasi**: test unit baru `test_rupiah_formatted_string_value_is_rejected_instead_of_silently_truncated` mengonfirmasi input `{"income_monthly":"10.000.000","savings":50000000}` ditolak (pesan "kurang mengerti datanya"), bukan dihitung jadi Rp10. Regresi penuh tetap bersih: `php artisan test` 234/234.

### 10.11 Celah kualitas jawaban: bot tidak mengklarifikasi kalau user menyebut angka "bersih"

**Gejala**: ditemukan lewat tinjauan kualitas jawaban (bukan bug kode/logic seperti Bab 10.7–10.10) — setelah pindah ke basis bruto (Bab 6.2), system prompt cuma menginstruksikan LLM untuk **mengumpulkan** "gaji bulanan kotor/bruto", tapi tidak ada instruksi untuk **menangkap** kalau user malah menyebut angkanya sebagai "gaji bersih"/"take home pay" (skenario ini bahkan sudah ada di `ChatbotBehaviorDataset.php` — *"Saya mau hitung zakat mal, gaji bersih 8,5 juta per bulan."* — tapi ekspektasi test di skenario itu cuma mengecek soal klarifikasi rentang, bukan soal bruto/netto). Tanpa instruksi eksplisit, angka yang disebut user sebagai "bersih" kemungkinan besar langsung dipakai apa adanya di rumus bruto — bukan salah hitung secara kode (rumus tetap konsisten terhadap input apa pun), tapi berpotensi **salah basis**: angka net dipakai seolah gross, tanpa klarifikasi apa pun ke user tentang bahwa Masjid An-Nur memakai basis bruto.

**Perbaikan** ([OpenAiChatbotProvider.php](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php)): menambah satu kalimat instruksi — kalau user menyebut angka gajinya sebagai "gaji bersih", "take home pay", atau "setelah potongan pajak/BPJS", LLM wajib klarifikasi dulu (jelaskan Masjid An-Nur pakai basis bruto sesuai BAZNAS) dan tanyakan angka bruto-nya, alih-alih langsung memakai angka net yang disebutkan.

**Verifikasi**: test regresi baru `test_system_prompt_instructs_clarifying_bruto_when_user_states_net_salary` (pola sama dengan `test_system_prompt_instructs_confirming_intent_before_collecting_financial_data` — cuma memastikan instruksi tidak hilang di edit prompt berikutnya; kepatuhan model sesungguhnya perlu dicek lewat `chatbot:eval-behavior` manual). Regresi penuh tetap bersih: `php artisan test` 235/235.

### 10.12 Celah arsitektur: topik zakat mal lanjutan tidak dilindungi sentinel pattern

**Gejala**: ditemukan lewat pertanyaan "apakah ada yang belum memuaskan soal properti/pertanian" — KB sudah mencakup 5 topik zakat mal lanjutan (`zakat-pertanian-perkebunan`, `zakat-peternakan`, `zakat-properti-sewa`, `zakat-saham-investasi-reksadana`, `zakat-warisan`), lengkap dengan entri `batas-hitung-zakat-mal-lanjutan` yang secara jujur bilang "belum bisa hitung otomatis" untuk topik-topik ini. Tapi instruksi "JANGAN PERNAH menghitung nominal zakat mal sendiri" di system prompt cuma dipasangkan dengan satu jalur keluar: sentinel `[HITUNG:{...}]`, yang skemanya cuma punya field untuk income/savings/gold/debt. Untuk pertanian/peternakan/properti/saham/warisan, LLM tidak diberi instruksi eksplisit soal apa yang harus dilakukan kalau user memberi angka nyata dan minta dihitungkan — sehingga LLM bisa saja (meniru contoh ilustrasi di teks KB, mis. "1.000 kg gabah → zakat 100 kg") menerapkan rumus itu ke angka pribadi user secara bebas di teks biasa, persis jenis self-calculation tanpa pengaman yang sentinel pattern (Bab 6) dibuat untuk mencegah — hanya saja di sini tidak ada pengaman teknis sama sekali.

**Perbaikan** ([OpenAiChatbotProvider.php](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php)): menambah instruksi eksplisit — untuk topik zakat mal lanjutan, sentinel `[HITUNG:]` tidak berlaku, LLM dilarang menerapkan rumus ke angka pribadi user, cuma boleh menjelaskan rumus/nisab secara umum (boleh pakai angka ilustrasi seperti di panduan) lalu arahkan ke panitia/ustadz untuk angka final.

**Verifikasi**: test regresi baru `test_system_prompt_forbids_self_calculating_advanced_zakat_mal_topics` — proses menulis test inilah yang secara tidak sengaja membongkar bug jauh lebih besar di Bab 10.13 di bawah (pesan pertanian dalam test ini ternyata tidak pernah sampai ke AI sama sekali).

### 10.13 Bug: pertanyaan zakat mal paling wajar dibajak ke jawaban "total terkumpul se-masjid"

**Gejala**: ditemukan tidak sengaja saat memverifikasi Bab 10.12 — test dengan pesan pertanian gagal dengan pesan "An expected request was not recorded", artinya **AI tidak pernah dipanggil sama sekali**. Ditelusuri ke `ChatbotActionDetector::intent()`: pengecekan intent `ask_total_summary` di baris paling atas fungsi cuma mensyaratkan kombinasi kata **"berapa" + "zakat"** (atau "semua"/"terkumpul"/"penerimaan") — tanpa pengecualian apa pun. Dikonfirmasi langsung lewat `php artisan tinker`: pesan **"Saya mau hitung zakat mal, gaji 10 juta, berapa zakatnya?"** dan **"Zakat penghasilan saya berapa kalau gaji 8 juta?"** — dua cara paling wajar dan umum untuk bertanya soal zakat mal pribadi — sama-sama ter-resolve ke `ask_total_summary`, balasan cepat berisi total zakat terkumpul se-masjid, **sebelum pernah sampai ke pengecekan intent zakat-mal spesifik manapun, apalagi ke AI**.

**Kenapa ini paling parah dari semua temuan hari ini**: bug-bug sebelumnya (Bab 10.7–10.11) muncul di kasus-kasus yang cukup spesifik/jarang (data tidak lengkap, format angka aneh, dua sentinel sekaligus). Bug ini menghantam **frasa paling umum** yang orang pakai untuk bertanya "zakat saya berapa" — persis pola bug yang sama seperti Bab 10.1 (fast-path terlalu agresif), tapi mengenai jalur yang jauh lebih sering dilalui pengguna nyata.

**Akar masalah**: `ask_zakat_mal_definition` sudah punya guard `$looksLikeCalculationRequest` sejak Bab 10.1 (kata "hitung"/"konsultasi" + angka) untuk mencegah pembajakan serupa — tapi guard itu didefinisikan **di tengah fungsi**, setelah pengecekan `ask_total_summary`, `ask_total_people`, dan `ask_total_money` di bagian atas fungsi sudah sempat dieksekusi lebih dulu. Guard yang sudah ada pun ternyata terlalu sempit: cuma menangkap kata "hitung"/"konsultasi", padahal "Zakat penghasilan saya berapa kalau gaji 8 juta?" tidak memakai kata "hitung" sama sekali.

**Perbaikan** ([ChatbotActionDetector.php](../app/Services/Chatbot/ChatbotActionDetector.php)):

1. `$looksLikeCalculationRequest` dipindah ke **paling atas** fungsi `intent()`, dipakai untuk menjaga keempat pengecekan `ask_total_summary`/`ask_total_people`/`ask_total_money` (dua kali muncul di fungsi ini) — bukan cuma `ask_zakat_mal_definition`.
2. Definisinya diperluas: selain "hitung"/"konsultasi" + angka, sekarang juga mengenali kata sinyal finansial (`gaji`, `tabungan`, `penghasilan`, `emas`, `hutang`, `aset`) + angka — memakai universe kata kunci yang sama dengan `ChatbotConversationContext::detectMode()`'s `$hasFinancialSignal`, supaya konsisten dengan definisi "sinyal finansial" yang sudah dipakai di tempat lain.
3. Pengecekan `ask_total_rice` (baris terpisah, Bab audit sebelumnya) juga diperbaiki: anchor wajib diganti dari `beras`/`kg` (dua-duanya opsional) jadi `beras` wajib + `kg` sebagai salah satu qualifier — karena "kg" sendirian terlalu generik (bisa muncul di pertanyaan berat apa pun, termasuk pertanian) untuk jadi penentu utama topik "total beras zakat fitrah terkumpul".

**Verifikasi**: test unit baru `tests/Unit/ChatbotActionDetectorTest.php` — 4 kasus pembajakan (termasuk kasus pertanian yang memicu temuan ini) dipastikan `null` (lolos ke AI), plus 2 kasus soal pertanyaan agregat asli (`ask_total_summary`) dipastikan tetap jalan seperti biasa supaya fix ini tidak mematikan fitur yang sah. Regresi penuh tetap bersih: `php artisan test` 241/241.

### 10.14 Refactor + 2 bug tambahan: duplikasi blok intent yang tidak disadari

**Konteks**: setelah Bab 10.13, ditinjau ulang struktur `ChatbotActionDetector::intent()` secara keseluruhan (bukan cuma titik bug yang sudah ditemukan) untuk menilai seberapa rapuh detektor ini secara umum. Ditemukan: tiga intent (`ask_total_people`, `ask_total_money`, `ask_total_summary`) **masing-masing dicek dua kali** di fungsi yang sama — satu di bagian atas (dengan syarat pasangan kata kunci yang ketat), satu lagi ~60 baris di bawahnya (versi lebih longgar, sisa dari edit-edit masa lalu yang tidak saling sadar). Ini persis pola yang menyebabkan Bab 10.13: waktu memperbaiki bug itu, guard `!$looksLikeCalculationRequest` harus ditempel di **dua tempat** — kalau satu ketinggalan, perbaikannya cuma setengah jalan.

**Dibuktikan lewat `php artisan tinker`, versi duplikat (blok bawah) menyembunyikan 2 bug aktif lain yang belum pernah ketahuan:**
- `ask_total_people` versi longgar tidak mensyaratkan pasangan "total"/"jumlah" sama sekali — kata **"orang"** sendirian sudah cukup. Pesan *"Orang tua saya sudah wafat, warisannya kena zakat gak?"* salah dibajak ke jawaban "total jiwa zakat fitrah terkumpul".
- `ask_total_summary` versi longgar tidak mensyaratkan pasangan kata bertopik zakat/agregat — kata **"berapa"** sendirian sudah cukup. Pesan *"Masjidnya di jalan apa, rumahnya nomor berapa ya?"* (soal alamat) ikut ter-hijack.

**Perbaikan** ([ChatbotActionDetector.php](../app/Services/Chatbot/ChatbotActionDetector.php)): blok duplikat (versi longgar) dihapus total, bukan dipertahankan "untuk jaga-jaga" — versi ketat di bagian atas fungsi sudah mencakup semua kasus sah yang dimaksudkan versi longgar. Sekalian ditemukan bug ketiga di versi yang **tidak dihapus** (blok atas, Bab 6/9 lama): syarat pasangan kata untuk `ask_total_summary` mengizinkan kata **"zakat"** sebagai salah satu pemenuhnya — padahal "zakat" muncul di hampir semua pertanyaan bertopik zakat apa pun, jadi kombinasi "berapa" + "zakat" nyaris selonggar tanpa syarat sama sekali. Pesan KB yang sah seperti *"Zakat perdagangan itu dihitung dari modal atau omzet, berapa persennya?"* ikut ter-hijack ke jawaban total terkumpul. Diperbaiki dengan menghapus "zakat" dari daftar itu — sisanya (`semua`, `terkumpul`, `penerimaan`) memang secara semantik menyiratkan agregat, beda dari "zakat" yang cuma penanda topik.

**Verifikasi**: 2 test baru di `ChatbotActionDetectorTest.php` — `test_unrelated_questions_are_not_hijacked_by_bare_generic_words` (dataset warisan + zakat perdagangan) plus penambahan assertion `ask_total_money`/`ask_total_people` genuine di test yang sudah ada, supaya penghapusan blok duplikat tidak diam-diam mematikan salah satu jalur yang sah. Regresi penuh tetap bersih: `php artisan test` 243/243.

**Pembelajaran metodologis**: bug paling berbahaya di sistem berbasis aturan keyword bukan selalu dari kata kunci baru yang ditambahkan sembarangan — kadang dari **kode lama yang dilupakan**, tetap aktif, tidak pernah dihapus meski sudah ada versi yang lebih baik di tempat lain dalam file yang sama. Tinjauan struktural (bukan cuma menambal titik bug yang dilaporkan) menemukan 2 bug tambahan yang tidak pernah muncul sebagai keluhan spesifik.

### 10.15 Audit sistematis seluruh `ChatbotActionDetector`: 11 kasus pembajakan tambahan

**Konteks**: setelah tiga temuan berturut-turut di `ChatbotActionDetector` (Bab 10.13–10.14), muncul pertanyaan eksplisit — apakah detektor ini sudah cukup andal, atau masih ada ruang perbaikan? Alih-alih menambal titik-titik yang kebetulan dilaporkan, seluruh fungsi `intent()` diaudit sistematis: tiap cabang diuji satu per satu lewat `php artisan tinker` dengan pesan-pesan adversarial yang secara sengaja mengandung kata kunci pemicu tapi topiknya tidak relevan.

**Prinsip yang dipakai untuk audit**: kata kunci tunggal yang **generik dalam bahasa Indonesia sehari-hari** ("orang", "hari", "harian", "paling besar", "tertinggi", "rekening", "transfer", "cara bayar", "kategori") tidak boleh jadi satu-satunya penentu topik zakat/masjid — harus dipasangkan dengan kata yang benar-benar spesifik ke domain (mis. "jiwa" utk zakat fitrah, "penerimaan"/"terkumpul" utk data agregat), atau kalau tidak bisa dipasangkan dengan aman, kata itu dilepas dari daftar sepenuhnya dan pesan dibiarkan lolos ke AI.

**11 kasus pembajakan baru ditemukan dan diperbaiki** ([ChatbotActionDetector.php](../app/Services/Chatbot/ChatbotActionDetector.php)):

| Pesan | Salah dibajak ke (sebelum) | Sekarang |
|---|---|---|
| "Total pengeluaran ... untuk orang tua ..., ngurangin zakat gak?" | `ask_total_people` (dari "orang") | lolos ke AI |
| "Saya mau hitung THR buat 3 orang karyawan..." | `calculate_fitrah_case` (dari "orang"+hitung+angka) | lolos ke AI |
| "Ada 5 hari libur lebaran ini, mau hitung cuti tambahan..." | `calculate_fidyah_case` (dari "hari"+hitung+angka) | lolos ke AI |
| "Petugas piket harian siapa aja ya minggu ini?" | `open_chart` (dari "harian") | lolos ke AI |
| "Nisab yang paling besar itu emas atau uang tunai?" | `ask_top_category` (dari "paling besar") | lolos ke AI |
| "...mana yang nisabnya tertinggi?" | `ask_top_category` (dari "tertinggi") | lolos ke AI |
| "Kategori aset yang kena zakat itu apa aja?" | `ask_categories` (dari "kategori") | lolos ke AI |
| "Jenis zakat mal yang paling sering ditanyakan apa ya?" | `ask_categories` (dari "jenis zakat") | lolos ke AI |
| "Ringkasan singkat soal zakat mal dong" | `open_summary` (dari "ringkasan") | lolos ke AI |
| "Rekening BCA punya saya kena zakat gak..." | `ask_payment_info` (dari "rekening") | lolos ke AI |
| "Cara bayar hutang riba itu gimana..." | `ask_payment_info` (dari "cara bayar") | lolos ke AI |

**Perbaikan per cabang** — pola yang konsisten dengan Bab 10.13–10.14 (anchor spesifik, bukan kata generik dipasangkan longgar):
- `ask_total_people`, `calculate_fitrah_case`: kata "orang" dihapus, disisakan "jiwa"/"muzakki fitrah" yang secara istilah memang khas domain zakat fitrah.
- `calculate_fidyah_case`: kata "hari" dihapus, disisakan "fidyah"/"puasa".
- `open_chart`: kata "harian" dihapus (sudah tercakup lewat "grafik"/"chart"/"tren").
- `ask_top_category`, `ask_categories`: kini wajib dipasangkan dengan kata "kategori" atau "penerimaan"/"tercatat"/"terkumpul" — bukan cuma kata pembanding generik ("paling besar"/"tertinggi") atau kata "kategori" sendirian.
- `open_summary`: kini wajib dipasangkan dengan kata kerja aksi ("buka"/"lihat"/"tampilkan"/"cek") atau kata data ("penerimaan"/"terkumpul") — membedakan "buka ringkasan [fitur dashboard]" dari "ringkasan [penjelasan singkat] soal X".
- `ask_payment_info`: daftar kata kunci diganti dari kata tunggal (`rekening`, `transfer`, `cara bayar`) jadi frasa penuh yang menyertakan "zakat" (`rekening zakat`, `cara bayar zakat`, dst.) — mencegah tabrakan dengan pertanyaan soal rekening/pembayaran di luar konteks zakat.

**Verifikasi**: 12 test baru di `ChatbotActionDetectorTest.php` — `test_unrelated_questions_are_not_hijacked_by_other_generic_anchor_words` (11 kasus di atas, dipastikan `null`/lolos ke AI) dan `test_narrowed_intents_still_resolve_for_their_genuine_phrasing` (5 kasus sah, dipastikan tetap jalan seperti biasa supaya penyempitan ini tidak mematikan fitur yang sah). Regresi penuh tetap bersih: `php artisan test` 255/255.

**Catatan untuk bab metodologi**: pendekatan "audit sistematis lewat pengujian adversarial manual" ini menemukan 11 bug sekaligus dalam satu sesi tinjauan — jauh lebih efektif dibanding menunggu tiap kasus dilaporkan satu per satu lewat penggunaan nyata (pola Bab 10.1, 10.5, 10.13 sebelumnya). Trade-off yang disengaja: fast-path (tanpa panggil AI) sekarang mencakup lebih sedikit variasi kalimat — pesan yang topiknya ambigu kini konsisten dilempar ke AI, yang memang sudah punya pemahaman bahasa natural (sinonim, negasi, konteks) tanpa perlu dibangun ulang di lapisan keyword-matching ini. Fast-path yang tersisa jadi lebih sempit tapi jauh lebih presisi.

### 10.16 Restrukturisasi system prompt + bug serius: versi Bahasa Inggris tanpa pengaman sama sekali

**Konteks**: ditinjau kualitas system prompt (`OpenAiChatbotProvider::getSystemInstruction()`) secara keseluruhan, bukan menunggu laporan spesifik. Diukur langsung: versi Indonesia **714 kata, 37 kalimat**, semuanya digabung jadi **satu paragraf datar** tanpa heading/bullet/pengelompokan sama sekali — hasil akumulasi organik, setiap perbaikan sepanjang sesi ini (konfirmasi niat, klarifikasi bruto, larangan topik lanjutan, dst.) cuma ditempel sebagai satu kalimat lagi di ujung string yang sama.

**Bug serius ditemukan saat membandingkan versi ID vs EN**: versi Bahasa Inggris cuma **120 kata**, dan setelah dicek satu per satu — **sama sekali tidak menyebut** sentinel `[HITUNG:...]` maupun larangan "JANGAN PERNAH menghitung nominal zakat mal sendiri". Dikonfirmasi lewat `ChatbotLanguageDetector`: begitu >30% kata pesan cocok daftar kata penanda Inggris, `language='en'` otomatis dikirim ke `getSystemInstruction()`. Artinya **user yang chat dalam Bahasa Inggris kehilangan seluruh pengaman zakat-mal** — LLM bisa saja langsung menghitung sendiri di teks bebas tanpa lewat kalkulator PHP, tanpa validasi angka, tanpa satu pun dari 6 aturan keras yang sudah dibangun sepanjang sesi ini (Bab 6, 10.11, 10.12) — karena semua aturan itu ditambahkan **hanya** ke blok `if ($language === 'id')`, tidak pernah ke blok default (EN).

**Perbaikan** ([OpenAiChatbotProvider.php](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php)):
1. **Restrukturisasi kedua bahasa** jadi dua bagian berlabel jelas: **ATURAN KERAS (jangan pernah dilanggar)** — berisi semua instruksi yang kalau dilanggar berakibat salah hitung/halusinasi/kebocoran (never self-calculate, skema JSON wajib, konfirmasi niat, klarifikasi bruto, larangan topik lanjutan, dst.) — dipisah dari **GAYA BICARA** — preferensi nada/tone yang kalau dilanggar cuma bikin jawaban kurang natural, bukan salah secara substansi. Pemisahan ini penting karena LLM (dan pembaca manusia) lebih andal mengikuti instruksi terstruktur dibanding satu paragraf 37-kalimat tanpa hierarki.
2. **Versi EN ditulis ulang dari nol** dengan paritas penuh terhadap seluruh Aturan Keras versi ID (diterjemahkan, bukan disingkat) — termasuk skema `[HITUNG:...]`, larangan self-calculate, klarifikasi gaji bersih/bruto, dan larangan topik lanjutan. Bagian Gaya Bicara EN sengaja tetap lebih ringkas dari ID (bahasa Inggris adalah jalur sekunder/edge-case di aplikasi ini — seluruh KB, dokumentasi, dan dataset eval berbahasa Indonesia) — tapi seluruh Aturan Keras (yang menentukan benar/salah, bukan cuma gaya) kini identik cakupannya di kedua bahasa.
3. Label heading "Konteks resmi:" yang disisipkan sebelum entri KB juga ternyata hardcode Indonesia terlepas dari bahasa pesan — diperbaiki jadi language-aware ("Official Context:" untuk EN).

**Verifikasi**: test baru `test_english_system_prompt_has_the_same_hard_rules_as_indonesian` (lewat reflection langsung ke `getSystemInstruction('en', [])`, bukan lewat deteksi bahasa otomatis — `ChatbotLanguageDetector` memakai daftar kata penanda tetap yang rapuh untuk dipicu secara reliable lewat teks bebas, jadi diuji langsung isinya) memastikan versi EN sekarang memuat larangan self-calculate, skema JSON persis, dan klarifikasi gaji bersih. Semua substring yang di-assert test lain terhadap prompt ID (konfirmasi niat, klarifikasi bruto, larangan topik lanjutan) dipertahankan persis sama meski struktur teksnya berubah — tidak ada test lama yang perlu diubah. Regresi penuh tetap bersih: `php artisan test` 256/256.

**Catatan tambahan**: `ChatbotLanguageDetector` sendiri (heuristik daftar kata penanda tetap, bukan classifier) juga teridentifikasi sebagai komponen yang cukup rapuh selama proses debug test ini — di luar scope perbaikan sesi ini, dicatat sebagai kandidat perbaikan lanjutan di Bab 12.

### 10.17 Bug: field `citations` di respons API membocorkan instruksi internal + gagal tampil di UI

**Gejala**: ditemukan tidak sengaja lewat inspeksi payload JSON respons API (bukan laporan user) saat mendebug test lain — field `citations` pada balasan jalur AI ternyata berisi objek mentah `_conversation_hint` (instruksi prompt-engineering internal seperti *"Mode percakapan: konsultasi zakat mal. Rangkum singkat data yang sudah diberikan user..."*), bukan daftar sumber/rujukan yang wajar ditampilkan ke user. Diverifikasi lewat `php artisan test`: pada percakapan mode `zakat_mal_consultation` (kondisi yang sangat umum), payload JSON yang dikirim ke browser memuat instruksi internal ini apa adanya.

**Akar masalah ganda**:
1. `ChatbotOrchestrator::finalizeAiReply()` mengoper `$contexts` — array internal yang dipakai `ChatbotConversationContext::withHints()` untuk menyisipkan hint (`_conversation_hint`, `_sentiment_hint`, `_correction_hint`) ke system prompt LLM — **langsung** sebagai `citations` di `ChatbotResponse::success()`, tanpa filter. Hint-hint ini seharusnya cuma untuk konsumsi LLM, tidak pernah dimaksudkan sampai ke response API publik.
2. Bahkan untuk entri KB asli (bukan hint) yang berhasil match, field yang dipakai adalah `source_label` ([KnowledgeBase.php:41](../app/Models/KnowledgeBase.php#L41)) — sementara frontend (`chatbot-widget.blade.php:208`) merender footer sitasi dengan `'Acuan: ' + message.citations[0].label` (`label`, bukan `source_label`). Akibatnya, untuk **setiap** balasan jalur AI yang punya `citations` (termasuk hint-only tanpa KB match sama sekali), UI menampilkan literal **"Acuan: undefined"** di bawah balasan bot — bug tampilan yang selalu bisa direproduksi, bukan cuma kadang-kadang.

Dua bug ini saling menutupi: karena field yang salah (`source_label`) dipakai, bug kebocoran hint tidak pernah terlihat sebagai teks aneh di layar (cuma "undefined" yang tampak) — kebocoran instruksi internalnya cuma terlihat kalau membuka Network tab browser atau memeriksa payload JSON langsung.

**Perbaikan** ([ChatbotOrchestrator.php](../app/Services/Chatbot/ChatbotOrchestrator.php)): method baru `buildCitations()` — memfilter `$contexts` supaya cuma entri yang benar-benar berisi `title` (KB asli) yang lolos jadi citation (entri hint-only otomatis tersaring karena tidak punya `title`), lalu memetakan `source_label` → `label` supaya bentuknya konsisten dengan yang sudah dipakai jalur fast-path ([ChatbotOrchestrator.php:143](../app/Services/Chatbot/ChatbotOrchestrator.php#L143), yang sejak awal sudah benar).

**Verifikasi**: 2 test baru — `test_ai_reply_citations_do_not_leak_internal_conversation_hints` (memastikan `citations` kosong dan tidak ada string `_conversation_hint` di body respons untuk turn yang men-trigger mode konsultasi) dan `test_ai_reply_citations_use_the_label_key_the_frontend_reads` (memastikan tiap citation asli punya key `label` terisi dan tidak lagi punya key `source_label`). Regresi penuh tetap bersih: `php artisan test` 258/258.

**Pembelajaran metodologis**: bug ini luput dari 3 lapisan verifikasi yang sudah ada — guardrail (Bab 8) cuma memeriksa teks balasan LLM, bukan field lain di response JSON; test manual sebelumnya cuma memeriksa `data.reply`, tidak pernah memeriksa `data.citations`; dan secara visual di UI, kegagalannya cuma tampak sebagai kata "undefined" yang mudah diabaikan/tidak dicurigai sebagai kebocoran data. Ditemukan murni karena kebiasaan membaca payload JSON penuh saat verifikasi, bukan cuma memeriksa field yang "diduga" relevan.

---

### 10.18 Bug: `ChatbotConversationContext::detectMode()` masuk mode konsultasi zakat mal cuma karena ada angka apa pun

**Gejala**: ditemukan lewat audit konteks/knowledge secara umum (bukan laporan produksi) — `$hasFinancialSignal` di `detectMode()` memakai `preg_match('/\d/', $normalized)`, cek "ada digit di mana pun" tanpa syarat tambahan. Dibuktikan lewat `php artisan tinker`: pesan *"Assalamualaikum, saya mau tanya jadwal shalat jam 5 sore"*, *"Ada acara kajian jam 7 malam ini gak?"*, dan *"Nomor antrian saya 15, sudah dipanggil belum?"* — tiga pertanyaan yang sama sekali tidak berkaitan dengan zakat mal — semuanya ter-resolve ke mode `zakat_mal_consultation` cuma karena mengandung angka (jam, nomor antrian).

**Dampak**: begitu mode salah terdeteksi, `applyConversationHint()` menyuntikkan instruksi *"Mode percakapan: konsultasi zakat mal. Rangkum singkat data yang sudah diberikan user..."* ke system prompt untuk pertanyaan yang tidak relevan sama sekali, dan mode ini **menempel** ke giliran-giliran berikutnya lewat context yang di-roundtrip ke frontend dan kembali (logika "stay in mode" di baris 81-100 kalau tidak ada kata pemicu ganti topik eksplisit).

**Akar masalah**: cek digit polos ini sebenarnya redundan — pasangan kata kunci finansial eksplisit (`gaji`, `tabungan`, `emas`, `hutang`, `pengeluaran`, `aset`) sudah cukup menangkap kasus sah ("gaji 10 juta" tetap match lewat kata "gaji"), dan follow-up angka polos di tengah konsultasi ("50 juta" tanpa kata kunci) sudah ditangani terpisah oleh logika "stay in mode" yang mengandalkan mode giliran sebelumnya, bukan `$hasFinancialSignal`.

**Perbaikan** ([ChatbotConversationContext.php](../app/Services/Chatbot/ChatbotConversationContext.php)): hapus klausa `preg_match('/\d/', ...)`, sisakan kata kunci finansial eksplisit saja. Diverifikasi lewat `tinker`: ketiga pesan adversarial di atas sekarang resolve ke `general`; kasus sah ("gaji 10 juta", "tabungan...hutang...") tetap `zakat_mal_consultation`; follow-up angka polos mid-konsultasi ("50 juta", "tidak ada hutang") tetap bertahan di mode lewat logika stay-in-mode yang independen dari perubahan ini. Regresi penuh tetap bersih: `php artisan test` 258/258 (di titik ini, sebelum penambahan fitur observability Bab 13).

### 10.19 Bug: redaksi privasi (`ChatbotChatLogger`) membocorkan placeholder `[nominal]` ke konteks AI — koreksi atas Bab 10.2

**Gejala**: dilaporkan langsung dari chat nyata — user bertanya *"Penghasilan saya Rp7.500.000 per bulan. Apakah sudah mencapai nisab zakat penghasilan dan berapa zakat yang harus saya bayar?"*, dijawab penjelasan generik nisab/haul (lihat Bab 10.20 untuk sebab jawaban generik itu sendiri). Giliran kedua, user menjawab *"ya berapa"* — balasan Zakky malah menyebut literal **"Rp[nominal]"** dan meminta user menyebutkan ulang nominalnya, seolah `[nominal]` adalah placeholder yang belum terisi.

**Akar masalah**: `ChatbotChatLogger::redactNominals()` ([ChatbotChatLogger.php](../app/Services/Chatbot/ChatbotChatLogger.php)) memakai regex `/\d[\d.,]{5,}\d|\b\d{6,}\b/` untuk mengganti angka berformat grouping panjang dengan `[nominal]` sebelum disimpan ke `ai_chat_logs` — tujuannya privasi jangka panjang. Regex ini cocok dengan **"Rp7.500.000"**, format Rupiah baku bertitik ribuan yang justru dipakai Zakky sendiri di setiap balasannya (lihat Bab 10.10). `ChatbotChatLogger::history()` kemudian membaca ulang **teks yang sudah ter-redaksi ini langsung dari `ai_chat_logs`** untuk dijadikan riwayat percakapan yang dikirim balik ke LLM di giliran berikutnya (`OpenAiChatbotProvider::buildMessagesArray()`). LLM pun melihat literal `"Rp[nominal]"` sebagai bagian dari histori percakapannya sendiri, dan membalasnya seolah itu adalah placeholder yang belum diisi.

**Kenapa investigasi Bab 10.2 meleset**: kesimpulan awal ("user biasanya menulis angka bebas, bukan format Rupiah") tidak menguji kasus format Rupiah baku secara eksplisit — asumsi yang keliru untuk format yang justru sangat lazim ditulis user saat menyebut nominal gaji/tabungan.

**Perbaikan** ([ChatbotChatLogger.php](../app/Services/Chatbot/ChatbotChatLogger.php)): memisahkan dua kebutuhan yang sebelumnya digabung di satu tempat penyimpanan. `ai_chat_logs` (DB permanen, dipakai untuk audit/analitik) **tetap** ter-redaksi seperti semula — tujuan privasinya (jangan simpan angka finansial jamaah dalam bentuk plain text jangka panjang) tetap valid dan dipertahankan. Ditambahkan cache terpisah berumur pendek (session-scoped, TTL 30 menit, key `chatbot:conversation:{session_id}`) yang menyimpan teks **asli tanpa redaksi** — dipakai khusus untuk membangun ulang konteks percakapan ke LLM. `history()` sekarang membaca dari cache ini, bukan dari kolom `ai_chat_logs` yang ter-redaksi.

**Verifikasi**: test regresi baru `test_formatted_rupiah_nominal_survives_into_next_turn_context` ([ChatbotApiTest.php](../tests/Feature/ChatbotApiTest.php)) mereproduksi persis skenario yang dilaporkan (dua giliran, "Rp7.500.000" di giliran pertama) dan memastikan riwayat yang dikirim ke provider AI di giliran kedua memuat angka asli, bukan `[nominal]`, sementara salinan di `ai_chat_logs` tetap ter-redaksi. Regresi penuh tetap bersih.

**Pembelajaran metodologis**: kesimpulan "tidak masalah dalam praktik" di Bab 10.2 ditulis berdasarkan asumsi soal kebiasaan format penulisan user, bukan pengujian eksplisit terhadap format yang justru dipakai sistem sendiri di balasannya — kesalahan yang sama persis kelasnya dengan Bab 10.10 (asumsi soal format input LLM, dibuktikan salah lewat pengujian langsung, bukan penalaran).

### 10.20 5 kasus pembajakan lanjutan di `ChatbotActionDetector` lolos dari audit sistematis Bab 10.15

**Konteks**: Bab 10.15 mengklaim audit sistematis atas seluruh fungsi `intent()` menutup 11 kasus pembajakan. Laporan bug Bab 10.19 di atas memicu pemeriksaan ulang cabang `ask_zakat_mal_nishab` — dan menemukan bahwa cabang itu (serta 4 cabang lain) **tidak pernah** diberi guard `!$looksLikeCalculationRequest` yang sudah dipasang di cabang-cabang tetangganya sejak Bab 10.13, kemungkinan karena ditambahkan pada titik kode yang berbeda dan tidak ikut disisir saat guard itu disebarkan. Audit lanjutan proaktif ke sisa fungsi (bukan cuma titik yang dilaporkan) menemukan 2 kasus tambahan lagi dengan pola yang sama.

**5 kasus pembajakan ditemukan dan diperbaiki** ([ChatbotActionDetector.php](../app/Services/Chatbot/ChatbotActionDetector.php)):

| Pesan | Salah dibajak ke (sebelum) | Sekarang |
|---|---|---|
| "Penghasilan saya Rp8.000.000 per bulan. Apakah sudah mencapai nisab...?" | `ask_zakat_mal_nishab` (definisi generik) | lolos ke AI |
| "Gaji saya 8 juta, tanggungan 3 jiwa, hitung zakat mal saya berapa?" | `calculate_fitrah_case` (dari "jiwa"+hitung+angka) | lolos ke AI |
| "Saya batal puasa 3 hari..., gaji 8 juta hitung zakat mal saya gimana?" | `calculate_fidyah_case` (dari "puasa"+hitung+angka) | lolos ke AI |
| "Kasih contoh hitungan zakat mal untuk gaji saya 8 juta dong" | `ask_zakat_mal_example` (contoh generik) | lolos ke AI |
| "Panen saya 500 kg beras, hitungkan zakatnya berapa kg" / "Saya punya beras 800 kg..., berapa zakat yang harus dikeluarkan?" | `ask_total_rice` (total beras se-masjid) | lolos ke AI |
| "Kapan sebaiknya saya bayar zakat mal saya, gaji saya 8 juta?" | `ask_payment_info` (dari substring "bayar zakat" di dalam "bayar zakat **mal**") | lolos ke AI |
| "Totalnya gaji saya 8 juta..., kena zakat gak?" (setelah sesi sempat bertopik data publik) | `publicDataFollowUpIntent()` — duplikat tak terjaga dari `ask_total_summary`/`ask_latest_update` | lolos ke AI |

**Perbaikan per cabang**:
- `ask_zakat_mal_nishab` ([ChatbotActionDetector.php:87](../app/Services/Chatbot/ChatbotActionDetector.php#L87)): ditambah guard `!$looksLikeCalculationRequest`, pola identik dengan `ask_zakat_mal_definition`.
- `calculate_fitrah_case`/`calculate_fidyah_case` ([ChatbotActionDetector.php:65-71](../app/Services/Chatbot/ChatbotActionDetector.php#L65-L71)): **tidak** dijaga oleh `$looksLikeCalculationRequest` biasa (kata "hitung" + angka juga cara paling wajar menulis permintaan fitrah/fidyah yang sah — men-guard dengan itu akan mematikan fitur yang benar). Ditambah variabel baru `$hasZakatMalSignal` ([ChatbotActionDetector.php:32](../app/Services/Chatbot/ChatbotActionDetector.php#L32)) — subset lebih sempit yang cuma memeriksa kata sinyal zakat-mal (`gaji`/`tabungan`/`penghasilan`/`emas`/`hutang`/`aset`) tanpa "hitung"/"konsultasi" — supaya "hitung fitrah saya 4 orang" (sah) tetap lolos sementara "gaji 8 juta ... hitung zakat mal" (salah topik) tertahan.
- `ask_zakat_mal_example` ([ChatbotActionDetector.php:79](../app/Services/Chatbot/ChatbotActionDetector.php#L79)): ditambah guard `!$looksLikeCalculationRequest`, pola identik dengan `ask_zakat_mal_definition` tepat di bawahnya — cabang ini terlewat saat guard itu ditambahkan meski secara struktur bersebelahan.
- `ask_total_rice` ([ChatbotActionDetector.php:145](../app/Services/Chatbot/ChatbotActionDetector.php#L145)): ditambah guard `!$looksLikeCalculationRequest`, dan pasangan kata dipersempit — "berapa"/"kg" (generik, bukan penanda agregat) dihapus, disisakan "total"/"terkumpul"/"jumlah" saja, mengikuti pola yang sudah dipakai `ask_total_money`/`ask_total_summary`.
- `ask_payment_info` ([ChatbotActionDetector.php:185](../app/Services/Chatbot/ChatbotActionDetector.php#L185)): ditambah guard `!$looksLikeCalculationRequest` — frasa "bayar zakat" (sudah dipersempit dari kata tunggal sejak Bab 10.15) ternyata masih jadi substring dari frasa alami "bayar zakat **mal**".
- `publicDataFollowUpIntent()` ([ChatbotActionDetector.php:202](../app/Services/Chatbot/ChatbotActionDetector.php#L202)): pemanggilannya digerbang tambahan `&& !$looksLikeCalculationRequest` — method ini adalah versi follow-up dari `ask_total_summary`/`ask_latest_update` yang aktif kalau topik giliran sebelumnya `public_data`, tapi tidak pernah ikut dijaga guard yang sama seperti versi aslinya, sehingga sesi yang pernah bertopik data publik jadi rentan membajak pertanyaan kalkulasi berikutnya.

**Verifikasi**: 5 test method baru (13 kasus data-provider) di `ChatbotActionDetectorTest.php` memastikan seluruh pesan di tabel di atas resolve ke `null` (lolos ke AI). Regresi penuh tetap bersih: `php artisan test` 326/326 (lihat Bab 10.23 untuk hitungan final pasca seluruh perbaikan hari ini).

**Pembelajaran metodologis**: mengonfirmasi ulang catatan Bab 10.15 sendiri — audit "sistematis" tetap punya batas dan bukan jaminan tuntas. 5 kasus di atas ditemukan lewat kombinasi laporan pengguna nyata (memicu pemeriksaan ulang satu cabang) diikuti audit proaktif ke cabang lain dengan pola serupa — bukan lewat metode yang berbeda dari Bab 10.15, tapi bukti bahwa metode yang sama perlu diulang berkala, bukan dianggap selesai setelah satu putaran.

### 10.21 Bug: `ChatbotConversationContext` kehilangan sinyal "penghasilan", tidak konsisten dengan `ChatbotActionDetector`

**Gejala**: ditemukan lewat audit lanjutan (bukan laporan terpisah) setelah Bab 10.20 — pertanyaan *"Penghasilan saya 8 juta, kena zakat gak?"* lolos dari pembajakan `ChatbotActionDetector` (benar, sampai ke AI) tapi `ChatbotConversationContext::detectMode()` salah meresolvenya ke mode `general`, bukan `zakat_mal_consultation`. Dibuktikan lewat perbandingan langsung: pesan yang secara makna identik, *"Gaji saya 8 juta, kena zakat gak?"*, resolve dengan benar ke `zakat_mal_consultation`.

**Dampak**: begitu mode salah terdeteksi sebagai `general`, `applyConversationHint()` tidak menyuntikkan instruksi konsultasi terpandu ke system prompt — LLM tidak diberi tahu untuk merangkum data, menanyakan data yang kurang, dan mengeluarkan `[HITUNG:...]`. Kemungkinan besar ini kontributor utama gejala awal yang dilaporkan user (Bab 10.19): jawaban generik nisab/haul, bukan alur konsultasi yang mengarah ke perhitungan.

**Akar masalah**: `$hasFinancialSignal` di `detectMode()` ([ChatbotConversationContext.php](../app/Services/Chatbot/ChatbotConversationContext.php)) berisi `gaji`, `tabungan`, `emas`, `hutang`, `pengeluaran`, `aset` — daftar kata kunci yang seharusnya sinkron dengan `$looksLikeCalculationRequest`/`$hasZakatMalSignal` di `ChatbotActionDetector` (dua file yang secara desain memakai "universe kata sinyal finansial" yang sama, ditegaskan eksplisit di Bab 10.13 poin 2) — tapi "penghasilan" ada di daftar `ChatbotActionDetector`, tidak pernah ditambahkan ke daftar `ChatbotConversationContext`.

**Perbaikan** ([ChatbotConversationContext.php](../app/Services/Chatbot/ChatbotConversationContext.php)): menambahkan `penghasilan` ke `$hasFinancialSignal`, menyamakan kembali kedua daftar.

**Verifikasi**: test regresi baru `test_penghasilan_keyword_triggers_the_mode_same_as_gaji` memastikan kedua frasa ("penghasilan" dan "gaji") resolve ke mode yang sama. Regresi penuh tetap bersih.

**Pembelajaran metodologis**: dua daftar kata kunci yang dimaksudkan identik tapi didefinisikan terpisah di dua file berbeda adalah sumber drift yang mudah luput — satu file diperbarui (Bab 10.13), satunya tidak, tanpa mekanisme apa pun yang memberi tahu bahwa keduanya seharusnya tetap sinkron.

### 10.22 Bug: guardrail (Lapisan 2) lolos oleh kata pendek "mal"/"rp" sebagai substring kata umum

**Gejala**: ditemukan lewat audit lanjutan terhadap `ChatbotGuardrailVerifier` (bukan laporan produksi) — heuristik fallback "balasan >150 karakter tanpa kata kunci domain zakat = kemungkinan LLM melantur/jailbreak" ([ChatbotGuardrailVerifier.php](../app/Services/Chatbot/ChatbotGuardrailVerifier.php)) memakai `str_contains()` polos, bukan pencocokan kata utuh. Dibuktikan lewat balasan buatan yang sengaja 100% di luar topik zakat (tidak menyebut satu pun kata domain sungguhan): balasan yang memuat kata "formal"/"normal"/"optimal"/"malam" **lolos tanpa diblokir** karena semuanya mengandung substring "mal" (salah satu kata kunci domain), dan balasan yang memuat "terperinci" lolos di mode `zakat_mal_consultation` karena mengandung substring "rp" (kata kunci domain khusus mode itu).

**Akar masalah**: kata kunci domain di daftar `$domainKeywords` dicek dengan `str_contains($lowerReply, $keyword)` — cocok kalau muncul di **mana pun** dalam teks, termasuk sebagai potongan kata lain. "mal" (3 huruf) dan "rp" (2 huruf) cukup pendek untuk muncul sebagai substring berbagai kata umum bahasa Indonesia/Inggris sehari-hari yang tidak ada hubungannya dengan zakat.

**Perbaikan** ([ChatbotGuardrailVerifier.php](../app/Services/Chatbot/ChatbotGuardrailVerifier.php)): menambahkan method `containsWholeWord()` — pola yang sama persis dengan yang sudah dipakai `KnowledgeRetriever::containsWholeWord()` — dan menggantikan `str_contains()` di pengecekan `$domainKeywords` dengan pencocokan kata utuh ini.

**Verifikasi**: 2 test regresi baru (`test_domain_keyword_heuristic_is_not_fooled_by_short_keyword_substrings`) memastikan balasan buatan tanpa konten domain sungguhan tetap terblokir walau memuat kata-kata yang mengandung "mal"/"rp" sebagai substring. Test lama yang memverifikasi balasan sah dengan "Rp1.000.000" (memakai kata kunci lain, "penghasilan", untuk lolos) tetap hijau — perbaikan ini tidak menyempitkan cakupan untuk balasan yang memang sah. Regresi penuh tetap bersih.

**Catatan soal Bab 11 poin 1**: ini adalah celah yang **berbeda** dari keterbatasan "parafrase melewati guardrail" yang sudah didokumentasikan di Bab 11 poin 1 — celah itu soal balasan yang memang tidak memakai kata terlarang apa pun (batasan desain yang disengaja, bukan bug), sementara celah ini soal heuristik yang seharusnya menangkap kasus itu (tidak ada kata domain sama sekali) tapi gagal karena kesalahan pencocokan string, bukan keterbatasan desain.

### 10.23 Verifikasi akhir: audit sistematis + evaluasi perilaku nyata pasca-perbaikan Bab 10.19-10.22

**Konteks**: setelah 4 bug di atas diperbaiki (Bab 10.19-10.22, ditambah 5 kasus pembajakan Bab 10.20 dihitung sebagai satu kelompok), dilakukan dua lapis verifikasi sebelum menganggap sesi perbaikan ini selesai: (1) audit manual manual seluruh file `app/Services/Chatbot/` yang tersisa (16 file) untuk pola bug serupa, dan (2) evaluasi perilaku end-to-end dengan API key asli, bukan cuma test unit/feature.

**Audit manual seluruh direktori chatbot**: `ChatbotSentinelParser`, `ChatbotZakatMalGuide`, `ChatbotCalculatorService`, `ChatbotPublicDataResponder`, `ChatbotSafetyClassifier`, `ChatbotStreamParser`, `ChatbotSentimentDetector`, `ChatbotLanguageDetector`, dan `getSystemInstruction()` dibaca penuh — tidak ditemukan bug baru yang clear-cut (bukti input→output salah yang konkret) di luar 9 yang sudah didokumentasikan. Dua catatan berseverity rendah (bukan bug, batasan desain): `ChatbotSentimentDetector` memakai `str_contains` biasa untuk kata penanda nada (frustrasi/bingung) tanpa whole-word matching, tapi dampaknya cuma ke nada balasan, bukan rute/isi jawaban; `ChatbotLanguageDetector` punya potensi salah deteksi bahasa untuk pesan sangat pendek yang memakai "no" sebagai singkatan "nomor" — trade-off inheren classifier rasio-kata pendek, menghapusnya akan merusak deteksi balasan Inggris singkat yang genuine.

**Evaluasi perilaku nyata (2026-08-07, API key asli, dijalankan manual sesuai Bab 11 poin 6)**:

| Command | Hasil |
|---|---|
| `chatbot:eval-behavior` | **19/19 skenario lolos** (100%) |
| `chatbot:eval-rag` | **Precision 1,0 / Recall 1,0 / F1 1,0** — 41 kasus positif semua menemukan topik tepat, 20 kasus out-of-scope semua kosong sesuai harapan |
| `chatbot:eval-safety` | Akurasi tier "confident" (satu-satunya tier yang benar-benar memblokir balasan) **1,0** pada threshold 0,66 — threshold sweep independen mengonfirmasi ulang titik 0,66 sebagai titik 0% false-positive `in_domain`, sama persis dengan `CONFIDENT_THRESHOLD` yang sudah dipakai kode (Bab 20.2) |

Ketiga hasil ini konsisten dengan yang sudah didokumentasikan sebelumnya (tidak ada regresi dari perbaikan Bab 10.19-10.22) dan menjadi bukti tambahan bahwa perbaikan hari ini tidak mengorbankan cakupan retrieval atau perilaku multi-turn yang sudah tervalidasi.

**Regresi test suite penuh**: `php artisan test` **326/326** (naik dari 258/258 di titik Bab 10.18 — selisih 68 test mencakup seluruh sesi Bab 13-18 di antaranya, plus 17 test baru dari Bab 10.19-10.22: 13 dari `ChatbotActionDetectorTest`, 1 dari `ChatbotConversationContextTest`, 1 dari `ChatbotApiTest`, 2 dari `ChatbotGuardrailVerifierTest`).

---

## 11. Keterbatasan yang Diketahui (Untuk Bab Batasan Penelitian)

1. **Guardrail keyword (Lapisan 2) bisa dilewati parafrase** yang tidak memakai kata terlarang eksplisit dan tetap di bawah 150 karakter. Terdokumentasi dan dibuktikan test, bukan diklaim sebagai perlindungan penuh terhadap prompt injection.
2. **Safety classifier (Lapisan 3) punya cakupan "confident" sekitar 7,5%** (diperbarui Bab 20.2 — turun dari ~28% setelah beralih ke k-NN dan re-tuning threshold demi false-positive `in_domain` 0%) — sisanya jatuh ke ambiguous/no_match dan tidak mendapat keputusan tegas dari lapisan ini (fail-open, mengandalkan Lapisan 1–2). Trade-off precision-vs-recall yang disengaja, bukan kegagalan tak disadari; tier "ambiguous" (mayoritas kasus) saat ini murni pass-through, belum dimanfaatkan sebagai sinyal apa pun — lihat Bab 12 poin 1 (baru).
3. **Kategori `unsupported_fatwa` sudah membaik dari 35,0% ke 10,0% error rate** (Bab 20.2, hasil switch 1-NN → k-NN berbobot) — perbaikan lanjutan sebelumnya (Bab 18, sekadar menambah jumlah contoh dataset) terbukti **tidak** memperbaiki angka ini; yang berpengaruh nyata adalah mengganti algoritma klasifikasinya, bukan volume datanya.
4. **Dataset reference untuk safety classifier tercampur dua gaya penulisan** (pertanyaan user + balasan bot, lihat Bab 10.4) setelah perbaikan celah distribusi — cukup untuk menutup gap yang ditemukan, tapi rasio 5 contoh reply-style per 25-40 contoh question-style per kategori masih kecil; menambah lebih banyak contoh reply-style adalah perbaikan lanjutan yang jelas kalau classifier ini dikembangkan lebih jauh.
5. **Refresh embedding cache KB bersifat sinkron** — menyimpan entri KB memblokir request admin selama kira-kira (jumlah entri aktif × latensi API embedding). Aman di skala 54 entri saat ini, perlu dipertimbangkan ulang (batching/queue) kalau KB tumbuh jadi ratusan entri.
6. **Evaluasi `eval-behavior`, `eval-behavior-rubric`, dan `eval-safety` bergantung pada API key asli** dan bersifat nondeterministik (jawaban LLM bisa sedikit berbeda antar run) — dijalankan manual sebagai regression check sebelum perubahan besar ke prompt, bukan gate CI otomatis seperti unit test biasa.
7. **Rubric kualitas konsultatif (Bab 9.3) butuh skor manual manusia** — sistem menyediakan bahan evaluasinya (balasan Zakky per skenario dalam format tabel), tapi penilaian 1–5 per aspek tetap memerlukan evaluator manusia (dosen/panitia/peneliti), bukan otomatis.
8. **Harga emas acuan nishab (Bab 6.1) tidak real-time** — `gold_price_per_gram` adalah input manual admin per periode (default mengikuti SK BAZNAS No. 15/2026 saat dokumen ini ditulis), bukan hasil tarikan API harga emas harian. Kalau periode berjalan lama tanpa admin memperbarui angkanya sementara harga emas pasar bergerak signifikan, nishab yang dipakai sistem bisa menyimpang dari acuan resmi terbaru.
9. **`ChatbotActionDetector` (fast-path, Bab 10.13–10.15) tetap berbasis keyword-matching murni, tanpa toleransi typo/sinonim/negasi** — trade-off desain yang disengaja (bukan kegagalan tak disadari): setelah rangkaian bug pembajakan ditemukan, keputusannya adalah mempersempit cakupan fast-path (anchor kata yang lebih spesifik/ketat) daripada membangun NLU sendiri di lapisan ini. Alasannya, classifier berbasis embedding pun terkenal lemah untuk kasus negasi, dan menambahkannya ke fast-path menghilangkan tujuan fast-path itu sendiri (instan, tanpa panggilan API). Konsekuensinya: pesan dengan topik ambigu sekarang lebih sering lolos ke AI (bukan dijawab instan lewat KB/kalkulator) — trade-off presisi lebih tinggi ditukar dengan sedikit lebih banyak pesan yang butuh panggilan AI.

---

## 12. Rekomendasi Pengembangan Lanjutan

1. ~~Tambah contoh dataset di kategori `unsupported_fatwa` dan `privacy_risk`~~ — **selesai dicoba, terbukti tidak cukup** (Bab 18: dataset diperluas, error rate `unsupported_fatwa` tetap flat 23,3%). Perbaikan yang benar-benar berdampak ternyata mengganti algoritma klasifikasi 1-NN → k-NN berbobot (Bab 20.2, error rate turun ke 10,0%). **Rekomendasi baru menggantikan poin ini**: manfaatkan tier "ambiguous" (Bab 20.2, ~79% dari total kasus, saat ini murni pass-through/no-op) sebagai sinyal tambahan — misalnya untuk menaikkan kehati-hatian prompt LLM atau menandai giliran untuk tinjauan manual — alih-alih membiarkannya tidak berkontribusi apa pun ke sistem.
2. Terapkan `chatbot:eval-safety` juga terhadap **pesan masuk user** (bukan cuma balasan LLM) — dataset sudah mendukung ini (banyak contoh `prompt_injection` ditulis dari sudut pandang pesan user), tapi integrasinya saat ini baru menyasar balasan akhir untuk menghindari penambahan panggilan embedding di jalur kritis.
3. Pertimbangkan caching/batching untuk refresh embedding KB kalau jumlah entri bertambah signifikan.
4. Lengkapi evaluasi kuantitatif (Bab 9.1, 9.2, 9.4/Bab 20.2) dengan evaluasi kualitatif oleh responden manusia (dosen pembimbing, panitia masjid, atau sampel jamaah) menggunakan rubric di Bab 9.3 — kombinasi keduanya (terukur otomatis + manusia) memberi validitas yang lebih kuat untuk klaim "chatbot ini membantu" di skripsi.
5. `ChatbotLanguageDetector` (Bab 10.16) memakai daftar kata penanda Inggris yang tetap dan rasio ambang 30% — cukup rapuh untuk kalimat pendek atau campuran ID/EN (dibuktikan tidak sengaja saat menulis test regresi Bab 10.16, sebuah kalimat Inggris wajar gagal terdeteksi sebagai EN). Bukan bug aktif yang berdampak sekarang (fallback ke ID tetap aman karena versi ID lebih lengkap), tapi layak diperkuat kalau basis pengguna berbahasa Inggris bertambah.
6. **Poin 1 dari rencana penghematan token/biaya (Bab 20.1) masih ditahan**: menurunkan `OPENAI_CHAT_MODEL` (tier default/menengah) ke model lebih murah butuh data nyata dari logging `route_reason` dulu sebelum dieksekusi — cek distribusi trafik lewat `chatbot:diagnostics` setelah cukup lama berjalan di produksi.

---

## 13. Observability: `ChatbotDiagnostics` (Layer-Tagged Diagnostic Logging)

**Latar belakang**: sebelum ini, logging chatbot tersebar tidak konsisten — sebagian lapisan (`ChatbotGuardrailVerifier`, `ChatbotSafetyClassifier`, `ChatbotSentinelParser`) **sama sekali tidak punya jejak log** saat memblokir/menolak sesuatu, padahal justru titik-titik itu yang paling penting untuk didiagnosis kalau ada perilaku aneh di produksi. Lapisan lain (`KnowledgeRetriever`, provider LLM) sudah punya `Log::` tapi bercampur dengan log Laravel umum di `storage/logs/laravel.log`, tanpa penanda "lapisan mana" yang konsisten untuk di-grep.

**Desain**: `ChatbotDiagnostics` ([ChatbotDiagnostics.php](../app/Services/Chatbot/ChatbotDiagnostics.php)) — helper statis tipis dengan 3 method (`info`/`warning`/`error`), masing-masing mewajibkan nama **layer** (konstanta: `action_detector`, `knowledge_retriever`, `llm_provider`, `sentinel_parser`, `guardrail`, `safety_classifier`, `orchestrator`) dan nama **event**. Semua entri masuk ke channel log terpisah `chatbot` ([config/logging.php](../config/logging.php), file harian `storage/logs/chatbot-YYYY-MM-DD.log`, retensi 14 hari) — terisolasi dari log Laravel umum supaya bisa di-grep per lapisan tanpa noise.

**Lapisan yang diinstrumentasi** (titik-titik yang sebelumnya bisu, sekarang tercatat):
- **Guardrail** (Lapisan 2): `blocked_by_keyword` (kata kunci mana yang cocok) dan `blocked_by_no_domain_keyword_heuristic`.
- **Safety Classifier** (Lapisan 3): `blocked` (kategori + skor kemiripan) dan `skipped_fail_open` (kapan lapisan ini tidak memberi proteksi sama sekali, mis. API embedding tidak tersedia).
- **Sentinel Parser**: `malformed_json`, `rejected_non_numeric_value` (Bab 10.10), `rejected_negative_value`, `rejected_implausible_value`, `insufficient_data_to_anchor_a_section` (Bab 10.8).
- **Knowledge Retriever**: `embedding_generation_failed`, `fell_back_to_keyword_search`, `no_cached_embeddings_available`, `empty_message_for_semantic_search`.
- **Orchestrator**: `handled_fast_path`/`handled_ai_path`/`handled_ai_path_stream` (dengan `duration_ms` per giliran) dan `unhandled_exception` (menyertakan nama class exception + `file:line` — bukan cuma pesan generik, supaya akar masalah bisa langsung dilacak ke lapisan yang benar-benar melempar error, bukan cuma "ada yang gagal di suatu tempat").

**Command ringkasan**: `php artisan chatbot:diagnostics {--days=1}` ([ChatbotDiagnosticsSummary.php](../app/Console/Commands/ChatbotDiagnosticsSummary.php)) — mem-parsing file log harian, menghitung kemunculan tiap kombinasi (layer, event, level), menampilkannya sebagai tabel terurut dari yang paling sering, dan menyorot khusus baris berlevel WARNING/ERROR di akhir sebagai "titik paling relevan untuk dicek duluan". Contoh output nyata (dari log selama satu sesi test suite lokal, `--days=1`):

```
| Layer               | Event                                 | Level   | Jumlah |
| knowledge_retriever | embedding_generation_failed           | WARNING | 257    |
| orchestrator        | handled_ai_path                       | INFO    | 72     |
| safety_classifier   | skipped_fail_open                     | INFO    | 56     |
| guardrail           | blocked_by_keyword                    | WARNING | 43     |
| sentinel_parser     | rejected_non_numeric_value            | WARNING | 7      |
| orchestrator        | unhandled_exception                   | ERROR   | 5      |
```

**Verifikasi**: 3 test baru di `ChatbotApiTest.php` memverifikasi tiap titik instrumentasi kritis benar-benar menulis ke file log sungguhan (bukan mocking `Log` facade — pendekatan itu berisiko mengganggu panggilan `Log::` lain yang tidak terkait di sepanjang pipeline) — `test_chatbot_guardrail_blocks_off_topic_reply` (ditambah assertion log), `test_sentinel_parser_rejection_is_logged_with_layer_tag`, `test_orchestrator_exception_is_logged_with_layer_tag`. Pembacaan log dibatasi ke bagian yang ditulis **setelah** test dimulai (via offset ukuran file), supaya tidak salah lolos akibat entri dari test lain yang kebetulan menulis string serupa ke file harian yang sama. Ditambah `ChatbotDiagnosticsSummaryTest.php` untuk command ringkasannya sendiri. Regresi penuh tetap bersih: `php artisan test` 262/262.

**Batasan yang disadari**: ini logger berbasis file, bukan dashboard/alerting — cukup untuk kebutuhan "telusur manual setelah insiden" (`php artisan chatbot:diagnostics`), belum untuk pemantauan real-time atau notifikasi otomatis saat error/block-rate melonjak. Sudah dicatat sebagai rekomendasi pengembangan lanjutan terpisah (Bab 12) kalau kebutuhan itu muncul.

---

## 14. Pengerasan Struktural: `ChatbotCitation` (Value Object) + Konsolidasi Test Paritas Prompt

Setelah audit kualitas arsitektur (dipicu pertanyaan "sudah level enterprise yang rapi belum?"), tiga celah struktural diidentifikasi sebagai akar penyebab berulang dari bug-bug di Bab 10: (1) data lintas-layer masih array asosiatif mentah tanpa kontrak eksplisit, (2) tidak ada satu titik verifikasi paritas aturan system prompt ID/EN, (3) tidak ada tooling anti-duplikasi. Dua yang pertama ditutup di bagian ini; yang ketiga **sengaja tidak dikerjakan** — dijelaskan di akhir bagian.

### 14.1 `ChatbotCitation`: value object pengganti array `['id' => ..., 'label' => ...]`

**Alasan**: bug Bab 10.17 (citations menampilkan "Acuan: undefined") berakar dari mismatch nama field (`source_label` vs `label`) yang tidak pernah ketahuan sampai payload JSON dibaca manual — PHP tidak punya cara memberi peringatan untuk key array yang salah nama. `ChatbotCitation` ([ChatbotCitation.php](../app/Services/Chatbot/ChatbotCitation.php)) — class readonly dengan properti `id`/`label` bertipe eksplisit, plus factory `fromKnowledgeArray()` sebagai **satu-satunya** tempat terjemahan `source_label` → `label` boleh terjadi. `ChatbotResponse::$citations` sekarang bertipe `ChatbotCitation[]` (didokumentasikan lewat PHPDoc `@param`/`@var`, PHP tidak mendukung generic array secara native), dan `toArray()` memanggil `->toArray()` di tiap elemen saat serialisasi ke JSON.

Empat titik yang tadinya membangun array sitasi mentah langsung ([ChatbotActionDetector.php](../app/Services/Chatbot/ChatbotActionDetector.php), [ChatbotPublicDataResponder.php](../app/Services/Chatbot/ChatbotPublicDataResponder.php), 2 titik di [ChatbotOrchestrator.php](../app/Services/Chatbot/ChatbotOrchestrator.php)) diubah memakai `ChatbotCitation`.

**Bug tambahan ditemukan sambil lalu**: constructor `ChatbotResponse` menerima parameter `$actions` tapi **mengabaikannya sepenuhnya** — `$this->actions = [];` di-hardcode, bukan `$this->actions = $actions;`. Saat ini tidak berdampak (belum ada caller yang mengisi actions dengan data nyata — diverifikasi lewat `grep` ke seluruh pemanggilan `ChatbotResponse::success()`), tapi tetap bug nyata di level constructor: parameter yang diterima lalu didiam-diamkan dibuang. Diperbaiki sekalian.

**Verifikasi**: `ChatbotCitationTest.php` (factory method, fallback label, bentuk `toArray()`) dan `ChatbotResponseTest.php` (parameter `$actions` tidak lagi dibuang, citations tetap serialisasi dengan key `label`).

### 14.2 Konsolidasi test paritas aturan keras ID/EN

Sebelumnya, verifikasi "aturan X ada di kedua bahasa" tersebar sebagai assertion terpisah-pisah di beberapa test (Bab 10.11, 10.12, 10.16) tanpa satu sumber kebenaran. Dikonsolidasi jadi satu test data-provider, `test_hard_rule_is_present_in_both_language_prompts` ([ChatbotApiTest.php](../tests/Feature/ChatbotApiTest.php)) — tabel pasangan `(nama aturan, substring ID, substring EN)` untuk 7 aturan keras (never-self-calculate, skema JSON `[HITUNG:]`, konfirmasi niat, klarifikasi bruto, larangan topik lanjutan, larangan tag `[SUGGEST]`, berhenti hitung kalau sudah bayar). Kalau prompt diedit lagi nanti dan satu bahasa lupa diupdate, pesan kegagalan test langsung menyebut nama aturan dan bahasa mana yang hilang — bukan cuma "assertion failed" generik.

**Kenapa bukan restrukturisasi penuh jadi prompt builder terpisah dari string**: dipertimbangkan, tapi ditolak sebagai over-engineering untuk skala proyek ini — risiko regresi dari refactor besar terhadap string yang sudah teruji lewat `chatbot:eval-behavior` (butuh API key asli, sulit di-otomatisasi ulang) dinilai lebih mahal daripada manfaat konsolidasi tesnya sendiri, yang sudah cukup didapat lewat pendekatan data-provider di atas.

### 14.3 Yang sengaja TIDAK dikerjakan: tooling anti-duplikasi otomatis

Dari 3 celah struktural yang diidentifikasi, poin "tooling yang mencegah duplikasi kode secara otomatis" (mis. custom PHPStan rule atau static analysis khusus) **sengaja dilewati**. Pertimbangan: kompleksitas membangun dan merawat tooling semacam itu tidak sepadan untuk basis kode seukuran ini (satu aplikasi masjid, bukan monorepo multi-tim) — kelas bug duplikasi yang sudah ditemukan (Bab 10.14) cukup dicegah lewat kombinasi komentar penjelas di lokasi rawan + kebiasaan review eksplisit ("apakah aturan ini sudah ada di tempat lain?"), bukan infrastruktur baru. Ini konsisten dengan prinsip yang dipakai sepanjang dokumen ini: menambah kompleksitas cuma kalau manfaatnya sepadan dengan biaya perawatannya.

**Verifikasi keseluruhan Bab 14**: `php artisan test` 278/278.

---

## 15. Performa: Prompt Caching Otomatis + Pengukuran Cache Hit Nyata

**Latar belakang**: Bab 7.2 mencatat latensi terukur, tapi belum pernah membahas *prompt caching* — teknik menyimpan hasil pemrosesan prefix prompt yang identik antar giliran supaya tidak diproses ulang dari nol. `config/services.php` menunjukkan provider LLM yang dipakai adalah **OpenAI asli** (`base_url` default `https://api.openai.com/v1`, bukan sekadar API yang kompatibel) — dan OpenAI menerapkan **prompt caching otomatis di level platform** untuk prompt di atas ~1024 token, tanpa perlu parameter atau kode tambahan apa pun di sisi klien.

**Prompt sistem sudah melewati ambang itu**: diukur langsung, system prompt versi Indonesia (Bab 10.16) sekitar **~1.281 token** — sudah di atas ambang 1024 token OpenAI sejak sebelum sesi ini, artinya caching kemungkinan **sudah aktif secara otomatis** tanpa pernah disadari/diverifikasi.

**Struktur prompt sudah kebetulan optimal untuk ini**: caching OpenAI mencocokkan **prefix** permintaan dari awal — dan `getSystemInstruction()` (Bab 10.16) sudah menaruh bagian yang stabil (identitas, ATURAN KERAS, GAYA BICARA) di **awal** string, baru menambahkan bagian yang berubah-ubah tiap giliran (konteks KB hasil RAG, hint sentimen/koreksi/mode) di **akhir** lewat `.=`. Urutan ini persis yang dibutuhkan supaya prefix yang identik antar giliran (bagian stabil) bisa cache-hit, sementara bagian yang genuinely berbeda tiap pesan tidak ikut menggagalkan cache.

**Yang tadinya tidak terlihat**: kode sebelumnya mem-parsing `usage.prompt_tokens`/`completion_tokens`/`total_tokens` dari respons API, tapi **tidak pernah membaca** `usage.prompt_tokens_details.cached_tokens` — field yang OpenAI kirim balik untuk melaporkan berapa banyak token prompt yang benar-benar cache-hit. Tanpa ini, tidak ada cara memverifikasi "caching kemungkinan aktif" jadi "caching terbukti aktif, sekian token/giliran".

**Perbaikan** ([OpenAiChatbotProvider.php](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php)):
1. `usageMetadata()` sekarang membaca `usage['prompt_tokens_details']['cached_tokens']`, disimpan sebagai kolom `cached_tokens` baru di `ai_chat_logs` (migrasi terpisah).
2. `estimateCostUsd()` diperbaiki — sebelumnya menghitung **semua** token prompt di harga penuh, padahal OpenAI membebankan token yang cache-hit di harga diskon (~50% dari harga input segar). Tanpa perbaikan ini, begitu caching aktif, angka `estimated_cost_usd` yang tersimpan akan **melebih-lebihkan** biaya riil — bug estimasi yang baru kelihatan sekarang justru karena caching-nya diasumsikan sudah lama berjalan otomatis.

**Verifikasi**: test baru `test_chatbot_bills_cached_prompt_tokens_at_the_discounted_rate` memastikan 1024 dari 1200 token prompt yang dilaporkan cache-hit dihitung di tarif diskon, bukan tarif penuh (estimasi biaya turun dari $0,01200 jadi $0,00944 untuk skenario yang sama). Regresi penuh tetap bersih: `php artisan test` 279/279.

**Batasan yang disadari dan diakui secara jujur**: sesi ini **tidak bisa** mengukur ulang latensi end-to-end secara langsung (Bab 7.2's angka ~1.000-4.600ms) karena tidak ada akses API key produksi di lingkungan pengerjaan ini — jadi klaim "caching sudah aktif" di atas didasarkan pada penalaran dari dokumentasi resmi OpenAI (ambang 1024 token, billing model) dan struktur kode, **bukan** hasil pengukuran `cached_tokens` yang sesungguhnya dari trafik nyata. Yang sudah tersedia sekarang adalah **instrumennya**: kolom `cached_tokens` di `ai_chat_logs` dan `duration_ms` di log diagnostik (Bab 13) sudah siap dipakai untuk verifikasi nyata begitu aplikasi berjalan dengan API key produksi — tinggal jalankan `php artisan chatbot:diagnostics` atau query `ai_chat_logs` setelah beberapa hari trafik nyata untuk memastikan angka cache-hit dan latensi aktual, alih-alih hanya berasumsi dari dokumentasi vendor.

---

## 16. Audit Komponen yang Belum Pernah Disentuh: Kalkulator, Deteksi Sentimen, Rate Limiting

Setelah pertanyaan eksplisit "masih ada ruang perbaikan?", tiga komponen yang **belum pernah diaudit sama sekali** sepanjang sesi ini ditinjau: `ChatbotCalculatorService` (kalkulator fitrah/fidyah), `ChatbotSentimentDetector::isCorrectingPreviousNumber()`, dan `ThrottleChatbot` (middleware rate-limit). Ketiganya sebelum ini punya **nol test coverage**, langsung maupun tidak langsung.

### 16.1 Bug paling parah di seluruh sesi: `ChatbotCalculatorService` salah tangkap angka tahun sebagai jumlah

**Gejala**: `extractNumberFromText()` punya fallback tahap 3 — kalau tidak ada angka yang ketemu di dekat kata kunci ("orang"/"jiwa" untuk fitrah, "hari" untuk fidyah), sistem **asal ambil angka pertama di mana pun dalam pesan**. Dibuktikan langsung: pesan *"Fitrah tahun 2026 itu berapa ya per orang?"* — pertanyaan biasa soal tarif tahun ini, bukan permintaan hitung — dihitung jadi **"Fitrah untuk 2026 orang: Rp101.300.000"**. Pola sama persis untuk fidyah (*"Fidyah tahun 2026 per hari berapa ya?"* → "Fidyah untuk 2026 hari: Rp60.780.000").

**Kenapa ini paling parah dari semua temuan sesi ini**: jalur ini murni **fast-path deterministik** — tidak pernah lewat AI, tidak pernah lewat guardrail Lapisan 2/3 (Bab 8), tidak ada satu lapisan pun yang berkesempatan menangkap hasil yang salah sebelum sampai ke user. Kombinasi "kata kunci + kata terkait tahun + angka tahun" ini juga sangat mudah terpicu tanpa maksud jahat sama sekali — cuma pertanyaan wajar soal tarif.

**Perbaikan** ([ChatbotCalculatorService.php](../app/Services/Chatbot/ChatbotCalculatorService.php)): fallback "asal ambil angka pertama" dihapus total. Deteksi angka tahap 1 diperluas supaya tetap menjangkau angka yang muncul **setelah** kata kunci dalam jarak dekat (bukan cuma sebelum), plus ditambah batas plausibilitas (`MAX_PLAUSIBLE_COUNT = 1000`) sebagai lapis pertahanan tambahan — angka besar seperti tahun otomatis ditolak meski kebetulan lolos regex kedekatan, dan sistem meminta klarifikasi alih-alih menghitung dari angka yang meragukan (konsisten dengan prinsip "jangan menebak, tanya" yang dipakai di seluruh sistem ini).

**Verifikasi**: `ChatbotCalculatorServiceTest.php` (test pertama untuk class ini) — kasus digit dekat kata kunci, kata-angka ("empat orang"), dan dua kasus regresi eksplisit untuk skenario tahun yang ditemukan. Regresi penuh tetap bersih.

### 16.2 Bug: kata umum bahasa Indonesia salah dikira sinyal koreksi angka

**Gejala**: `ChatbotSentimentDetector::isCorrectingPreviousNumber()` memakai `str_contains()` (substring, bukan kata utuh) untuk daftar kata koreksi termasuk `'eh'` dan `'salah'`, dipasangkan dengan "ada angka di mana pun dalam pesan". Dibuktikan: pesan *"Apakah boleh saya bayar zakat fitrah untuk 4 orang sekaligus?"* — pertanyaan biasa, bukan koreksi — salah terdeteksi sebagai koreksi karena kata **"boleh"** mengandung substring "eh". Pola sama untuk *"Salah satu syarat zakat..."* (frasa "salah satu" = "one of", bukan soal salah-benar) dan *"Ini bukan zakat mal, tapi zakat fitrah untuk 4 jiwa"*.

**Dampak**: setiap false-positive menyisipkan `_correction_hint` ("User tampaknya sedang mengoreksi angka...") ke system prompt untuk pertanyaan yang sama sekali bukan koreksi — berpotensi membingungkan LLM di awal percakapan yang sebenarnya baru mulai, bukan mengoreksi apa pun.

**Perbaikan** ([ChatbotSentimentDetector.php](../app/Services/Chatbot/ChatbotSentimentDetector.php)): diganti dari substring matching + "angka di mana pun" jadi pencocokan **kata utuh** + **jendela kedekatan** (6 kata di kiri-kanan kata koreksi) terhadap token yang mengandung digit — otomatis menghilangkan masalah "eh" di dalam "boleh"/"oleh" (karena "boleh" ≠ kata utuh "eh") sekaligus masalah "bukan" yang jauh dari angka manapun dalam kalimat. Frasa "salah satu" dikecualikan eksplisit karena tetap lolos pencocokan kata-utuh.

**Verifikasi**: 3 kasus regresi (false-positive lama) + 3 kasus koreksi asli (termasuk pesan yang sudah dipakai test lain, `"eh salah, harusnya 12 juta bukan 10 juta"`) ditambahkan ke `ChatbotSentimentDetectorTest.php` yang sudah ada.

### 16.3 `ThrottleChatbot`: tidak ada bug logic, tapi dua celah kecil ditutup

Middleware rate-limit ini **tidak menunjukkan bug separah dua di atas** — key per-user/IP masuk akal, kedua rute chatbot (`/message`, `/stream`) sama-sama ter-cover, batas 50/menit wajar. Dua perbaikan minor:
1. Respons 429 sebelumnya tidak menyertakan header `Retry-After`/`X-RateLimit-Remaining` — klien (frontend) tidak punya cara terukur mengetahui kapan aman mencoba lagi, cuma teks "Tunggu beberapa menit". Ditambahkan.
2. Zero test coverage — ditambahkan `ThrottleChatbotTest.php` (request di bawah limit lolos + header benar, request ke-51 diblokir dengan header retry yang benar).

**Catatan operasional yang disadari, tidak diperbaiki**: `getKey()` pakai `$request->ip()` untuk user yang belum login. Kalau aplikasi di-deploy di belakang reverse proxy tanpa `TrustProxies` dikonfigurasi (defaultnya `null`, belum diset di proyek ini), semua user di belakang proxy yang sama bisa berbagi satu IP yang sama di mata Laravel — artinya satu bucket rate-limit collectively, bukan per-user. Ini murni **isu konfigurasi deployment**, bukan sesuatu yang bisa/boleh diperbaiki lewat kode tanpa tahu topologi deployment sesungguhnya (menyetel `$proxies = '*'` secara membabi buta justru downgrade keamanan kalau salah konteks) — dicatat sebagai perhatian operasional, bukan dieksekusi.

**Verifikasi keseluruhan Bab 16**: `php artisan test` 293/293.

---

## 17. Validasi Nyata Pertama: `chatbot:eval-behavior`/`eval-safety` dengan API Key Asli (2026-07-29)

Sepanjang Bab 10–16, seluruh perbaikan cuma diverifikasi lewat simulasi (`Http::fake()`, `tinker`) — kepatuhan LLM sungguhan terhadap instruksi prompt selalu dicatat sebagai "perlu dicek manual lewat `chatbot:eval-behavior`" tapi belum pernah benar-benar dijalankan. Setelah MySQL/XAMPP aktif, ketiga command evaluasi dijalankan dengan API key produksi asli — inilah validasi nyata pertama sepanjang proses pengerjaan ini.

**`chatbot:eval-safety`**: 145 kasus — top-1 accuracy 0,807, confident-tier accuracy 0,951, confident coverage 0,283, kategori terlemah `unsupported_fatwa` (error rate 0,35). **Angka ini persis cocok dengan yang sudah didokumentasikan di Bab 9.4/10.4** — konfirmasi kuat bahwa seluruh perbaikan Bab 10–16 tidak meregresi apa pun di lapisan keamanan ini.

**`chatbot:eval-behavior-rubric --markdown`**: 12 skenario berhasil menghasilkan tabel bahan skor manual. Belum diisi skor oleh evaluator manusia (di luar scope sesi ini), tapi sekilas ada dua sinyal kualitatif yang layak dicatat untuk peninjauan lanjutan: satu balasan skenario "detail" terasa terlalu defensif/pendek ("belum punya info itu"), dan beberapa closure hasil kalkulasi cukup panjang. Belum ditindaklanjuti — butuh skor rubric formal dulu untuk memastikan ini pola nyata, bukan kesan sekali baca.

**`chatbot:eval-behavior`**: 18 skenario, **15 lolos, 3 gagal**. Ketiga kegagalan ditelusuri satu per satu ke teks balasan asli (bukan ditebak) — ternyata tiga akar masalah yang **berbeda jenis**:

### 17.1 Bug di evaluator, bukan di model — "mengganti angka lama saat user mengoreksi" (2 putaran perbaikan)

**Putaran 1**: balasan asli — *"Baik, saya ganti ya: gaji Rp7,5 juta per bulan, bukan Rp75 juta, dan tabungan..."* — model **sudah benar**, secara eksplisit menyatakan nilai baru lalu menegasikan nilai lama untuk kejelasan (gaya yang justru bagus). Tapi ekspektasi test (`ChatbotBehaviorDataset.php`) memakai `!preg_match('/75\s*juta/i', $reply)` — larangan blanket, menganggap SEGALA kemunculan "75 juta" sebagai kegagalan, termasuk saat kemunculannya ada di dalam frasa negasi "bukan Rp75 juta" sendiri. Diperbaiki dengan menghapus frasa negasi dulu sebelum memeriksa sisa teks — plus tetap mensyaratkan kata pengakuan (`ganti`/`koreksi`/`catat`/`ubah`) di dekat nilai baru.

**Putaran 2 (run ulang setelah putaran 1)**: 17 dari 18 skenario lolos — **kedua perbaikan 17.2 dan 17.3 terkonfirmasi berhasil di trafik nyata**, tapi skenario koreksi ini **masih gagal**, dengan balasan yang beda lagi: *"Baik, gaji yang benar Rp7.500.000 per bulan, dan tabungan Rp10.000.000. Apak..."* — model tetap benar mengganti nilai, tapi memakai frasa **"gaji yang benar"**, bukan salah satu dari 4 kata kunci pengakuan yang disyaratkan putaran 1 (`ganti`/`koreksi`/`catat`/`ubah`). Ini bukti nyata: daftar kata kunci untuk "mendeteksi pengakuan koreksi" secara inheren rapuh — terlalu banyak cara valid berbahasa Indonesia untuk mengumumkan "nilai yang benar adalah X".

**Perbaikan final**: `expect` closure direstrukturisasi total — bukan lagi mencari kata kunci pengakuan (proxy yang rapuh), tapi langsung memeriksa **substansi** yang sebenarnya diminta `expect_description`: apakah nilai baru (`7,5 juta` / `Rp7.500.000`) ada, DAN nilai lama (`75 juta`) tidak lagi muncul tanpa negasi. Pola nilai baru sengaja mensyaratkan pemisah eksplisit antara "7" dan "5" (`7[,.]5`, bukan `7[,.]?5`) — supaya tidak salah cocok dengan angka lama "75" itu sendiri (pemisah opsional bikin "75" ikut kecocokan sebagai "7.5").

**Verifikasi**: diuji terhadap 3 kasus — dua balasan asli dari kedua putaran (harus lolos) plus satu balasan hipotetis yang benar-benar tidak mengoreksi ("gaji Rp75 juta" tetap dipakai, harus gagal) — dipastikan lewat `ChatbotBehaviorDatasetTest.php` baru (test unit langsung untuk closure ini, bukan cuma lewat `chatbot:eval-behavior` yang butuh API key asli setiap kali mau verifikasi).

### 17.2 Bug prompt nyata — instruksi klarifikasi bruto di-generalisasi berlebihan oleh model

Balasan asli: *"Terima kasih. Untuk bisa diproses, saya perlu pastikan satu hal: gaji Rp2.000..."* — pada skenario di mana user cuma bilang "gaji 2 juta/bulan" (angka polos, **tidak pernah** bilang "bersih"/"kotor") dan bahkan sudah eksplisit bilang "Iya sudah benar semua, tolong hitung sekarang" di giliran berikutnya. Model tetap berhenti untuk menanyakan klarifikasi bruto/bersih.

**Diagnosis**: instruksi Bab 10.11 ("Jika user menyebut gajinya sebagai 'gaji bersih'... klarifikasi dulu") secara desain seharusnya cuma trigger kalau user **eksplisit** memakai kata "bersih"/"take home pay" — tapi model tampaknya men-generalisasi jadi "selalu konfirmasi bruto/kotor untuk setiap sebutan gaji", bahkan mengabaikan konfirmasi eksplisit user untuk lanjut menghitung.

**Perbaikan** ([OpenAiChatbotProvider.php](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php)): ditambahkan klausa eksplisit di kedua bahasa — kalau user **tidak** menyebutkan bersih/kotor sama sekali, ANGGAP itu sudah angka bruto dan **lanjutkan**, jangan tanya klarifikasi yang tidak pernah dipicu apa pun dari user.

### 17.3 Gap prompt yang belum pernah ditutup — follow-up ubah variabel setelah hasil

Balasan asli: *"Baik, tabungannya saya ubah dari Rp50 juta menjadi Rp100 juta. Data lain..."* — model mengakui perubahan tapi **tidak langsung mengeluarkan `[[HASIL]]`** pada balasan yang sama; sepertinya berhenti dulu untuk konfirmasi data lain yang sebenarnya tidak berubah. Ditelusuri: poin ini sudah ada di spesifikasi perilaku (`chatbot-behavior-notes.md`, poin 47) sejak lama, tapi **tidak pernah benar-benar diterjemahkan jadi instruksi eksplisit** di system prompt — dicek langsung, tidak ada satu kalimat pun soal ini sebelum perbaikan ini.

**Perbaikan**: ditambahkan aturan eksplisit baru di kedua bahasa — kalau user follow-up dengan mengubah satu variabel setelah hasil sudah keluar, langsung hitung ulang dan keluarkan `[HITUNG:]` lagi, tanpa minta konfirmasi data lain yang tidak berubah.

### Ringkasan Bab 17

**Verifikasi**: 2 aturan baru ditambahkan ke tabel paritas ID/EN yang sudah ada (`test_hard_rule_is_present_in_both_language_prompts`, Bab 14.2), plus `ChatbotBehaviorDatasetTest.php` baru untuk closure koreksi angka. Regresi penuh tetap bersih: `php artisan test` 298/298.

**Yang sudah benar-benar terkonfirmasi dari trafik nyata** (bukan cuma "instruksi ada di prompt"): setelah perbaikan 17.2 dan 17.3, `chatbot:eval-behavior` run kedua naik dari **15/18 ke 17/18** — kedua perbaikan itu terbukti bekerja di percakapan sungguhan dengan model asli, bukan cuma lolos test string-matching. Satu-satunya sisa kegagalan (17.1) sudah bukan soal perilaku model — melainkan cara `expect` closure mendeteksi perilaku itu — dan sudah diperbaiki dengan pendekatan yang jauh lebih tahan terhadap variasi bahasa alami (memeriksa substansi nilai, bukan menebak kata kunci pengakuan).

**Konfirmasi final (2026-07-29, run ketiga)**: `chatbot:eval-behavior` — **18/18 skenario lolos, 0 gagal, exit code 0**. Ini titik pertama sepanjang seluruh proses pengerjaan (Bab 1–17) di mana perilaku multi-turn Zakky tervalidasi **penuh** terhadap model LLM sungguhan dengan API key produksi asli — bukan lagi simulasi `Http::fake()`/`tinker`, dan bukan lagi cuma "instruksi ada di kode prompt". Bersamaan dengan `chatbot:eval-safety` yang angkanya persis cocok dengan dokumentasi sebelumnya (Bab 17, paragraf pembuka), ini menutup gap validasi terbesar yang berulang kali dicatat di seluruh dokumen ini (Bab 11 poin 6, dan penilaian jujur di percakapan pengerjaan) sebagai "belum pernah benar-benar dibuktikan, cuma diasumsikan lewat test string-matching".

**Pembelajaran metodologis Bab 17**: dua dari tiga "kegagalan" pertama yang tampak seperti bug perilaku model ternyata adalah **bug di alat ukurnya sendiri** (evaluator dataset), bukan di sistem yang diukur — dan bahkan setelah diperbaiki "putaran pertama", satu di antaranya masih butuh putaran kedua karena perbaikan awal masih menebak pola bahasa yang sempit. Ini menegaskan pelajaran yang sudah dicatat di Bab 10.3 sebelumnya: evaluasi otomatis butuh divalidasi lagi lapisannya sendiri (apakah ekspektasinya benar-benar mengukur yang dimaksud, dan tahan terhadap variasi frasa alami), bukan langsung dipercaya sebagai kebenaran mutlak begitu satu run selesai — dan kadang butuh lebih dari satu putaran verifikasi-perbaikan sebelum benar-benar tuntas.

---

## 18. Perluasan Dataset `unsupported_fatwa` (Rekomendasi Bab 12 Poin 1, Dikerjakan)

**Latar belakang**: Bab 9.4/10.4 mencatat kategori `unsupported_fatwa` punya error rate tertinggi di leave-one-out cross-validation (35,0%), paling sering rancu dengan `in_domain` — dijelaskan sebagai "secara struktur kalimat mirip pertanyaan case-consultation biasa, bedanya di nada 'menuntut vonis pasti' yang lebih sulit dipisahkan lewat embedding semantik murni." Dataset kategori ini juga paling kecil (20 contoh) dibanding kategori lain (`in_domain` 40, `out_of_scope` 30).

**Pendekatan yang dipilih**: bukan sekadar menambah jumlah, tapi menambah **variasi topik dengan nada tegas yang konsisten** di sisi `unsupported_fatwa` (10 contoh baru — 7 gaya pertanyaan, 3 gaya balasan bot, topik baru: NFT, saham, bonus tahunan, bunga tabungan), **dipasangkan** dengan menambah `in_domain` (6 contoh baru) di topik yang **sengaja sama** (gono-gini/cerai, crypto, riba, judi, zakat mal terlewat, trading forex) tapi dibingkai sebagai pertanyaan wajar, bukan tuntutan vonis. Tujuannya: classifier nearest-neighbor punya sinyal lebih kaya untuk belajar membedakan berdasarkan **nada**, bukan cuma kebetulan topiknya beda dari contoh lama.

**Dataset sekarang**: `in_domain` 40→46, `unsupported_fatwa` 20→30, total dataset 145→161 contoh.

**Efek samping yang ditemukan dan diperbaiki**: `ChatbotSafetyClassifierTest.php` ternyata hardcode index `40` sebagai asumsi "batas akhir contoh in_domain" saat membangun reference array sintetis untuk test cosine-similarity — begitu `inDomainCases()` bertambah jadi 46, index itu diam-diam menunjuk ke entri `in_domain` lain, bukan lagi entri `out_of_scope` pertama seperti yang test itu asumsikan. Diperbaiki jadi index 46, dengan komentar eksplisit bahwa angka ini terikat ke jumlah `inDomainCases()` dan berisiko drift lagi kalau dataset bertambah lagi tanpa mengecek test ini.

**Verifikasi yang SUDAH bisa dilakukan tanpa API key**: struktur dataset (jumlah per kategori, tidak ada duplikat/typo fatal) dan seluruh 298 test regresi tetap bersih.

**Yang BELUM bisa diverifikasi di sesi ini (butuh API key + langkah manual)**: apakah perluasan ini benar-benar menurunkan error rate `unsupported_fatwa`. Langkah yang perlu dijalankan manual:
1. `php artisan chatbot:cache-safety-embeddings` — regenerasi embedding untuk seluruh 161 contoh (termasuk yang lama, karena cache lama cuma untuk 145 entri).
2. `php artisan chatbot:eval-safety` — ukur ulang leave-one-out cross-validation, bandingkan error rate `unsupported_fatwa` sebelum (35,0%) vs sesudah perluasan ini.

Angka hasil langkah 2 di atas **belum ada** di dokumen ini — akan diisi setelah dijalankan, konsisten dengan disiplin Bab 17: tidak mengklaim perbaikan berhasil sebelum ada bukti dari run nyata.

---

## 19. Audit Konten Knowledge Base: Kata Kunci Tumpang Tindih + Bug Distribusi Perbaikan Konten

Ditinjau lewat pertanyaan eksplisit "ada yang janggal di knowledge?" — bukan cuma `KnowledgeRetriever` (sudah diaudit, solid), tapi **konten** 54+ entri KB itu sendiri, dan mekanisme perbaikannya sampai ke database sungguhan.

### 19.1 28 kata kunci dipakai lebih dari satu entri — 1 di antaranya presisi rendah

Diaudit programatik seluruh 454 kata kunci di `KnowledgeBaseSeeder.php`: 28 dipakai oleh lebih dari satu entri. Mayoritas **disengaja dan wajar** (mis. "zakat fitrah" dipakai baik entri umum `jenis-zakat` maupun entri spesifik `zakat-fitrah` — keduanya memang relevan untuk query itu). Tapi satu pasangan menunjukkan tumpang tindih presisi rendah: `cara-bayar-zakat` (entri umum metode pembayaran) dan `pembayaran-cek` (entri khusus cek/giro) berbagi **4 kata kunci identik** (`cek`, `cheque`, `giro`, `bilyet giro`) — bikin jalur fallback keyword-scoring (dipakai kalau embedding API gagal) berpotensi salah mengutamakan entri umum untuk query yang jelas-jelas spesifik soal cek.

**Perbaikan**: 4 kata kunci itu dihapus dari `cara-bayar-zakat`, disisakan cuma di `pembayaran-cek` — entri umum tetap punya kata kunci sendiri yang cukup (`cara bayar zakat`, `metode pembayaran`, dst.) tanpa perlu bersaing untuk kasus yang lebih tepat dijawab entri spesifik. Ditambahkan kasus baru ke `ChatbotEvalDataset.php` ("Bisa bayar zakat pakai cek atau giro tidak?" → `pembayaran-cek`) sebagai regression guard.

### 19.2 Bug distribusi: perbaikan konten Bab 6.2 bisa saja belum pernah sampai ke database nyata

**Akar masalah**: `KnowledgeBaseSeeder::run()` sengaja memakai `firstOrCreate`, bukan `updateOrCreate` — supaya re-run seeder tidak menimpa editan admin lewat panel `/internal/knowledge-base`. Konsekuensi yang baru disadari: **perbaikan teks Bab 6.2** (mengubah jawaban `catatan-metodologi-zakat` dan `zakat-penghasilan-potongan-pajak-bpjs` dari "tergantung lembaga, silakan konfirmasi ke panitia" jadi pernyataan eksplisit "Masjid An-Nur pakai bruto") **cuma mengubah file seeder** — kalau kedua baris itu sudah ter-seed ke database sebelum perbaikan itu dibuat, re-run seeder **tidak akan mengoreksinya**. Chatbot bisa saja masih menjawab dengan narasi lama meski kode sumbernya sudah benar, tanpa ada tanda kesalahan apa pun di level kode.

**Perbaikan**: migration baru [2026_07_29_010000_sync_bruto_methodology_kb_content.php](../database/migrations/2026_07_29_010000_sync_bruto_methodology_kb_content.php) — patch data sekali-jalan yang meng-update kedua baris itu (by slug) ke teks final Bab 6.2, terlepas dari kapan baris itu pertama kali ter-seed. Pola yang sama seperti migration `delete_stale_pre_consolidation_knowledge_base_rows` yang sudah ada sebelumnya — pola yang sudah dikenal proyek ini untuk "hotfix" data KB, bukan mekanisme baru. `down()` sengaja kosong (mengembalikan ke teks ambigu lama = mereproduksi bug yang sudah dikonfirmasi, bukan keadaan netral) — didaftarkan ke allowlist test kebijakan migration (`MigrationPolicyTest.php`) yang mewajibkan alasan eksplisit untuk `down()` kosong.

**Verifikasi**: `SyncBrutoMethodologyKbContentMigrationTest.php` — mensimulasikan skenario nyata (baris dengan teks lama sudah ada di database), menjalankan migration, memastikan teksnya ter-update ke versi bruto; plus kasus "migration tidak melakukan apa-apa kalau slug belum pernah ada" (aman dijalankan di database kosong/baru). Regresi penuh tetap bersih: `php artisan test` 301/301.

**Pembelajaran metodologis**: ini kelas bug baru yang belum pernah muncul di Bab 10-18 — bukan bug logika, bukan bug test, tapi **bug distribusi**: perbaikan yang benar di source code bisa gagal total mencapai efeknya di lingkungan nyata kalau mekanisme deploy-nya (dalam kasus ini, seeder yang sengaja idempotent demi melindungi data admin) tidak dirancang untuk membedakan "data lama yang perlu di-patch" dari "data yang sengaja dilindungi dari overwrite".

---

## 20. Observability Model Routing + Perbaikan `ChatbotSafetyClassifier`: dari 1-NN ke k-NN Berbobot

Ditinjau dari dua permintaan terpisah: (1) rencana penghematan token/biaya API, dan (2) tindak lanjut temuan Bab 17 bahwa `unsupported_fatwa` masih 23,3% salah klasifikasi walau dataset-nya sudah diperluas (Bab 18).

### 20.1 Logging keputusan routing model

`OpenAiChatbotProvider::selectModel()` sebelumnya memilih tier model (`fast_model`/`chat_model`/`premium_model`) tanpa jejak audit — sulit menjawab "berapa persen trafik nyata jatuh ke tier mana" tanpa membaca kode. Ditambahkan pencatatan ke channel diagnostik yang sudah ada (`ChatbotDiagnostics`, Bab 13) setiap kali model dipilih: `model_used`, `route_reason` (`premium_signal`/`fast_signal`/`default_tier`), `message_length`, `conversation_turn_count`. Dipilih menambah pada infrastruktur logging yang sudah ada, bukan tabel baru — konsisten dengan `ai_chat_logs` yang sudah mencatat token/biaya per giliran.

**Keputusan yang sengaja ditahan**: rencana menurunkan `OPENAI_CHAT_MODEL` (tier default/menengah) ke model yang lebih murah **tidak dieksekusi** tanpa bukti dari log ini dulu — tier itu menangani banyak pertanyaan yang lolos dari heuristik "premium" (≥2 kata kunci zakat mal) maupun "fast" (FAQ pendek), jadi menurunkannya berisiko menurunkan kualitas jawaban pada eval-behavior yang baru saja tuntas 18/18 (Bab 17) tanpa data nyata untuk menjustifikasinya.

### 20.2 `ChatbotSafetyClassifier`: 1-NN murni → k-NN voting berbobot

**Akar masalah**: klasifikasi sebelumnya murni 1-nearest-neighbor — kategori diambil dari SATU contoh referensi dengan cosine similarity tertinggi. Ini rapuh terhadap kebetulan: satu contoh `in_domain` ("Siapa saja yang termasuk 8 asnaf penerima zakat?") kebetulan paling mirip secara embedding dengan satu contoh `privacy_risk`, sehingga seluruh klasifikasi salah meski mayoritas contoh `in_domain` lain jauh lebih relevan.

**Perbaikan**: `classifyVector()` diubah ke k-NN (k=5) dengan voting berbobot skor — tiap tetangga terdekat memberi suara ke kategorinya sendiri, dibobotkan oleh similarity-nya sendiri, sehingga satu tetangga yang sangat dekat tetap bisa mengalahkan beberapa tetangga jauh dari kategori lain (bukan cuma voting-per-jumlah polos).

**Efek samping yang harus ditangani**: skor yang dilaporkan sekarang adalah rata-rata tertimbang, bukan skor mentah tetangga terdekat — skalanya turun secara sistematis dibanding 1-NN lama. `CONFIDENT_THRESHOLD` (dituning lewat threshold sweep `chatbot:eval-safety`, kriteria "titik terendah di mana false-positive `in_domain` = 0%") ikut dituning ulang dari **0,68 → 0,66**.

**Verifikasi (leave-one-out, 161 kasus, real API)**:

| Metrik | 1-NN (sebelum) | k-NN berbobot (sesudah) |
|---|---|---|
| Top-1 akurasi keseluruhan | 80,7% | **84,5%** |
| Error rate `unsupported_fatwa` | 23,3% | **10,0%** |
| Akurasi tier "confident" | 88,5% | 100% |
| Cakupan tier "confident" | 32,3% | **7,5%** |

**Trade-off yang jujur perlu dicatat**: akurasi keseluruhan naik dan `unsupported_fatwa` membaik drastis, tapi cakupan tier "confident" anjlok dari 32,3% ke 7,5% (12 dari 161 kasus) — konsekuensi langsung dari re-tuning threshold ke titik FP=0%. (Koreksi: laporan awal sempat salah kutip angka ini sebagai 2,5% — itu angka pada threshold 0,68 yang belum diperbarui saat command `chatbot:eval-safety` dijalankan, bukan angka pada threshold final 0,66 yang benar-benar dipakai di kode.) Artinya Layer 3 (safety classifier) sekarang jauh lebih jarang aktif memblokir balasan secara mandiri; sebagian besar kasus jatuh ke "ambiguous"/"no_match" dan bergantung pada Layer 2 (`ChatbotGuardrailVerifier`, keyword blocklist) sebagai garis pertahanan utama. Ini sejalan dengan filosofi yang sudah didokumentasikan sejak awal classifier ini dibuat (precision di atas recall — salah memblokir pertanyaan zakat yang sah dianggap lebih buruk daripada satu pesan berisiko lolos sebagai "ambiguous"), tapi patut disebut eksplisit sebagai batasan, bukan disembunyikan di balik angka akurasi yang naik.

Regresi test tetap bersih: `php artisan test` 301/301 (termasuk `ChatbotSafetyClassifierTest.php` yang tidak perlu diubah — assersinya kebetulan tetap valid di bawah k-NN karena data uji-nya hanya berisi 1-2 referensi per kategori).

---

## 21. Perbaikan Evaluator + Kebijakan Prompt: Status Haul Bukan Data Wajib untuk Estimasi Awal

**Laporan awal**: `chatbot:eval-behavior` sempat 17/18 pada skenario "data yang disebut 'tidak ada' dicatat sebagai nol" — investigasi memakai teks balasan asli (bukan tebakan) membuktikan itu **bukan** regresi kode (balasannya bukan pesan penolakan `ChatbotSafetyClassifier`, sama sekali tidak menyinggung emas), melainkan bug di evaluator: pola `ada\s+emas` (tanpa syarat tanda tanya) ikut mencocokkan kalimat rangkuman yang menegaskan kembali "emas tidak ada" — bukan pertanyaan ulang sungguhan. Diperbaiki jadi `ada\s+emas\s*\?` + tambahan pola `punya\s+emas` di [ChatbotBehaviorDataset.php:198-203](../app/Services/Chatbot/Knowledge/ChatbotBehaviorDataset.php#L198-L203).

**Audit sistematis lanjutan**: seluruh 18 skenario diperiksa untuk kelas bug yang sama (kata kunci deteksi "menanyakan ulang"/"gagal" yang ambigu dengan negasi/rangkuman). Hanya skenario "emas" yang kena — skenario lain yang punya tujuan serupa memakai pola `berapa\s+X` (kata "berapa" secara alami tidak muncul di kalimat negasi/rangkuman, berbeda dari "ada" yang ambigu). `chatbot:eval-rag` (fact-check berbasis `str_contains` polos) dan `chatbot:eval-behavior-rubric` (skor manual, tanpa pengecekan otomatis) juga dicek — tidak ada bug sejenis.

**Temuan perilaku nyata (bukan bug evaluator)**: setelah evaluator diperbaiki, dilaporkan model **kadang** memperlakukan status haul tabungan (apakah harta sudah disimpan genap setahun) sebagai data wajib tambahan dan berhenti menunggu jawabannya — padahal skema JSON `[HITUNG:]` tidak pernah punya field haul, dan sebelum perbaikan ini **tidak ada satu pun aturan keras di system prompt** yang menyebut haul sama sekali (istilah itu cuma disinggung di bagian gaya bahasa, sebagai istilah fiqih yang perlu penjelasan). Kekosongan aturan inilah akar masalahnya, sejalan dengan pola yang sudah terlihat di Bab 17 (rule bruto) — model cenderung over-generalisasi kehati-hatian ke arah "tanya dulu" kalau tidak ada instruksi eksplisit yang bilang sebaliknya.

**Perbaikan**: menambah satu ATURAN KERAS baru di kedua bahasa ([OpenAiChatbotProvider.php:360](../app/Services/Chatbot/Providers/OpenAiChatbotProvider.php) ID, blok EN sejajar) — kalau user tidak pernah menyinggung status haul sama sekali, JANGAN jadikan itu data wajib tambahan dan JANGAN tunda perhitungan; ANGGAP syarat haul terpenuhi untuk estimasi awal, tetap hitung, dan sertakan catatan singkat bahwa hasil ini mengasumsikan haul terpenuhi serta bisa dikonfirmasi ke panitia/ustadz kalau ragu. Pola ini konsisten dengan aturan "anggap bruto by default" (Bab 17) — asumsi eksplisit-dan-dikoreksi lebih baik daripada bot berhenti bertanya untuk sesuatu yang user tidak pernah anggap penting.

**Verifikasi**:
- Test paritas prompt baru: `assume haul satisfied when never raised by the user` di `hardRulePresentInBothLanguagesProvider()` ([ChatbotApiTest.php](../tests/Feature/ChatbotApiTest.php)) — memastikan rule ada di kedua bahasa, bukan cuma satu seperti gap yang pernah terjadi sebelum Bab 17.
- Skenario `eval-behavior` baru: "tidak berhenti menghitung hanya karena status haul tidak disebutkan" — user memberi semua data kecuali haul (memang tidak pernah disinggung), lalu minta dihitung; balasan terakhir harus `[[HASIL]]`, bukan pertanyaan haul.
- Regresi: `php artisan test` 302/302.

---

## Lampiran: Indeks File Kode Sumber

| Area                     | File                                                                                                                                                                                      |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Orkestrasi utama         | `app/Services/Chatbot/ChatbotOrchestrator.php`                                                                                                                                          |
| Kontrak respons/sitasi   | `app/Services/Chatbot/ChatbotResponse.php`, `ChatbotCitation.php`                                                                                                                       |
| Deteksi intent fast-path | `app/Services/Chatbot/ChatbotActionDetector.php`                                                                                                                                        |
| RAG / retrieval          | `app/Services/Chatbot/Knowledge/KnowledgeRetriever.php`, `KnowledgeEmbeddingsCache.php`                                                                                               |
| Manajemen konteks        | `app/Services/Chatbot/ChatbotConversationContext.php`                                                                                                                                   |
| Kalkulasi zakat mal      | `app/Services/Chatbot/ChatbotZakatMalGuide.php`, `ChatbotSentinelParser.php`                                                                                                          |
| Provider LLM             | `app/Services/Chatbot/Providers/OpenAiChatbotProvider.php`                                                                                                                              |
| Guardrail keyword        | `app/Services/Chatbot/ChatbotGuardrailVerifier.php`                                                                                                                                     |
| Safety classifier        | `app/Services/Chatbot/Safety/ChatbotSafetyClassifier.php`, `ChatbotSafetyDataset.php`, `ChatbotSafetyEmbeddingsCache.php`                                                           |
| Logging & privasi        | `app/Services/Chatbot/ChatbotChatLogger.php`                                                                                                                                            |
| Observability/diagnostik | `app/Services/Chatbot/ChatbotDiagnostics.php`, `app/Console/Commands/ChatbotDiagnosticsSummary.php`                                                                                    |
| Basis pengetahuan        | `database/seeders/KnowledgeBaseSeeder.php`                                                                                                                                              |
| Dataset evaluasi         | `app/Services/Chatbot/Knowledge/ChatbotEvalDataset.php`, `ChatbotBehaviorDataset.php`, `ChatbotBehaviorRubricDataset.php`, `app/Services/Chatbot/Safety/ChatbotSafetyDataset.php` |
| Command evaluasi         | `app/Console/Commands/EvaluateChatbotRag.php`, `EvaluateChatbotBehavior.php`, `EvaluateChatbotBehaviorRubric.php`, `EvaluateChatbotSafety.php`                                    |
| Frontend widget          | `resources/js/chatbot-widget.js`, `resources/views/components/chatbot-widget.blade.php`                                                                                               |
