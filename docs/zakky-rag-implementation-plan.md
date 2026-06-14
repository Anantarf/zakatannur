# Zakky RAG Implementation Plan

## Tujuan

Zakky harus berubah dari chatbot generative biasa menjadi asisten yang:

- bisa membantu navigasi data publik zakat;
- menjawab dari sumber resmi aplikasi/masjid;
- tidak mengarang informasi lokal seperti nomor rekening, jadwal, panitia, nominal, atau data penerimaan;
- tetap bisa memakai API model AI untuk pertanyaan umum yang aman;
- mudah dites dan dirawat.

Target arsitektur v1 bukan vector database penuh. Target v1 adalah **hybrid RAG sederhana**:

```text
User message
-> ChatbotController
-> ChatbotOrchestrator
-> Local Action Detector
-> Public Data Context
-> Curated Knowledge Retriever
-> AI Provider fallback
-> JSON response with reply/actions/citations
-> Frontend executes actions
```

## Prinsip Eksekusi

- Fokus fungsional dulu, UI polish setelah flow chatbot benar.
- Jangan langsung membuat vector DB, embedding, admin UI dokumen, atau migration besar.
- Mulai dari knowledge base curated di config PHP agar mudah direview.
- Pertahankan route existing: `POST /api/chatbot/message`.
- Pertahankan provider existing: `GeminiChatbotProvider`, `MockChatbotProvider`.
- Jangan ubah public page data structure kecuali diperlukan untuk action chatbot.
- Semua response harus aman saat AI provider down.
- Jika konteks tidak cukup, Zakky harus bilang tidak tahu atau arahkan ke ringkasan/grafik, bukan mengarang.

## Current State

Saat ini flow masih direct:

```text
Frontend chatbot
-> /api/chatbot/message
-> ChatbotController
-> ChatbotServiceInterface
-> Gemini/Mock Provider
-> reply string
```

Keterbatasan saat ini:

- belum ada retriever;
- belum ada curated knowledge base;
- belum ada citation/source;
- belum ada response action resmi dari backend;
- sebagian aksi tab masih diproses frontend;
- AI bisa menjawab generative tanpa grounding lokal;
- controller masih terlalu tahu detail fallback provider.

## Target Response API

Controller tetap menerima:

```json
{
  "message": "string"
}
```

Response sukses:

```json
{
  "status": "success",
  "data": {
    "reply": "Saya buka tab Ringkasan Penerimaan.",
    "source": "action",
    "actions": [
      {
        "type": "open_tab",
        "target": "laporan"
      }
    ],
    "citations": []
  }
}
```

Response knowledge:

```json
{
  "status": "success",
  "data": {
    "reply": "Zakat fitrah adalah zakat yang ditunaikan menjelang Idulfitri...",
    "source": "knowledge",
    "actions": [],
    "citations": [
      {
        "label": "Panduan Zakat Masjid An-Nur",
        "id": "zakat-fitrah"
      }
    ]
  }
}
```

Response error retryable:

```json
{
  "status": "error",
  "message": "Layanan asisten AI sedang tidak tersedia saat ini. Silakan coba beberapa saat lagi.",
  "retryable": true
}
```

Catatan kompatibilitas:

- Frontend harus tetap bisa membaca format lama selama transisi:
  `payload.data.reply`.
- Jika `actions` tidak ada, anggap array kosong.
- Jika `citations` tidak ada, anggap array kosong.

## File dan Komponen Baru

Tambahkan:

- `app/Services/Chatbot/ChatbotOrchestrator.php`
- `app/Services/Chatbot/ChatbotResponse.php`
- `app/Services/Chatbot/ChatbotActionDetector.php`
- `app/Services/Chatbot/Knowledge/KnowledgeRetriever.php`
- `app/Services/Chatbot/Knowledge/KnowledgeEntry.php` jika dibutuhkan
- `config/zakky_knowledge.php`

Update:

- `app/Http/Controllers/Api/ChatbotController.php`
- `app/Providers/ChatbotServiceProvider.php`
- `app/Services/Chatbot/ChatbotServiceInterface.php`
- `app/Services/Chatbot/Providers/GeminiChatbotProvider.php`
- `app/Services/Chatbot/Providers/MockChatbotProvider.php`
- `resources/js/chatbot-widget.js`
- `resources/views/components/chatbot-widget.blade.php`

## Phase 1 - DTO dan Orchestrator

### Implementasi

Buat `ChatbotResponse` sebagai object sederhana.

Field minimal:

- `string $reply`
- `string $source`
- `array $actions`
- `array $citations`
- `bool $retryable`
- `int $statusCode`

Source values:

- `action`
- `public_data`
- `knowledge`
- `ai`
- `fallback`

Tambahkan helper factory:

- `ChatbotResponse::success(...)`
- `ChatbotResponse::error(...)`

Buat `ChatbotOrchestrator` dengan method:

```php
public function handle(string $message): ChatbotResponse
```

Precedence wajib:

```text
1. local action
2. public data answer
3. curated knowledge
4. AI fallback
5. safe fallback error
```

### Catatan Penting

- Controller tidak boleh lagi memotong `FALLBACK_PREFIX`.
- Logic fallback provider harus dibungkus di orchestrator.
- Controller hanya:
  - validate message;
  - call orchestrator;
  - return JSON dari DTO.

## Phase 2 - Local Action Detector

### Intent Awal

Intent `open_summary`:

- ringkasan
- penerimaan
- total zakat
- data zakat
- laporan

Action:

```json
{ "type": "open_tab", "target": "laporan" }
```

Reply:

```text
Saya buka tab Ringkasan Penerimaan. Di sana jamaah bisa melihat total jiwa, uang, beras, dan rincian kategori.
```

Intent `open_chart`:

- grafik
- harian
- chart
- tren
- pola penerimaan

Action:

```json
{ "type": "open_tab", "target": "grafik" }
```

Reply:

```text
Saya buka tab Grafik Harian. Di sana jamaah bisa melihat pola penerimaan per hari.
```

### Implementasi

Buat:

```php
public function detect(string $message): ?ChatbotResponse
```

Gunakan normalisasi sederhana:

- lowercase;
- trim;
- hapus spasi ganda;
- jangan pakai regex rumit jika tidak perlu.

## Phase 3 - Public Data Context

### Tujuan

Zakky bisa menjawab pertanyaan seperti:

- "total penerimaan berapa?"
- "berapa total jiwa?"
- "berapa beras terkumpul?"
- "kategori zakat apa saja?"

### Implementasi

Tambahkan service kecil atau method orchestrator untuk mengambil data yang sama dengan public summary.

Jangan scraping DOM.

Utamakan reuse service/query yang dipakai:

- `GuestSummaryController`
- service summary existing jika ada
- query existing yang sudah dipercaya

Jika belum ada service reusable, buat wrapper read-only kecil agar tidak duplikasi query berat.

### Jawaban Minimal

Untuk total:

```text
Total penerimaan saat ini: {total_jiwa} jiwa, {total_uang}, dan {total_beras}. Data ini mengikuti ringkasan publik periode berjalan.
```

Untuk kategori:

```text
Kategori yang tercatat saat ini: Zakat Fitrah, Fidyah, Zakat Mal, dan Infaq Shodaqoh.
```

Jika data kosong:

```text
Belum ada data penerimaan yang tercatat untuk periode ini.
```

### Catatan Penting

- Jangan izinkan AI mengarang angka.
- Jawaban angka harus berasal dari data aplikasi.
- Format angka harus sama dengan frontend jika memungkinkan.

## Phase 4 - Curated Knowledge Base

### File

Buat `config/zakky_knowledge.php`.

Struktur:

```php
return [
    [
        'id' => 'cara-bayar-zakat',
        'title' => 'Cara bayar zakat',
        'keywords' => ['bayar', 'pembayaran', 'transfer', 'cara bayar', 'zakat fitrah'],
        'answer' => '...',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [],
    ],
];
```

### Konten Awal

Wajib ada item:

- `cara-bayar-zakat`
- `zakat-fitrah`
- `zakat-mal`
- `fidyah`
- `batas-waktu-zakat`
- `cara-baca-ringkasan`
- `cara-baca-grafik`
- `batas-kemampuan-zakky`

### Catatan Konten

- Jangan isi nomor rekening jika belum ada data resmi.
- Jika belum ada info teknis pembayaran, jawab:

```text
Informasi cara pembayaran mengikuti arahan panitia zakat Masjid An-Nur. Jika belum tersedia di portal, silakan konfirmasi kepada panitia.
```

- Jangan mengarang jadwal spesifik.
- Jangan memberi fatwa detail yang butuh otoritas khusus.
- Untuk zakat umum, jawab singkat dan anjurkan konfirmasi ke panitia/ustadz jika kasusnya khusus.

## Phase 5 - Knowledge Retriever

### Implementasi

Buat `KnowledgeRetriever`.

Method:

```php
public function search(string $message, int $limit = 3): array
```

Scoring v1:

- cocok keyword exact: +3
- cocok title token: +2
- cocok answer token penting: +1 jika mudah dilakukan

Threshold:

- jika top score >= 3, pakai knowledge result;
- jika kurang dari 3, lanjut AI fallback.

Jawaban v1:

- Untuk top result yang jelas, balas `answer` langsung.
- Sertakan citation:

```php
[
    'id' => $entry['id'],
    'label' => $entry['source_label'],
]
```

### Catatan Penting

- Jangan bikin retrieval terlalu pintar dulu.
- Jangan pakai package baru.
- Jangan pakai database dulu.
- Pastikan config cache aman:
  `php artisan config:cache` tidak boleh error.

## Phase 6 - Grounded AI Fallback

### Interface Provider

Update `ChatbotServiceInterface`.

Opsi minimal:

```php
public function sendMessage(string $message, array $context = []): string;
```

Update semua provider agar signature sama.

### Gemini Prompt

System instruction harus mencakup:

```text
Anda adalah Zakky, asisten virtual Zakat An-Nur.
Jawab singkat, sopan, dan jelas.
Untuk informasi lokal Masjid An-Nur, gunakan hanya konteks yang diberikan.
Jika konteks tidak cukup, katakan bahwa informasi belum tersedia dan arahkan pengguna untuk konfirmasi ke panitia.
Jangan mengarang nomor rekening, jadwal, panitia, nominal, data penerimaan, atau kebijakan lokal.
Jika pertanyaan di luar zakat/operasional portal, tolak singkat dan kembalikan ke topik zakat.
```

Jika context dikirim ke provider, format prompt user:

```text
Konteks resmi:
{context}

Pertanyaan pengguna:
{message}
```

### Catatan

- AI fallback tidak boleh menerima seluruh knowledge base.
- Kirim hanya top context yang relevan.
- Batasi output tetap pendek.
- Temperature disarankan turun dari `0.7` ke `0.3` atau `0.4`.

## Phase 7 - Frontend Actions dan Citations

### Frontend Behavior

Update `resources/js/chatbot-widget.js`.

Saat menerima response:

- push bot reply;
- jika ada `actions`, execute;
- jika ada `citations`, simpan di message object.

Message object:

```js
{
  role: 'bot',
  content: payload.data.reply,
  source: payload.data.source,
  actions: payload.data.actions || [],
  citations: payload.data.citations || [],
  createdAt: nowIso()
}
```

Action handler:

```js
open_tab -> dispatch public-home:set-tab
```

### UI Minimal

Jangan polish besar dulu.

Tampilkan citation kecil di bawah bot bubble:

```text
Sumber: Panduan Zakat Masjid An-Nur
```

Hanya tampil jika `citations.length > 0`.

### Quick Replies

Update quick replies:

- Buka ringkasan
- Lihat grafik
- Total penerimaan
- Cara bayar zakat

Untuk `Total penerimaan`, kirim message:

```text
Berapa total penerimaan zakat saat ini?
```

## Phase 8 - Tests

### Feature Tests

Buat atau update test untuk:

- empty message -> validation error
- too long message -> validation error
- "buka ringkasan" -> `source=action`, action `open_tab:laporan`
- "lihat grafik" -> `source=action`, action `open_tab:grafik`
- "berapa total penerimaan" -> `source=public_data`
- "cara bayar zakat" -> `source=knowledge`, ada citation
- provider fallback -> response error retryable

### Unit Tests

Untuk `ChatbotActionDetector`:

- ringkasan keywords
- grafik keywords
- unrelated returns null

Untuk `KnowledgeRetriever`:

- keyword exact match returns expected entry
- weak/no match returns empty
- limit respected

Untuk `ChatbotOrchestrator`:

- action lebih prioritas dari knowledge
- public data lebih prioritas dari AI
- knowledge lebih prioritas dari AI
- AI dipanggil hanya saat tidak ada action/data/knowledge

### Commands

Minimal setelah implementasi:

```bash
php artisan view:cache
npm run build
php artisan test --filter=Chatbot
```

Jika test filter belum ada, jalankan targeted test file yang dibuat.

## Acceptance Criteria

Functional:

- "Buka ringkasan" membuka tab Ringkasan tanpa call AI.
- "Lihat grafik" membuka tab Grafik tanpa call AI.
- "Berapa total penerimaan?" dijawab dari data aplikasi.
- "Cara bayar zakat?" dijawab dari curated knowledge.
- Pertanyaan yang tidak ada knowledge tidak mengarang info lokal.
- Jika AI provider down, user mendapat pesan retryable yang jelas.

API:

- Response punya `source`, `actions`, dan `citations`.
- Format lama `data.reply` tetap tersedia.
- Error response tetap punya `retryable`.

Frontend:

- Action dari backend dieksekusi.
- Citation bisa tampil kecil di bot message.
- Chat tetap bisa dipakai jika `actions/citations` kosong.

Maintainability:

- Controller tipis.
- Provider hanya urus komunikasi AI.
- Orchestrator mengatur flow.
- Knowledge base bisa direview tanpa baca kode provider.

## Non Goals v1

Jangan implement di v1:

- vector database;
- embeddings;
- admin UI untuk dokumen;
- upload PDF;
- scraping website;
- conversation memory database;
- streaming response;
- multi-provider routing kompleks;
- role-based personalized answer;
- UI redesign besar chatbot.

## Risiko dan Guardrail

Risiko:

- AI mengarang informasi lokal.
- Knowledge base terlalu sedikit sehingga user kecewa.
- Action detector terlalu agresif.
- Query public data duplikatif dan rawan beda angka.

Guardrail:

- Prompt melarang mengarang info lokal.
- Knowledge answer untuk pembayaran/jadwal harus konservatif jika belum ada data resmi.
- Action keyword jangan terlalu umum seperti "lihat" saja.
- Public data harus reuse sumber data ringkasan.
- Tests wajib cover precedence.

## Urutan Eksekusi yang Disarankan

1. Buat DTO `ChatbotResponse`.
2. Buat `ChatbotActionDetector`.
3. Buat `ChatbotOrchestrator` dengan action-only dulu.
4. Update controller pakai orchestrator.
5. Update frontend membaca `actions`.
6. Tambah knowledge config.
7. Buat `KnowledgeRetriever`.
8. Tambah public data answer.
9. Update AI provider agar menerima context.
10. Tambah tests.
11. Baru rapikan UI chatbot.

## Catatan untuk AI Executor

- Kerjakan bertahap, jangan sekaligus rewrite semua chatbot.
- Setelah setiap phase kecil, jalankan test/build yang relevan.
- Jangan menghapus provider existing.
- Jangan ubah route public API kecuali response body tambahan.
- Hindari dependency baru.
- Jika ada service summary existing, reuse.
- Jika tidak yakin sumber data publik, inspeksi `GuestSummaryController` sebelum membuat query baru.
- Jangan memperbaiki UI besar sampai functional flow selesai.

## Backlog Lanjutan Setelah RAG v1

Bagian ini dipakai untuk pekerjaan berikutnya setelah fondasi hybrid RAG v1 masuk.

### 1. Matangkan Knowledge Base

File utama:

```text
config/zakky_knowledge.php
```

Yang perlu dilengkapi dengan data resmi:

- cara bayar zakat;
- kontak/panitia yang boleh disebut;
- lokasi penerimaan zakat;
- jam layanan atau periode penerimaan;
- ketentuan zakat fitrah lokal;
- ketentuan fidyah lokal;
- cara membaca ringkasan penerimaan;
- cara membaca grafik harian;
- batas kemampuan Zakky.

Format entry:

```php
[
    'id' => 'cara-bayar-zakat',
    'title' => 'Cara bayar zakat',
    'keywords' => ['bayar', 'pembayaran', 'transfer'],
    'answer' => 'Jawaban resmi yang sudah disetujui panitia.',
    'source_label' => 'Panduan Zakat Masjid An-Nur',
    'actions' => [],
]
```

Catatan:

- Jangan isi nomor rekening jika belum resmi.
- Jangan isi jadwal jika belum resmi.
- Jika data belum ada, tulis jawaban konservatif:

```text
Informasi ini belum tersedia di portal. Silakan konfirmasi kepada panitia zakat Masjid An-Nur.
```

### 2. Tambah Data Lokal Resmi

Pisahkan data lokal dari FAQ umum agar Zakky tidak mengarang.

Contoh struktur yang bisa ditambahkan nanti:

```php
'local_info' => [
    'masjid_name' => 'Masjid An-Nur',
    'area' => 'Komplek BPK V Gandul',
    'committee_name' => 'Panitia Zakat Masjid An-Nur',
    'payment_methods' => [
        'Tunai melalui panitia zakat',
    ],
    'service_hours' => null,
    'bank_account' => null,
]
```

Aturan:

- Field yang belum resmi isi `null`.
- Jika `bank_account` null, Zakky tidak boleh menyebut nomor rekening.
- Jika `service_hours` null, Zakky tidak boleh membuat jam layanan sendiri.
- Jika kontak panitia belum resmi, Zakky hanya boleh mengarahkan untuk konfirmasi ke panitia.

### 3. Tambah Test Retriever

Tambahkan unit test untuk `KnowledgeRetriever`.

Skenario minimal:

- `cara bayar zakat` menemukan `cara-bayar-zakat`;
- `nishab zakat mal` menemukan `zakat-mal`;
- `batas waktu bayar` menemukan `batas-waktu-zakat`;
- pertanyaan unrelated mengembalikan null atau hasil di bawah threshold;
- limit hasil retrieval dipatuhi.

Tujuan:

- knowledge base yang makin besar tidak membuat retrieval ngawur;
- perubahan keyword bisa divalidasi otomatis.

### 4. Tambah Test Orchestrator

Tambahkan unit/feature test untuk precedence:

```text
action > public data > knowledge > AI fallback
```

Skenario minimal:

- `buka ringkasan` tidak memanggil AI dan mengembalikan action `open_tab:laporan`;
- `lihat grafik` tidak memanggil AI dan mengembalikan action `open_tab:grafik`;
- `berapa total penerimaan zakat` memakai data aplikasi;
- `cara bayar zakat` memakai curated knowledge;
- `halo` atau pertanyaan umum masuk ke AI/mock fallback;
- saat AI provider fallback, response tetap aman dan retryable.

Tujuan:

- Zakky tidak langsung generative;
- angka publik tidak dihasilkan AI;
- knowledge resmi lebih diprioritaskan daripada AI.

### 5. Audit Jawaban Real API Model AI

Setelah API Gemini aktif, lakukan audit manual atau semi-otomatis.

Prompt wajib:

```text
Nomor rekening zakatnya berapa?
Kapan panitia buka?
Siapa ketua panitia?
Berapa total zakat sekarang?
Apa itu zakat mal?
Saya mau lihat grafik
Bisa bayar via transfer?
Bagaimana cara bayar zakat?
Apa saja kategori zakat?
Apakah data ini real-time?
```

Checklist audit:

- tidak mengarang nomor rekening;
- tidak mengarang jadwal;
- tidak mengarang nama panitia;
- tidak mengarang nominal/data penerimaan;
- jawaban tetap singkat;
- jika konteks tidak ada, Zakky mengaku informasi belum tersedia;
- jika pertanyaan meminta data publik, Zakky mengarah ke data aplikasi;
- jika pertanyaan navigasi, Zakky membuka tab terkait.

Catat hasil audit di file baru:

```text
docs/audit/zakky-ai-answer-audit.md
```

Format audit:

```md
## Prompt
Nomor rekening zakatnya berapa?

## Expected
Tidak menyebut nomor rekening jika belum ada data resmi.

## Actual
...

## Verdict
Pass/Fail

## Fix
...
```

### 6. Pertimbangkan Embedding/Vector

Jangan masuk embedding/vector sebelum kondisi ini terpenuhi:

- knowledge base sudah banyak;
- keyword search mulai sering salah;
- dokumen menjadi panjang;
- ada PDF/SOP/arsip yang perlu dicari semantik;
- citations per dokumen benar-benar dibutuhkan.

Jika masuk phase vector, desain baru:

- table `chatbot_documents`;
- table `chatbot_chunks`;
- embedding provider;
- vector store atau similarity search;
- admin UI untuk kelola dokumen;
- citations per chunk.

Untuk 10-30 FAQ, keyword retriever masih cukup.
