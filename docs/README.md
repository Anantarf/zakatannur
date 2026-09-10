# 📚 ZakatAnNur Documentation Index

Selamat datang di pusat dokumentasi teknis dan akademis **ZakatAnNur** (Sistem Pengelolaan Zakat & Infaq Terpadu dengan AI Assistant Chatbot Zakky).

Dokumen-dokumen di bawah ini disusun untuk membantu pengembang, penguji, maupun peneliti (skripsi/tesis) memahami arsitektur, skema basis data, modul AI RAG, serta standar pengkodean pada proyek ini.

---

## 🗺 Navigasi Dokumentasi

### 🏗 1. Arsitektur & Desain Sistem
- [**Arsitektur & Batasan Modul (`architecture-boundaries.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/architecture-boundaries.md)  
  Prinsip *Modular Monolith*, batasan antar-domain (*Transactions*, *Periods*, *Chatbot*, *Audit*, *Muzakki*, *Reporting*), serta kebijakan migrasi database.
- [**Skema Database Reference (`db-schema.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/db-schema.md)  
  Dokumentasi struktur tabel utama (`users`, `muzakki`, `zakat_transactions`, `zakat_periods`, `audit_logs`, `chatbot_chat_logs`, `transaction_risk_reviews`, dll.) beserta relasi dan indeksnya.
- [**Panduan UI & Design System (`ui-design-system.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/ui-design-system.md)  
  Prinsip desain visual, palet warna, tipografi, pola komponen Blade/Alpine.js, dan standar aksesibilitas.
- [**Visi Produk (`../PRODUCT.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/PRODUCT.md)  
  Target pengguna (panitia zakat & jamaah), tujuan produk, kepribadian brand, serta prinsip aksesibilitas.

---

### 🤖 2. Modul AI & Chatbot Zakky (RAG)
- [**Dokumentasi Teknis Chatbot Zakky (`CHATBOT_ZAKKY.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/CHATBOT_ZAKKY.md)  
  Arsitektur `ChatbotOrchestrator`, endpoint API publik, integrasi OpenAI GPT & provider Mock fallback, serta fitur Zakky AI Admin Insights.
- [**Konsep AI Assistant & AI Audit (`ACUAN.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/ACUAN.md)  
  Konsep dasar pengembangan *Natural Language Analytics Assistant* dan *AI Audit Assistant* untuk transparansi zakat.
- [**Catatan Perilaku & Prompt Chatbot (`chatbot-behavior-notes.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/chatbot-behavior-notes.md)  
  Panduan *prompt engineering*, penanganan *sentinel tags*, bahasa, serta mekanisme *fallback* jawaban.
- [**Evaluasi Threshold RAG (`rag-threshold-evaluation.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/rag-threshold-evaluation.md)  
  Hasil pengujian empiris pencarian semantik (Vector Similarity) vs *Keyword Fallback* pada Knowledge Retriever Chatbot Zakky.
- [**Spesifikasi Desain Konsultan Zakat AI (`superpowers/specs/2026-07-14-ai-zakat-consultant-design.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/superpowers/specs/2026-07-14-ai-zakat-consultant-design.md)  
  Spesifikasi teknis awal perancangan fitur konsultasi zakat AI.
- [**Rencana Peningkatan Kualitas RAG (`superpowers/plans/2026-07-22-chatbot-rag-quality-improvements.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/superpowers/plans/2026-07-22-chatbot-rag-quality-improvements.md)  
  Rencana kerja optimasi pencarian pengetahuan dan *guardrails* keselamatan respon.

---

### 💻 3. Standar Rekayasa & Protokol Kerja
- [**Konvensi Engineering (`engineering-conventions.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/engineering-conventions.md)  
  Aturan teknis harian: *Source of Truth*, pemisahan Blade vs Service, manajemen artefak build, dan verifikasi minimal sebelum merge.
- [**Protokol Kolaborasi AI (`COLLAB_PROTOCOL.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/COLLAB_PROTOCOL.md)  
  Aturan kerja kolaborasi AI agent / assistant pada repositori proyek.

---

### 🎓 4. Dokumentasi Akademis & Skripsi
- [**Dokumentasi Skripsi Chatbot Zakky (`chatbot-dokumentasi-skripsi.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/chatbot-dokumentasi-skripsi.md)  
  Dokumentasi komprehensif implementasi RAG Chatbot untuk keperluan karya ilmiah/skripsi.
- [**Catatan Tesis & Sidang (`chatbot-thesis-notes.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/chatbot-thesis-notes.md)  
  Poin-poin penting, pertanyaan umum sidang, dan pembuktian empiris untuk pertahanan skripsi.
- [**Audit Repositori untuk Bab IV (`../AUDIT_REPOSITORY_UNTUK_BAB_IV.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/AUDIT_REPOSITORY_UNTUK_BAB_IV.md)  
  Hasil audit sistemik repositori untuk penulisan laporan penelitian Bab IV.
- [**Lampiran Kode Program (`../LAMPIRAN_KODE_PROGRAM_ZAKKY.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/LAMPIRAN_KODE_PROGRAM_ZAKKY.md)  
  Kumpulan cuplikan kode program utama Chatbot Zakky sebagai lampiran skripsi.

---

### 🚀 5. Operasional & Deployment
- [**Panduan Go-Live (`go-live.md`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/go-live.md)  
  Daftar periksa (*checklist*) dan instruksi penyiapan lingkungan produksi.
- [**Templat `.env` Produksi (`env.production.example`)**](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/docs/env.production.example)  
  Contoh konfigurasi variabel lingkungan untuk server produksi.

---

## 🛠 Cara Berkontribusi pada Dokumentasi
1. Pastikan setiap perubahan fitur diikuti dengan pembaruan dokumen terkait di folder `docs/`.
2. Jangan merubah nama file di `docs/` tanpa memperbarui referensi link pada file indeks ini dan pada [README.md](file:///c:/Users/Ananta%20Raihan/Kuliah/ZAKAT%20TRIAL/README.md) utama.
3. Jalankan `composer verify` sebelum melakukan komit untuk memastikan tidak ada Blade/route cache yang pecah akibat perubahan data backend.
