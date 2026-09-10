# ZakatAnNur (Professional Zakat Management & AI Analytics System)

[![Laravel](https://img.shields.io/badge/Laravel-v9.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-v8.0%2B-blue.svg)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v3.x-38bdf8.svg)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-v3.x-8bc0d0.svg)](https://alpinejs.dev)
[![AI-Powered](https://img.shields.io/badge/AI--Powered-OpenAI%20%2B%20RAG-brightgreen.svg)](docs/CHATBOT_ZAKKY.md)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**ZakatAnNur** adalah sistem informasi pengelolaan Zakat, Infaq, Fidyah, dan Sedekah terpadu berbasis web yang dirancang untuk **Masjid An-Nur Komplek BPK V Gandul** dan organisasi sosial. Aplikasi ini menggabungkan pencatatan operasional presisi tinggi dengan transparansi publik secara real-time dan **AI Assistant Chatbot Zakky** berbasis RAG (*Retrieval-Augmented Generation*).

---

## 🚀 Key Features

### 1. 💵 Multi-Category Transaction Management
- Pencatatan transaksi **Zakat Fitrah**, **Zakat Mal**, **Fidyah**, dan **Infaq/Shodaqoh** dalam satu antarmuka cepat.
- Kalkulasi otomatis nominal Zakat Fitrah per jiwa (uang/beras) dan Fidyah per hari berdasarkan tarif periode aktif.
- Proteksi konkurensi pembuatan nomor transaksi menggunakan `Cache::lock` untuk mencegah *race condition*.
- *Trash Bin* dan penanganan *Soft Deletes* untuk pemulihan atau void transaksi secara akuntabel.

### 2. 🤖 Chatbot Zakky AI (Public RAG Assistant)
- **RAG (Retrieval-Augmented Generation)**: Jawaban cerdas berbasis pencarian pengetahuan semantik (*Vector Similarity Search*) dan *keyword fallback*.
- **Multi-Model Provider**: Terintegrasi dengan OpenAI GPT (Routing otomatis *Fast Model* vs *Premium Model*) serta *Mock Provider* otomatis jika API Key tidak diset.
- **Data Privacy & Guardrails**: Filter ketat untuk memastikan data sensitif (password, nomor telepon lengkap, alamat detail) tidak terekspos ke publik.
- **Streaming Parser & Sentinel Swallowing**: Respon cepat berformat *stream* dengan deteksi intent/aksi otomatis.

### 3. 🔍 Zakky AI Admin Insights (Smart Audit & Anomaly Detection)
- Panel analisis otomatis di halaman Admin Audit Log dan Transaksi untuk mendeteksi risiko dan anomali pencatatan.
- Scoring tingkat risiko (*risk score & risk flags*) untuk mendeteksi potensi duplikasi transaksi atau pengeditan transaksi setelah cetak kwitansi.
- Caching cerdas untuk menjaga performa dashboard admin ( Audit: 1 jam, Anomali: 10 menit).

### 4. 📄 Smart Receipt & Financial Reporting
- Generasi kwitansi PDF instan berpresisi tinggi menggunakan **FPDI** dan **TCPDF** dengan skema *overlay* pada templat resmi masjid.
- Ekspor data transaksi dan rekapitulasi ke format Excel (**PhpSpreadsheet**) untuk laporan pertanggungjawaban panitia.

### 5. 📊 Public Transparency Dashboard
- Halaman ringkasan publik yang teduh dan akuntabel untuk jamaah masjid.
- Menampilkan grafik total penerimaan uang, beras, dan jiwa secara real-time tanpa mengganggu privasi data pembayar zakat.

### 6. 🏗 Modular Monolith Architecture
- Penerapan pola *"Lean Service, Fat Model"* & *Domain-Driven Boundaries* (Domain: *Transactions*, *Periods*, *Chatbot*, *Audit*, *Muzakki*, *Reporting*).
- Logika bisnis terpusat pada Service Layer (`TransactionSyncService`, `ZakatService`, `PublicSummaryService`, `DashboardInsightsService`) untuk keterawatan tingkat tinggi.

---

## 🛠 Tech Stack

- **Framework**: [Laravel 9.x](https://laravel.com) (PHP 8.0+)
- **Frontend**: [Tailwind CSS 3.x](https://tailwindcss.com), [Alpine.js 3.x](https://alpinejs.dev), [Vite](https://vitejs.dev)
- **Database**: MySQL / SQLite (native support untuk testing)
- **AI & RAG Engine**: OpenAI GPT (GPT-5.6 / GPT-4o series) + Custom Knowledge Embeddings & Retriever
- **PDF Engine**: [FPDI](https://www.setasign.com/products/fpdi/about/) & [TCPDF](https://tcpdf.org/)
- **Spreadsheet Engine**: [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/)
- **Charts & UI**: [Chart.js](https://www.chartjs.org/)
- **Testing**: PHPUnit, Laravel Dusk / Playwright E2E

---

## 🗺 Documentation Map

Dokumentasi lengkap proyek ini tersimpan secara terstruktur di folder [`docs/`](docs/README.md):

| Kategori | Dokumentasi Utama | Deskripsi |
| :--- | :--- | :--- |
| **Arsitektur & DB** | [**Architecture Boundaries**](docs/architecture-boundaries.md)<br>[**Database Schema**](docs/db-schema.md)<br>[**UI Design System**](docs/ui-design-system.md)<br>[**Product Vision**](PRODUCT.md) | Batasan modul, skema tabel MySQL/SQLite, aturan Blade/Alpine, dan visi produk. |
| **AI & Chatbot** | [**Chatbot Zakky Technical**](docs/CHATBOT_ZAKKY.md)<br>[**Konsep AI & Audit**](docs/ACUAN.md)<br>[**Behavior & Prompt Notes**](docs/chatbot-behavior-notes.md)<br>[**RAG Threshold Evaluation**](docs/rag-threshold-evaluation.md) | Endpoint API Chatbot, RAG architecture, scoring anomali admin, serta pengujian threshold RAG. |
| **Standards** | [**Engineering Conventions**](docs/engineering-conventions.md)<br>[**Collaboration Protocol**](docs/COLLAB_PROTOCOL.md) | Standar *Source of Truth*, pemisahan Blade vs Service, dan protokol kolaborasi AI. |
| **Akademis** | [**Dokumentasi Skripsi**](docs/chatbot-dokumentasi-skripsi.md)<br>[**Catatan Tesis**](docs/chatbot-thesis-notes.md)<br>[**Audit Repositori (Bab IV)**](AUDIT_REPOSITORY_UNTUK_BAB_IV.md) | Bahan penulisan karya ilmiah, pembuktian empiris, dan lampiran kode skripsi. |
| **Operations** | [**Go-Live Checklist**](docs/go-live.md)<br>[**Production ENV Example**](docs/env.production.example) | Panduan deployment ke server produksi dan templat variabel lingkungan. |

👉 Lihat [**Indeks Dokumentasi Lengkap (`docs/README.md`)**](docs/README.md) untuk rincian semua file.

---

## 📦 Installation & Setup

### Prerequisites
- **PHP**: `^8.0` (ext-mbstring, ext-xml, ext-pdo, ext-gd)
- **Composer**: `^2.0`
- **Node.js**: `^16.0` atau `^18.0` & NPM
- **Database**: MySQL 8.0+ (atau SQLite untuk pengembangan lokal)

### Step-by-Step Installation

1. **Clone Repositori**
   ```bash
   git clone https://github.com/Anantarf/zakatannur.git
   cd zakatannur
   ```

2. **Install Dependensi PHP & Node.js**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database & Chatbot AI pada `.env`**
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=zakatannur
   DB_USERNAME=root
   DB_PASSWORD=

   # AI Chatbot Configuration
   CHATBOT_PROVIDER=openai
   OPENAI_API_KEY=sk-proj-...
   OPENAI_CHAT_MODEL=gpt-5.6-terra
   OPENAI_FAST_MODEL=gpt-5.6-luna
   OPENAI_PREMIUM_MODEL=gpt-5.6-sol
   ```
   *(Catatan: Jika `OPENAI_API_KEY` dikosongkan atau `CHATBOT_PROVIDER=mock`, aplikasi secara otomatis beralih ke Mock Provider).*

5. **Jalankan Migrasi & Data Seeder**
   ```bash
   php artisan migrate --seed
   ```

6. **Build Aset Frontend & Jalankan Server**
   ```bash
   # Jalankan build frontend
   npm run build

   # Jalankan server aplikasi
   php artisan serve
   ```
   Aplikasi dapat diakses melalui `http://127.0.0.1:8000`.

---

## 🧪 Testing & Quality Assurance

Proyek ini dilengkapi dengan suite pengujian otomatis untuk menjamin keandalan transaksi dan keamanan logika bisnis:

```bash
# Jalankan suite unit & feature test (PHPUnit)
php artisan test

# Jalankan skrip verifikasi lengkap (Test + Cache View Verification)
composer verify

# Jalankan pengujian E2E (Playwright)
npx playwright test
```

---

## 📜 Core Business Rules & Concurrency Safeguards

1. **Auto-Tarif Zakat Fitrah & Fidyah**: Nilai default rupiah per jiwa dan beras per jiwa dihitung otomatis dari tabel `zakat_periods` aktif saat transaksi dibuat.
2. **Concurrency Locking**: Penomoran transaksi diproteksi dengan `Cache::lock('transaction-number-lock', 5)` untuk mencegah nomor ganda pada kondisi traffic tinggi.
3. **Audit Log & Anomaly Review**: Setiap pembuatan, pengeditan, pembatalan (*void*), atau pemulihan (*restore*) transaksi mencatat snapshot data pengguna dan memicu kalkulasi skor risiko secara otomatis.

---

Built with ❤️ for mosque transparency and community trust.
