# Desain: Layanan Konsultasi Zakat Berbasis AI (RAG)

Tanggal: 2026-07-14
Status: Disetujui, siap masuk rencana implementasi

## Masalah

Chatbot Zakky saat ini sudah punya pipeline RAG yang berfungsi (embeddings, cosine
similarity, semantic search, injeksi konteks ke system prompt, memori 3-turn). Tiga hal
yang belum ada:

1. **Sumber pengetahuan statis.** `KnowledgeRetriever` dan `KnowledgeEmbeddingsCache`
   membaca `config('zakky_knowledge')`. Amil tidak bisa menambah landasan hukum tanpa
   deploy ulang.
2. **AI pasif, bukan konsultan.** Perhitungan zakat mal digerakkan regex
   (`ChatbotZakatMalGuide::detect()`) yang hanya jalan jika semua variabel muncul dalam
   satu kalimat. AI tidak bisa menggali variabel yang kurang lewat percakapan.
3. **AI tidak benar-benar ingat.** `ChatbotOrchestrator` men-cache jawaban AI 1 jam per
   (pesan + sesi). Dalam konsultasi multi-turn, pertanyaan yang diulang setelah user
   menambah data akan dijawab dari cache dengan data lama.

## Prinsip arsitektur

> **LLM memegang percakapan. PHP memegang angka.**

LLM menggali variabel keuangan secara proaktif lintas pesan. Begitu data cukup, LLM
menerbitkan sentinel `[HITUNG:{...}]`. Orchestrator mencegat sentinel itu, menjalankan
`ChatbotZakatMalGuide::calculate()` yang deterministik, lalu menukar sentinel dengan
rincian hasil PHP. **Tidak ada satu rupiah pun yang dihitung oleh LLM.**

Alasan: nominal zakat harus bisa dipertanggungjawabkan dan di-unit-test. Aritmatika LLM
tidak menjamin keduanya.

Mekanisme sentinel dipilih karena orchestrator **sudah** punya parser bracket untuk
`[SUGGEST:...]` di jalur stream maupun non-stream. Menambah `[HITUNG:...]` adalah
perluasan mesin yang ada, bukan mesin baru — tanpa loop dua-tahap dan buffering
`tool_calls` delta yang dituntut OpenAI function calling saat streaming.

## Komponen

### 1. Knowledge Base pindah ke database

**Tabel `knowledge_bases`:**

| Kolom          | Tipe               | Catatan                                        |
|----------------|--------------------|------------------------------------------------|
| `id`           | bigint             |                                                |
| `slug`         | string, unique     | Menggantikan `id` string di config (`zakat-mal-definisi`). Dipakai sebagai kunci cache embedding. |
| `title`        | string             |                                                |
| `keywords`     | json               | Array string, untuk fallback keyword search.   |
| `answer`       | text               |                                                |
| `source_label` | string, nullable   | Landasan hukum: BAZNAS / MUI / Panduan An-Nur. |
| `actions`      | json, nullable     | Tombol saran, sudah dipakai di config saat ini. |
| `is_active`    | boolean, default 1 |                                                |
| timestamps     |                    |                                                |

Tanpa kolom `embedding_vector` — vektor sudah disimpan `Cache::rememberForever` di
`KnowledgeEmbeddingsCache`. Menyimpannya dua kali adalah duplikasi state.

**Model `KnowledgeBase`** dengan cast json, scope `active()`, dan metode
`toKnowledgeArray()` yang mengembalikan bentuk array identik dengan entri config lama
(`['id' => slug, 'title', 'keywords', 'answer', 'source_label', 'actions']`) — sehingga
`KnowledgeRetriever` dan orchestrator tidak perlu tahu sumbernya berubah.

**Invalidasi cache:** event `saved` dan `deleted` pada Model memanggil
`KnowledgeEmbeddingsCache::refreshCache()`. Amil menyimpan → embedding ter-regenerate
otomatis, tanpa perintah artisan.

**Seeder `KnowledgeBaseSeeder`** memindahkan seluruh isi `config/zakky_knowledge.php` ke
tabel. File config dipertahankan sebagai sumber seed (bukan dihapus), agar `php artisan
db:seed` di instalasi baru tetap punya isi awal.

### 2. Peralihan pembaca knowledge

Dua tempat membaca `config('zakky_knowledge')` dan keduanya harus dialihkan:

- `KnowledgeRetriever::search()` baris 20
- `KnowledgeEmbeddingsCache::generateAllEmbeddings()` baris 46

Keduanya menjadi `KnowledgeBase::active()->get()->map->toKnowledgeArray()->all()`.

Blok di `ChatbotOrchestrator` yang mencari entri config berdasarkan `$entryId` (baris
126–131) ikut dialihkan ke `KnowledgeBase::where('slug', ...)`.

### 3. Nishab & harga emas dinamis

`ChatbotZakatMalGuide::calculate()` saat ini meng-hardcode nishab Rp65.000.000 dan harga
emas Rp900.000/gram. Keduanya berubah tiap tahun.

Dua kolom baru di **`zakat_periods`** (tabel yang benar-benar diedit form period
settings):

- `nishab_gold_gram` — unsignedSmallInteger, default 85
- `gold_price_per_gram` — unsignedBigInteger, default 900000

Nishab rupiah = `nishab_gold_gram × gold_price_per_gram`, tidak disimpan (turunan).

`AnnualZakatDefaults` DTO mendapat dua properti baru dengan nilai default, sehingga
pemanggil lama (`TransactionPayloadBuilder`) tidak berubah. `AnnualZakatDefaultsResolver`
mengambil dari period aktif; jika tidak ada period, jatuh ke
`config('zakat.annual_defaults.*')`.

`calculate()` berubah tanda tangan menjadi `calculate(array $data, AnnualZakatDefaults
$defaults)` — angka masuk dari luar, tidak lagi konstanta.

Form `internal/settings/period.blade.php` dan `UpdatePeriodSettingsRequest` mendapat dua
field tambahan.

### 4. CRUD Admin (Gudang Solusi AI)

`Internal/KnowledgeBaseController` — resource controller mengikuti pola
`PeriodSettingsController` yang sudah ada. Rute di grup `/internal` yang sudah
terproteksi middleware admin.

Satu view `internal/knowledge_base/index.blade.php`: tabel entri + form inline untuk
tambah/edit. Tanpa `create.blade.php` terpisah — satu form melayani keduanya.

### 5. Otak AI

**System prompt (`OpenAiChatbotProvider::getSystemInstruction`)** dirombak total:

- **Persona:** konsultan zakat, bukan penjawab FAQ. Memandu langkah demi langkah.
- **Proaktif:** jika variabel keuangan kurang, WAJIB bertanya, bukan menebak.
- **Larangan hitung:** dilarang menghitung nominal sendiri. Untuk zakat mal, satu-satunya
  cara menghasilkan angka adalah menerbitkan `[HITUNG:{...}]`.
- **Guardrail:** hanya menjawab dari konteks resmi. Kasus fiqih di luar konteks →
  menolak berfatwa dan merujuk ke panitia/ustadz An-Nur.

**Format sentinel:**

```
[HITUNG: {"income_monthly":10000000,"expenses_monthly":2000000,"savings":50000000,"gold_gram":0,"debt":0}]
```

Semua kunci opsional, nilai integer rupiah (gram untuk emas). Orchestrator mem-parse,
memvalidasi (integer, non-negatif, kunci dikenal), menjalankan `calculate()`, dan menukar
sentinel dengan rincian terformat. **JSON rusak atau tidak ada kunci yang valid → sentinel
dibuang diam-diam dan diganti kalimat ajakan melengkapi data.** Tidak pernah crash, tidak
pernah menampilkan sentinel mentah ke user.

**`ChatbotActionDetector`:** intent `calculate_zakat_mal_case` dan
`refer_zakat_mal_complex` dihapus — keduanya sekarang urusan LLM. Intent navigasi (buka
halaman/grafik) dan data publik tetap. Fitrah & fidyah tetap regex: inputnya satu angka,
tidak ada yang perlu dinalar.

**`ChatbotZakatMalGuide`:** `detect()` (regex ekstraksi) dihapus. `calculate()`
dipertahankan dan dijadikan penerima defaults.

**`ChatbotOrchestrator`:**

- Cache jalur AI **dimatikan** (baris 35 dan 75). Ini yang membuat AI bisa "ingat".
  Cache jalur quick-response (fitrah, fidyah, data publik, navigasi) tetap jalan — jawaban
  di sana deterministik dan tidak bergantung riwayat.
- `buildHistory()` naik dari 4 ke 8 log; sliding window di provider naik dari 3 ke 6 turn.
  Alasan: konsultasi zakat mal butuh 4–6 giliran untuk mengumpulkan variabel.
- Fallback saat OpenAI down **dipertahankan**. Itu error handling, bukan utang teknis.

### 6. UI

`resources/views/public/konsultasi.blade.php` — halaman penuh (layout guest, tinggi
viewport, gaya ChatGPT) yang me-reuse komponen chat yang ada. Rute publik `/konsultasi`.

Banner CTA di `public/home.blade.php` mengarah ke `/konsultasi`.

`<x-chatbot-widget />` dicabut dari `layouts/guest.blade.php:34` dan
`public/home.blade.php:57`. Komponennya tidak dihapus — hanya tidak lagi dipasang.

## Alur data (konsultasi zakat mal)

```
User: "Gaji saya 10 juta, tabungan 50 juta"
  → Orchestrator: bukan quick-response → jalur AI
  → KnowledgeRetriever: semantic search dari DB → konteks nishab & zakat mal
  → LLM: "Baik. Ada pengeluaran rutin bulanan atau hutang?"      (tidak ada sentinel)

User: "Cicilan rumah 2 juta sebulan"
  → buildHistory() mengirim 2 giliran sebelumnya ke LLM
  → LLM: "Oke, datanya lengkap. [HITUNG:{"income_monthly":10000000,
          "savings":50000000,"expenses_monthly":2000000}]"
  → Orchestrator mencegat sentinel
  → AnnualZakatDefaultsResolver → nishab 85g × harga emas periode aktif
  → ChatbotZakatMalGuide::calculate() → angka
  → Sentinel ditukar rincian PHP → dikirim ke user
```

## Penanganan error

| Kondisi                                   | Perilaku                                                |
|-------------------------------------------|---------------------------------------------------------|
| JSON sentinel rusak                        | Sentinel dibuang, diganti ajakan melengkapi data.       |
| Sentinel berisi nilai negatif / kunci asing| Kunci asing diabaikan, negatif ditolak → ajakan ulang.  |
| Sentinel muncul tapi semua nilai nol/kosong| Diperlakukan seperti data kurang → AI diminta bertanya. |
| OpenAI down / 429 / 401                    | Fallback yang sudah ada, tidak berubah.                 |
| Embedding gagal                            | Fallback keyword search yang sudah ada, tidak berubah.  |
| Tidak ada period aktif                     | Nishab & harga emas dari `config/zakat.php`.            |

## Pengujian

**Unit:**
- `calculate()` dengan nishab dari defaults: di bawah nishab, tepat di nishab, di atas
  nishab, hutang melebihi aset (neto negatif).
- Parser sentinel: JSON valid, JSON rusak, kunci asing, nilai negatif, sentinel ganda,
  sentinel berdampingan dengan `[SUGGEST:]`.

**Feature:**
- Guardrail: pertanyaan fiqih di luar knowledge base → jawaban merujuk panitia (provider
  di-mock).
- Memori: dua pesan berurutan dengan variabel terpisah → giliran kedua menerima riwayat
  giliran pertama.
- Cache: pertanyaan AI identik dua kali → provider dipanggil dua kali (bukan dari cache).
- CRUD knowledge base: menyimpan entri memicu regenerasi cache embedding.
- Halaman `/konsultasi` dapat diakses publik dan me-render.

## Yang sengaja tidak dikerjakan

| Dibuang                                   | Alasan                                                       |
|-------------------------------------------|--------------------------------------------------------------|
| Kolom `embedding_vector` di tabel         | Cache Laravel sudah menyimpannya. Duplikasi state.           |
| `create.blade.php` terpisah               | Satu form inline melayani tambah dan edit.                   |
| OpenAI function/tool calling              | Butuh loop dua-tahap + buffering tool_calls saat streaming. Sentinel memakai parser bracket yang sudah ada. |
| Menghapus fallback provider               | Fallback saat OpenAI down adalah error handling, bukan utang. |
| Harga emas live dari API                  | Satu angka yang diupdate Amil setahun sekali sudah cukup.    |
| Menghapus regex fitrah/fidyah             | Inputnya satu angka. Tidak ada yang perlu dinalar LLM.       |
